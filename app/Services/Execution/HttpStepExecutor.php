<?php

namespace App\Services\Execution;

use Illuminate\Support\Facades\Http;

class HttpStepExecutor
{
    public static function execute(array $stepDefinition, array $input = []): array
    {
        $config = $stepDefinition['config'] ?? [];

        $url = $config['url'] ?? null;

        if (! $url) {
            throw new \InvalidArgumentException('HTTP step missing config.url');
        }

        // Auto-prepend scheme jika tidak ada
        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        $method = strtolower($config['method'] ?? 'get');

        // Apply step-level timeout (default: 300 seconds)
        $timeout = $stepDefinition['timeout'] ?? 300;

        $httpRequest = Http::{$method}($url, $input);

        // Apply timeout to the request
        $response = $httpRequest->timeout($timeout);

        // Throw jika HTTP error (4xx/5xx)
        if ($response->failed()) {
            throw new \RuntimeException(
                "HTTP {$response->status()} from {$url}: " .
                substr($response->body(), 0, 200) // truncate body panjang
            );
        }

        return [
            'status' => $response->status(),
            'body'   => $response->json() ?? $response->body(),
        ];
    }
}
