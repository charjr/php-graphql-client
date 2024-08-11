<?php

namespace GraphQL;

use BackedEnum;
use GraphQL\Exception\ArgumentException;
use GraphQL\Exception\InvalidSelectionException;
use GraphQL\Exception\InvalidVariableException;
use GraphQL\QueryBuilder\QueryBuilderInterface;
use GraphQL\Util\StringLiteralFormatter;
use Stringable;

class Query implements Stringable
{
    /**
     * The GraphQL query format
     *
     * First string is object name
     * Second string is arguments
     * Third string is selection set
     */
    protected const QUERY_FORMAT = '%s%s%s';

    /** The type of the operation to be executed on the GraphQL server */
    protected const OPERATION_TYPE = OperationType::Query->value;

    /** The name of the operation to be run on the server */
    protected string $operationName = '';

    /**
     * The list of variables to be used in the query
     *
     * @var Variable[]
     */
    protected array $variables;

    /**
     * The list of arguments used when querying data
     *
     * @var array<null|array<mixed>|scalar|Stringable|BackedEnum>
     */
    protected array $arguments = [];

    /**
     * Stores the selection set desired to get from the query, can include nested queries
     *
     * @var array<string|InlineFragment|Query>
     */
    protected array $selectionSet;

    protected bool $isNested = false;

    /**
     * GQLQueryBuilder constructor.
     *
     * @param string $fieldName if no value is provided,  empty query object is assumed
     * @param string $alias the alias to use for the query if required
     */
    public function __construct(
        protected string $fieldName = '',
        protected string $alias = ''
    ) {
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function setAlias(string $alias): Query
    {
        $this->alias = $alias;

        return $this;
    }

    public function setOperationName(string $operationName): Query
    {
        if (!empty($operationName)) {
            $this->operationName = " $operationName";
        }

        return $this;
    }

    /** @param Variable[] $variables */
    public function setVariables(array $variables): Query
    {
        foreach ($variables as $variable) {
            if (!$variable instanceof Variable) {
                throw new InvalidVariableException(
                    'All variables must be an instance of GraphQL\\Variable'
                );
            }
        }

        $this->variables = $variables;

        return $this;
    }

    /**
     * @param array<null|scalar|array<mixed>|Stringable|BackedEnum> $arguments
     * @throws ArgumentException for invalid arguments
     */
    public function setArguments(array $arguments): Query
    {
        foreach ($arguments as $name => $argument) {
            if (!is_string($name)) {
                throw new ArgumentException(
                    'All query arguments require string keys,' .
                    'these represent the argument name'
                );
            }

            $this->validateArgument($argument);
        }

        $this->arguments = $arguments;

        return $this;
    }

    private function validateArgument(mixed $value): void
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $this->validateArgument($item);
            }

            return;
        }

        if (
            is_null($value)
            || is_scalar($value)
            || $value instanceof Stringable
            || $value instanceof BackedEnum
        ) {
            return;
        }

        throw new ArgumentException(sprintf(
            '%s cannot be supported',
            gettype($value),
        ));
    }

    /** @return array<null|scalar|array<?scalar>|Stringable|BackedEnum> */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    protected function constructVariables(): string
    {
        if (empty($this->variables)) {
            return '';
        }

        return sprintf('( %s )', implode(' ', $this->variables));
    }

    protected function constructArguments(): string
    {
        if (empty($this->arguments)) {
            return '';
        }

        $formattedArguments = [];
        foreach ($this->arguments as $name => $value) {
            $formattedArguments[] = sprintf('%s: %s', $name, is_array($value) ?
                    StringLiteralFormatter::formatArrayForGQLQuery($value) :
                    StringLiteralFormatter::formatValueForRHS($value));
        }

        return sprintf('(%s)', implode(' ', $formattedArguments));
    }

    protected function generateFieldName(): string
    {
        return empty($this->alias) ? $this->fieldName : sprintf('%s: %s', $this->alias, $this->fieldName);
    }

    protected function generateSignature(): string
    {
        return sprintf(
            '%s%s%s',
            static::OPERATION_TYPE,
            $this->operationName,
            $this->constructVariables()
        );
    }

    public function setAsNested(): void
    {
        $this->isNested = true;
    }

    /**
     * @param array<InlineFragment|Query|QueryBuilderInterface|string> $selectionSet
     * @throws InvalidSelectionException
     */
    public function setSelectionSet(array $selectionSet): self
    {
        $selectionSet = array_map(
            fn ($s) => $s instanceof QueryBuilderInterface ?
                $s->getQuery() :
                $s,
            $selectionSet,
        );


        foreach ($selectionSet as $selection) {
            if (
                !is_string($selection) &&
                !$selection instanceof Query &&
                !$selection instanceof InlineFragment
            ) {
                throw new InvalidSelectionException(sprintf(
                    'Can only set a selection from one of the following: %s',
                    implode(', ', [
                        InlineFragment::class,
                        Query::class,
                        QueryBuilderInterface::class,
                        'string',
                    ]),
                ));
            }
        }

        $this->selectionSet = $selectionSet;
        return $this;
    }

    /** @return array<string|InlineFragment|Query> */
    public function getSelectionSet(): array
    {
        return $this->selectionSet;
    }

    protected function constructSelectionSet(): string
    {
        if (empty($this->selectionSet)) {
            return '';
        }

        return sprintf(' { %s }', implode(' ', array_map(
            function ($selection) {
                if ($selection instanceof Query) {
                    $selection->setAsNested();
                }
                return $selection;
            },
            $this->selectionSet,
        )));
    }

    public function __toString(): string
    {
        $queryFormat = self::QUERY_FORMAT;
        $selectionSetString = $this->constructSelectionSet();

        if (!$this->isNested) {
            $queryFormat = $this->generateSignature();
            if ($this->fieldName === '') {
                return $queryFormat . $selectionSetString;
            } else {
                $queryFormat = $this->generateSignature() . ' { ' . static::QUERY_FORMAT . ' }';
            }
        }
        $argumentsString = $this->constructArguments();

        return sprintf($queryFormat, $this->generateFieldName(), $argumentsString, $selectionSetString);
    }
}
