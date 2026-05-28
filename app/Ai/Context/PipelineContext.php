<?php

namespace App\Ai\Context;

class PipelineContext
{
    // Handle both camelCase and snake_case properties explicitly
    public int $pipelineId = 0;
    public int $pipeline_id = 0;

    public function __construct(
        public array $data = []
    ) {
        // 1. Extract the ID using any possible key naming variant
        $id = data_get($data, 'pipelineId', data_get($data, 'pipeline_id', 0));

        // 2. Assign to both property variants to stay safe
        $this->pipelineId = (int) $id;
        $this->pipeline_id = (int) $id;
    }

    // Magic getter fallback in case the executor reads it like a dynamic property
    public function __get(string $key)
    {
        if ($key === 'pipelineId' || $key === 'pipeline_id') {
            return $this->pipelineId;
        }
        return $this->get($key);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        // If the executor runs $context->get('pipelineId'), intercept and return the int
        if ($key === 'pipelineId' || $key === 'pipeline_id') {
            return $this->pipelineId;
        }

        return data_get($this->data, $key, $default);
    }

    public function set(string $key, mixed $value): static
    {
        data_set($this->data, $key, $value);

        if ($key === 'pipelineId' || $key === 'pipeline_id') {
            $this->pipelineId = (int) $value;
            $this->pipeline_id = (int) $value;
        }

        return $this;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
