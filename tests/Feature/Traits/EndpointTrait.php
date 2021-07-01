<?php

namespace Tests\Feature\Traits;

use Illuminate\Support\Str;

trait EndpointTrait
{
    /**
     * Endpoint with param.
     *
     * @param $param
     * @param ?string $path
     * @param array $queries
     *
     * @return string
     */
    private function endpointWithParam($param, ?string $path = '', array $queries = []): string
    {
        return Str::of($this->endpoint)
            ->finish('/')
            ->append($param ?? '')
            ->append($path ?? '')
            ->append(count($queries) ? '?' : '')
            ->append(http_build_query($queries));
    }
}