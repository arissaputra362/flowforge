<?php

namespace App\Services\AI;

use App\Models\StepRun;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FailureAnalyzerService
{
    private PromptBuilder $promptBuilder;
    private ResponseValidator $validator;

    public function __construct(PromptBuilder $promptBuilder, ResponseValidator $validator)
    {
        $this->promptBuilder = $promptBuilder;
        $this->validator = $validator;
    }

    public function analyze(StepRun $stepRun): array
    {
        
        \Log::debug("ANALIZE GEMINI");
        $prompt = $this->promptBuilder->build($stepRun);
        $apiKey = config('services.gemini.api_key');

        \Log::debug("GEMINI_API_KEY");
        \Log::debug($apiKey);
        if (empty($apiKey)) {
            return $this->fallback('Gemini API key is not configured.');
        }

        try {
            $response = Http::timeout(10)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key={$apiKey}",
                [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json'
                    ]
                ]
            );

            if ($response->failed()) {
                Log::error('Gemini API Error', ['response' => $response->body()]);
                return $this->fallback('AI service returned an error.');
            }

            $body = $response->json();
            $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';

            $parsed = $this->validator->parse($text);

            if ($this->validator->isValid($parsed)) {
                return $parsed;
            }

            return $this->fallback('AI returned invalid JSON schema.');

        } catch (\Throwable $e) {
            Log::error('AI Failure Analysis Exception', ['error' => $e->getMessage()]);
            return $this->fallback('Timeout or connection error to AI service.');
        }
    }

    private function fallback(string $reason): array
    {
        return [
            'root_cause' => 'Unknown',
            'suggestion' => 'Check logs manually. Fallback triggered: ' . $reason,
            'retry_safe' => false,
            'severity' => 'medium',
        ];
    }
}
