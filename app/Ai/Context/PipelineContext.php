<?php

namespace App\Ai\Context;

class PipelineContext
{
    public function __construct(
        public array $data = []
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->data, $key, $default);
    }

    public function set(string $key, mixed $value): static
    {
        data_set($this->data, $key, $value);

        return $this;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
