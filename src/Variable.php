<?php

namespace GraphQL;

use BackedEnum;
use Stringable;

final readonly class Variable implements \Stringable
{
    /**
     * @param null|array<mixed>|scalar|BackedEnum|Stringable $defaultValue
     */
    public function __construct(
        public string $name,
        public string $type,
        public bool $nonNullable = false,
        public null|bool|float|int|string|array|Stringable|BackedEnum $defaultValue = null,
    ) {
    }

    public function __toString(): string
    {
        return sprintf(
            '$%s: %s%s%s',
            $this->name,
            $this->type,
            $this->nonNullable ? '!' : '',
            isset($this->defaultValue) ?
                sprintf('=%s', json_encode($this->defaultValue)) :
                '',
        );
    }
}
