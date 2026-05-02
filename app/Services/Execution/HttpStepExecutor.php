<?php

namespace App\Services\Execution;

use Illuminate\Support\Facades\Http;

class HttpStepExecutor
{
    public static function execute(array $stepDefinition, array $input = []): array
    {
        $url = $stepDefinition['url'] ?? null;

        if (! $url) {
            throw new \InvalidArgumentException('HTTP step missing url');
        }

        $response = Http::post($url, $input);

        return [
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
        ];
    }
}
