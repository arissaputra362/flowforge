<?php

namespace App\Services\AI;

class ResponseValidator
{
    /**
     * Validates if the given parsed JSON array matches the required AI schema.
     */
    public function isValid(?array $data): bool
    {
        if (!is_array($data)) {
            return false;
        }

        $requiredKeys = ['root_cause', 'suggestion', 'retry_safe', 'severity'];

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data)) {
                return false;
            }
        }

        if (!is_bool($data['retry_safe'])) {
            return false;
        }

        $validSeverities = ['low', 'medium', 'high'];
        if (!in_array($data['severity'], $validSeverities)) {
            return false;
        }

        return true;
    }

    /**
     * Tries to parse the AI text response into an array.
     */
    public function parse(string $text): ?array
    {
        // Sometimes LLMs return markdown blocks like ```json ... ```
        $text = trim($text);
        if (str_starts_with($text, '```json')) {
            $text = substr($text, 7);
        }
        if (str_starts_with($text, '```')) {
            $text = substr($text, 3);
        }
        if (str_ends_with($text, '```')) {
            $text = substr($text, 0, -3);
        }

        return json_decode(trim($text), true);
    }
}
