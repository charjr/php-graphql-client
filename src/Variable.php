<?php

namespace GraphQL;

final readonly class Variable implements \Stringable
{
    /** @param null|scalar|array<mixed>|RawObject $defaultValue */
    public function __construct(
        public string $name,
        public string $type,
        public bool $nonNullable = false,
        public null|bool|float|int|string|array|RawObject $defaultValue = null,
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
