<?php

namespace GraphQL\QueryBuilder;

use GraphQL\Query;
use GraphQL\RawObject;
use GraphQL\Variable;
use Stringable;

abstract class AbstractQueryBuilder implements QueryBuilderInterface
{
    protected Query $query;

    /** @var Variable[] */
    private array $variables = [];

    /** @var array */
    private array $selectionSet = [];

    /** @var array<null|scalar|array<?scalar>|RawObject> */
    private array $argumentsList = [];

    public function __construct(string $queryObject = '', string $alias = '')
    {
        $this->query = new Query($queryObject, $alias);
    }

    public function setAlias(string $alias): self
    {
        $this->query->setAlias($alias);

        return $this;
    }

    public function getQuery(): Query
    {
        // Convert nested query builders to query objects
        foreach ($this->selectionSet as $key => $field) {
            if ($field instanceof QueryBuilderInterface) {
                $this->selectionSet[$key] = $field->getQuery();
            }
        }

        $this->query->setVariables($this->variables);
        $this->query->setArguments($this->argumentsList);
        $this->query->setSelectionSet($this->selectionSet);

        return $this->query;
    }

    protected function selectField(
        Query|QueryBuilder|string $selectedField,
    ): AbstractQueryBuilder {
        $this->selectionSet[] = $selectedField;

        return $this;
    }

    /** @param null|scalar|array<?scalar>|RawObject $argumentValue */
    protected function setArgument(
        string $argumentName,
        null|bool|float|int|string|array|Stringable $argumentValue
    ): AbstractQueryBuilder {
        if (
            is_scalar($argumentValue) ||
            is_array($argumentValue) ||
            $argumentValue instanceof RawObject
        ) {
            $this->argumentsList[$argumentName] = $argumentValue;
        }

        return $this;
    }

    /** @param null|scalar|array<?scalar>|RawObject $defaultValue */
    protected function setVariable(
        string $name,
        string $type,
        bool $isRequired = false,
        mixed $defaultValue = null
    ): AbstractQueryBuilder {
        $this->variables[] = new Variable(
            $name,
            $type,
            $isRequired,
            $defaultValue
        );

        return $this;
    }
}
