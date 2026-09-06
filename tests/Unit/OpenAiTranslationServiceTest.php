<?php

namespace Tests\Unit;

use App\Services\OpenAiTranslationService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiTranslationServiceTest extends TestCase
{
    public function test_translate_structured_batch_sends_the_whole_chunk_in_one_request_and_mirrors_ids(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        $requests = [];

        Http::fake(function (Request $request) use (&$requests) {
            $requests[] = $request;
            $content = data_get($request->data(), 'messages.1.content');
            $decoded = json_decode((string) $content, true);
            $items = $decoded['items'] ?? [];

            return Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'items' => array_map(
                                fn(array $item) => [
                                    'id' => $item['id'],
                                    'translations' => array_map(
                                        fn($value) => '[fr] ' . $value,
                                        $item['translations'],
                                    ),
                                ],
                                $items,
                            ),
                        ]),
                    ],
                ]],
            ], 200);
        });

        $service = app(OpenAiTranslationService::class);

        $result = $service->translateStructuredBatch(
            [
                ['id' => 1, 'translations' => ['name' => 'Phone', 'description' => 'A phone']],
                ['id' => 2, 'translations' => ['name' => 'Laptop']],
            ],
            'en',
            'fr',
            'French',
            'Test context',
        );

        // Exactly one HTTP request for the whole chunk.
        $this->assertCount(1, $requests);

        $this->assertSame([
            1 => ['name' => '[fr] Phone', 'description' => '[fr] A phone'],
            2 => ['name' => '[fr] Laptop'],
        ], $result);
    }

    public function test_translate_structured_batch_falls_back_to_source_when_id_missing_from_reply(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake(function () {
            return Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'items' => [
                                ['id' => 1, 'translations' => ['name' => '[fr] Phone']],
                            ],
                        ]),
                    ],
                ]],
            ], 200);
        });

        $service = app(OpenAiTranslationService::class);

        $result = $service->translateStructuredBatch(
            [
                ['id' => 1, 'translations' => ['name' => 'Phone']],
                ['id' => 2, 'translations' => ['name' => 'Laptop']],
            ],
            'en',
            'fr',
            'French',
            'Test context',
        );

        $this->assertSame(['name' => '[fr] Phone'], $result[1]);
        // id 2 was dropped by the model — falls back to the original source values.
        $this->assertSame(['name' => 'Laptop'], $result[2]);
    }

    public function test_translate_grouped_pending_groups_by_id_and_sends_one_request_per_chunk(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        $requests = [];

        Http::fake(function (Request $request) use (&$requests) {
            $requests[] = $request;
            $content = data_get($request->data(), 'messages.1.content');
            $decoded = json_decode((string) $content, true);
            $items = $decoded['items'] ?? [];

            return Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'items' => array_map(
                                fn(array $item) => [
                                    'id' => $item['id'],
                                    'translations' => array_map(
                                        fn($value) => '[fr] ' . $value,
                                        $item['translations'],
                                    ),
                                ],
                                $items,
                            ),
                        ]),
                    ],
                ]],
            ], 200);
        });

        $service = app(OpenAiTranslationService::class);

        $pending = [
            ['group' => 10, 'field' => 'name', 'text' => 'Phone'],
            ['group' => 10, 'field' => 'description', 'text' => 'A phone'],
            ['group' => 20, 'field' => 'name', 'text' => 'Laptop'],
        ];

        $result = $service->translateGroupedPending($pending, 'en', 'fr', 'French', 'Test context');

        $this->assertCount(1, $requests);
        $this->assertSame([
            0 => '[fr] Phone',
            1 => '[fr] A phone',
            2 => '[fr] Laptop',
        ], $result);
    }

    public function test_translate_grouped_pending_respects_chunk_size(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        $requestCount = 0;

        Http::fake(function (Request $request) use (&$requestCount) {
            $requestCount++;
            $content = data_get($request->data(), 'messages.1.content');
            $decoded = json_decode((string) $content, true);
            $items = $decoded['items'] ?? [];

            return Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'items' => array_map(
                                fn(array $item) => ['id' => $item['id'], 'translations' => $item['translations']],
                                $items,
                            ),
                        ]),
                    ],
                ]],
            ], 200);
        });

        $service = app(OpenAiTranslationService::class);

        $pending = [];
        for ($i = 0; $i < 5; $i++) {
            $pending[] = ['group' => $i, 'field' => 'name', 'text' => "Item {$i}"];
        }

        $service->translateGroupedPending($pending, 'en', 'fr', 'French', 'Test context', chunkSize: 2);

        // 5 groups at chunkSize=2 => 3 requests (2, 2, 1).
        $this->assertSame(3, $requestCount);
    }
}
