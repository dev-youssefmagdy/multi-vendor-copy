<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiTranslationService
{
    protected int $totalTokensUsed = 0;

    public function configured(): bool
    {
        return filled(config('services.openai.api_key'));
    }

    /**
     * Reset the running token-usage counter. Call before a batch of related
     * translation calls (e.g. at the start of a full-store translation) so
     * totalTokensUsed() reflects just that run.
     */
    public function resetUsage(): void
    {
        $this->totalTokensUsed = 0;
    }

    public function totalTokensUsed(): int
    {
        return $this->totalTokensUsed;
    }

    /**
     * Drop-in replacement for the old flat translateBatch(), built on translateStructuredBatch().
     * $pending is a flat list of ['group' => mixed, 'field' => string, 'text' => string, ...],
     * e.g. one entry per translatable field. Entries sharing the same 'group'
     * are combined into a single {id, translations} record, records are sent
     * $chunkSize at a time (one request per chunk, ids/keys mirrored back by
     * OpenAI), and the result is returned keyed by the original $pending
     * offset => translated text, so callers can index it exactly like
     * translateBatch()'s return value.
     */
    public function translateGroupedPending(
        array $pending,
        string $sourceLocale,
        string $targetLocale,
        string $targetLanguage,
        string $context,
        int $chunkSize = 100,
    ): array {
        if ($pending === []) {
            return [];
        }

        $groups = [];

        foreach ($pending as $offset => $item) {
            $groups[$item['group']]['translations'][$item['field']] = (string) $item['text'];
            $groups[$item['group']]['offsets'][$item['field']][] = $offset;
        }

        $items = [];

        foreach ($groups as $groupId => $group) {
            $items[] = ['id' => $groupId, 'translations' => $group['translations']];
        }

        $result = [];

        foreach (array_chunk($items, $chunkSize) as $chunk) {
            $translated = $this->translateStructuredBatch($chunk, $sourceLocale, $targetLocale, $targetLanguage, $context);

            foreach ($chunk as $item) {
                $groupId = $item['id'];
                $translatedFields = $translated[$groupId] ?? $item['translations'];

                foreach ($groups[$groupId]['offsets'] as $field => $offsets) {
                    $value = trim((string) ($translatedFields[$field] ?? $item['translations'][$field]));

                    foreach ($offsets as $offset) {
                        $result[$offset] = $value;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Translate one already-chunked array of records in a single request, each
     * shaped like ['id' => 1, 'translations' => ['name' => '...', ...]] — the
     * same shape Product::getTranslatedKeys() produces. The whole array is
     * json-encoded once and sent as-is; OpenAI must echo back the identical
     * array (same ids, same translation keys) with values translated.
     * Returns the array keyed by id => field => translated value.
     */
    public function translateStructuredBatch(
        array $items,
        string $sourceLocale,
        string $targetLocale,
        string $targetLanguage,
        string $context,
    ): array {
        if ($items === []) {
            return [];
        }

        if (!$this->configured()) {
            throw new RuntimeException('OpenAI translation is not configured.');
        }

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
                            'You will receive a JSON array of records, each with an "id" and a "translations" object mapping field name to source text.',
                            'Return the exact same array back — same ids, same "translations" keys, same number of records — but with every value inside "translations" translated. Do not add, remove, or rename any id or field key.',
                            'Return JSON only with the shape {"items":[{"id":1,"translations":{"name":"...","description":"..."}}]}.',
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

        if (!is_array($decoded) || !is_array($decoded['items'] ?? null)) {
            throw new RuntimeException('OpenAI translation response did not contain an items array.');
        }

        $sourceById = collect($items)->keyBy('id');
        $result = [];

        foreach ($decoded['items'] as $row) {
            if (!is_array($row) || !array_key_exists('id', $row)) {
                continue;
            }

            $id = $row['id'];
            $sourceTranslations = $sourceById->get($id)['translations'] ?? null;

            if (!is_array($sourceTranslations)) {
                continue;
            }

            $translations = is_array($row['translations'] ?? null) ? $row['translations'] : [];
            $translatedFields = [];

            foreach ($sourceTranslations as $field => $sourceValue) {
                $translatedFields[$field] = trim((string) ($translations[$field] ?? '')) !== ''
                    ? trim((string) $translations[$field])
                    : (string) $sourceValue;
            }

            $result[$id] = $translatedFields;
        }

        // Make sure every requested id is represented, falling back to the
        // original source values if OpenAI dropped a record from its reply.
        foreach ($sourceById as $id => $item) {
            $result[$id] ??= $item['translations'];
        }

        return $result;
    }
}
