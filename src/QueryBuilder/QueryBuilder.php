<?php

namespace GraphQL\QueryBuilder;

use BackedEnum;
use GraphQL\InlineFragment;
use GraphQL\Query;
use GraphQL\RawObject;
use Stringable;

class QueryBuilder extends AbstractQueryBuilder
{
    public function selectField(
        InlineFragment | Query | QueryBuilderInterface | string $selection
    ): AbstractQueryBuilder {
        return parent::selectField($selection);
    }

    /** @param null|scalar|array<mixed>|BackedEnum|Stringable $value */
    public function setArgument(
        string $name,
        null|bool|float|int|string|array|BackedEnum|Stringable $value,
    ): AbstractQueryBuilder {
        return parent::setArgument($name, $value);
    }

    /** @param null|scalar|array<?scalar>|RawObject $defaultValue */
    public function setVariable(
        string $name,
        string $type,
        bool $isRequired = false,
        null|bool|float|int|string|array|RawObject $defaultValue = null,
    ): AbstractQueryBuilder {
        return parent::setVariable($name, $type, $isRequired, $defaultValue);
    }
}
