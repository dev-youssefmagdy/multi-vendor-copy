<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiTranslationService
{
    protected array $cache = [];

    protected int $totalTokensUsed = 0;

    public function configured(): bool
    {
        return filled(config('services.openai.api_key'));
    }

    /**
     * Reset the running token-usage counter. Call before a batch of related
     * translateBatch() calls (e.g. at the start of a full-store translation)
     * so totalTokensUsed() reflects just that run.
     */
    public function resetUsage(): void
    {
        $this->totalTokensUsed = 0;
    }

    public function totalTokensUsed(): int
    {
        return $this->totalTokensUsed;
    }

    public function translateBatch(
        array $texts,
        string $sourceLocale,
        string $targetLocale,
        string $targetLanguage,
        string $context,
        ?callable $onChunkTranslated = null,
    ): array {
        $translations = array_fill(0, count($texts), '');
        $pending = [];

        foreach ($texts as $index => $text) {
            $value = trim((string) $text);

            if ($value === '') {
                continue;
            }

            $cacheKey = $this->cacheKey($sourceLocale, $targetLocale, $value);

            if (array_key_exists($cacheKey, $this->cache)) {
                $translations[$index] = $this->cache[$cacheKey];
                continue;
            }

            if (!isset($pending[$cacheKey])) {
                $pending[$cacheKey] = [
                    'text' => $value,
                    'indexes' => [],
                ];
            }

            $pending[$cacheKey]['indexes'][] = $index;
        }
        info('OpenAI translation: ' . count($pending) . ' unique items to translate.');

        $chunks = array_chunk(array_values($pending), 25);
        $totalChunks = count($chunks);

        foreach ($chunks as $chunkNumber => $chunk) {
            $chunkTranslations = $this->requestTranslations(
                array_map(fn(array $item) => $item['text'], $chunk),
                $sourceLocale,
                $targetLocale,
                $targetLanguage,
                $context,
            );

            foreach ($chunk as $chunkIndex => $item) {
                $translated = trim((string) ($chunkTranslations[$chunkIndex] ?? $item['text']));
                $cacheKey = $this->cacheKey($sourceLocale, $targetLocale, $item['text']);

                $this->cache[$cacheKey] = $translated;

                foreach ($item['indexes'] as $index) {
                    $translations[$index] = $translated;
                }
            }

            if ($onChunkTranslated !== null) {
                $onChunkTranslated($chunkNumber + 1, $totalChunks);
            }
        }

        return $translations;
    }

    protected function requestTranslations(
        array $texts,
        string $sourceLocale,
        string $targetLocale,
        string $targetLanguage,
        string $context,
    ): array {
        if (!$this->configured()) {
            throw new RuntimeException('OpenAI translation is not configured.');
        }

        $items = array_map(
            fn(string $text, int $index) => ['index' => $index, 'text' => $text],
            array_values($texts),
            array_keys($texts),
        );

        $response = Http::baseUrl(rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/'))
            ->withToken((string) config('services.openai.api_key'))
            ->acceptJson()
            ->timeout((int) config('services.openai.timeout', 120))
            ->post('/chat/completions', [
                'model' => (string) config('services.openai.translation_model', 'gpt-4.1-mini'),
                'temperature' => 0.1,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => implode("\n", [
                            'You are a professional ecommerce localization expert.',
                            "Translate text from {$sourceLocale} into {$targetLanguage} ({$targetLocale}).",
                            'The context below describes the specific store — adapt your translations to fit its brand voice, product niche, and audience.',
                            'Preserve placeholders exactly, including :name, :count, {name}, %s, HTML tags, URLs, SKUs, and line breaks.',
                            'Return JSON only with the shape {"translations":[{"index":0,"translation":"..."}]}.',
                        ]),
                    ],
                    [
                        'role' => 'user',
                        'content' => json_encode([
                            'context' => $context,
                            'items' => $items,
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ],
                ],
            ])
            ->throw();

        $this->totalTokensUsed += (int) data_get($response->json(), 'usage.total_tokens', 0);

        $content = data_get($response->json(), 'choices.0.message.content');

        if (!is_string($content) || trim($content) === '') {
            throw new RuntimeException('OpenAI translation response was empty.');
        }

        info($content);

        $decoded = json_decode($content, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('OpenAI translation response was not valid JSON.');
        }

        $rows = $decoded['translations'] ?? null;

        if (!is_array($rows)) {
            throw new RuntimeException('OpenAI translation response did not contain a translations array.');
        }

        $translations = array_fill(0, count($texts), '');

        foreach ($rows as $row) {
            $index = is_array($row) ? (int) ($row['index'] ?? -1) : -1;

            if ($index < 0 || $index >= count($texts)) {
                continue;
            }

            $translations[$index] = trim((string) ($row['translation'] ?? ''));
        }

        foreach ($translations as $index => $translation) {
            if ($translation === '') {
                $translations[$index] = (string) ($texts[$index] ?? '');
            }
        }

        return $translations;
    }

    protected function cacheKey(string $sourceLocale, string $targetLocale, string $text): string
    {
        return $sourceLocale . '|' . $targetLocale . '|' . md5($text);
    }
}
