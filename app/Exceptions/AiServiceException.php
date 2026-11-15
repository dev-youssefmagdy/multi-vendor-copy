<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A failure reported by the Python AI service (python-modules), with a stable reason code
 * so the UI can tell the user what actually went wrong instead of a generic "try again".
 *
 * Codes come from python-modules/app.py (_ai_provider_http_error) plus local ones:
 *   ai_quota_exhausted | ai_auth_failed | ai_rate_limited | ai_provider_error
 *   search_unavailable | service_unreachable | no_results | unknown
 */
final class AiServiceException extends RuntimeException
{
    public function __construct(public readonly string $reason, string $technicalMessage = '', ?\Throwable $previous = null)
    {
        parent::__construct($technicalMessage ?: $reason, 0, $previous);
    }

    /** Build from a failed HTTP response of the Python service (detail may be a string or {code, message}). */
    public static function fromResponse(int $status, mixed $detail, string $body = ''): self
    {
        $code = is_array($detail) ? (string) ($detail['code'] ?? '') : '';
        $message = is_array($detail) ? (string) ($detail['message'] ?? '') : (string) ($detail ?? '');

        $reason = $code ?: match (true) {
            $status === 402 => 'ai_quota_exhausted',
            $status === 429 => 'ai_rate_limited',
            str_contains(strtolower($message . $body), 'insufficient_quota') => 'ai_quota_exhausted',
            str_contains($message . $body, 'OPENAI_API_KEY') => 'ai_auth_failed',
            default => 'unknown',
        };

        return new self($reason, $message ?: $body ?: ('HTTP ' . $status));
    }

    /** Translated, non-technical message for the vendor UI. */
    public function userMessage(string $feature = 'generic'): string
    {
        return match ($this->reason) {
            'ai_quota_exhausted' => __('AI features are paused: the platform’s AI account (OpenAI) has no credits left. Please contact support.'),
            'ai_auth_failed' => __('AI features are not configured correctly (the AI API key was rejected). Please contact support.'),
            'ai_rate_limited' => __('The AI service is busy right now. Please try again in a minute.'),
            'search_unavailable' => __('Web price search is temporarily unavailable. Please try again in a few minutes.'),
            'service_unreachable' => __('The AI service is offline. Please contact support.'),
            'no_results' => $feature === 'price'
                ? __('No market prices were found for this product. Try including the product image, or shorten the product name.')
                : __('The AI service returned no posts. Please try again.'),
            default => $feature === 'price'
                ? __('We couldn’t fetch market prices right now. Please try again in a few minutes. If the problem continues, contact support.')
                : __('We couldn’t generate posts right now. Please try again in a few minutes. If the problem continues, contact support.'),
        };
    }
}
