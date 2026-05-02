<?php

namespace App\Services\Execution;

use Throwable;
use Illuminate\Validation\ValidationException;

class RetryService
{
    /**
     * Determine if an exception should be retried.
     */
    public function isRetryable(Throwable $e): bool
    {
        // Validation exceptions should not be retried
        if ($e instanceof ValidationException) {
            return false;
        }

        // Add additional non-retryable checks here
        return true;
    }
}
