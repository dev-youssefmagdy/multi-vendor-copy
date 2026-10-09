<?php

namespace App\Services;

/**
 * Client-facing, deterministic pre-check run before a return request is submitted, so the
 * customer gets inline feedback instead of discovering problems only after ReturnRequestService::create()
 * rejects them. Both run the very same rules (ReturnRequestService::creationErrors()), so they
 * can't drift apart; this does NOT replace the server-side guard in create().
 */
class ReturnRequestValidationService
{
    public function __construct(
        private readonly ReturnRequestService $returnRequestService,
    ) {}

    /**
     * @param  array{reason?:string, description?:string, photos?:array, video?:mixed, order_item_id?:int|null, quantity?:int|null, type?:string|null, return_method?:string|null, customer_note?:string|null, replacement_product_variant_id?:int|null, customer_id?:int|null}  $data
     * @param  int|null  $productId  legacy way to identify the order line when order_item_id is missing
     * @return string[] error messages, empty when valid
     */
    public function validate(array $data, string $tenantId, string $orderNumber, ?int $productId = null): array
    {
        $photos = $data['photos'] ?? [];
        $video = $data['video'] ?? null;

        unset($data['photos'], $data['video']);

        return $this->returnRequestService->creationErrors(
            array_merge($data, [
                'tenant_id' => $tenantId,
                'order_number' => $orderNumber,
                'product_id' => $data['product_id'] ?? $productId,
            ]),
            is_countable($photos) ? count($photos) : (empty($photos) ? 0 : 1),
            is_countable($video) ? count($video) : (empty($video) ? 0 : 1),
        );
    }
}
