<?php

namespace Tests\Unit;

use App\Services\Execution\HttpStepExecutor;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HttpStepExecutorTest extends TestCase
{
    public function test_http_step_executes_with_configured_timeout(): void
    {
        Http::fake([
            'https://example.com/*' => Http::response(['ok' => true], 200),
        ]);

        $result = HttpStepExecutor::execute([
            'type' => 'http',
            'timeout' => 2,
            'config' => [
                'url' => 'example.com/hooks',
                'method' => 'post',
            ],
        ], ['payload' => ['id' => 123]]);

        $this->assertSame(200, $result['status']);
        $this->assertSame(['ok' => true], $result['body']);

        Http::assertSent(fn ($request) => $request->url() === 'https://example.com/hooks');
    }
}
