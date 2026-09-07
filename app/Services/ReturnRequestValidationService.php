<?php

namespace App\Services;

use App\Enums\ReturnReason;

/**
 * Client-facing, deterministic pre-check run before a return request is submitted, so the
 * customer gets inline feedback instead of discovering problems only after ReturnRequestService::create()
 * rejects them. This does NOT replace the server-side guard in ReturnRequestService::create() —
 * both layers stay in place.
 */
class ReturnRequestValidationService
{
    public function __construct(
        private readonly ReturnRequestService $returnRequestService,
        private readonly ReturnPolicyService $returnPolicyService,
    ) {
    }

    /**
     * @param array{reason?:string, description?:string, photos?:array, video?:mixed} $data
     * @return string[] error messages, empty when valid
     */
    public function validate(array $data, string $tenantId, string $orderNumber, ?int $productId = null): array
    {
        $errors = [];

        $reason = $data['reason'] ?? '';

        if ($reason === '' || $reason === null) {
            $errors[] = 'Please select a return reason.';
        }

        $reasonCase = ReturnReason::tryFrom((string) $reason);

        if (empty($data['photos'])) {
            $errors[] = 'At least one photo is required as evidence.';
        }

        $policy = $this->returnPolicyService->resolveProductPolicy($tenantId, $productId);

        if ($reasonCase && $this->reasonRequiresVideo($reasonCase, $policy) && empty($data['video'])) {
            $errors[] = 'A video is required as evidence for this return reason.';
        }

        if (!$this->returnRequestService->isWithinReturnWindow($tenantId, $orderNumber, $policy['window_days'])) {
            $errors[] = "This order is outside the {$policy['window_days']}-day return window.";
        }

        $description = $data['description'] ?? '';

        if ($description !== null && $description !== '' && mb_strlen((string) $description) > 2000) {
            $errors[] = 'Description must not exceed 2000 characters.';
        }

        if (isset($policy['is_returnable']) && !$policy['is_returnable']) {
            $errors[] = 'This product is not eligible for return based on its return policy.';
        }

        if ($productId && in_array($productId, $policy['non_returnable_ids'] ?? [], true)) {
            $errors[] = 'This product is not eligible for return.';
        }

        return $errors;
    }

    private function reasonRequiresVideo(ReturnReason $reason, array $policy): bool
    {
        if (!empty($policy['video_required'])) {
            return true;
        }

        if (!empty($policy['video_required_reasons'])) {
            return in_array($reason->value, $policy['video_required_reasons'], true);
        }

        return $reason->requiresVideo();
    }
}
