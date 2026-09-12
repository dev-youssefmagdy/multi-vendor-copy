<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\BrandPaymentRequestStatus;
use App\Enums\BrandRequestStatus;
use App\Events\BrandRequestMessageSent;
use App\Models\BrandPaymentRequest;
use App\Models\BrandRequest;
use App\Models\BrandRequestMessage;
use Illuminate\Http\UploadedFile;

final class BrandRequestService
{
    /**
     * @param  UploadedFile[]  $files
     */
    public function create(string $title, string $description, array $files): BrandRequest
    {
        $tenantId = tenant('id');

        $storedPaths = [];
        foreach ($files as $file) {
            $storedPaths[] = $file->store("brand-requests/{$tenantId}", 'public');
        }

        return BrandRequest::create([
            'tenant_id' => $tenantId,
            'title' => $title,
            'description' => $description,
            'attachments' => $storedPaths ?: null,
            'status' => BrandRequestStatus::Pending->value,
        ]);
    }

    public function sendMessage(BrandRequest $brandRequest, string $senderName, string $body): BrandRequestMessage
    {
        $body = trim($body);

        $message = BrandRequestMessage::create([
            'brand_request_id' => $brandRequest->id,
            'sender_type' => 'tenant',
            'sender_name' => $senderName,
            'message' => $body,
        ]);

        try {
            event(new BrandRequestMessageSent(
                requestId: $brandRequest->id,
                tenantId: tenant('id'),
                senderType: 'tenant',
                senderName: $senderName,
                body: $body,
                sentAt: now()->toIso8601String(),
            ));
        } catch (\Throwable) {
        }

        return $message;
    }

    public function findPendingPaymentRequest(BrandRequest $brandRequest, int $paymentRequestId): BrandPaymentRequest
    {
        return BrandPaymentRequest::where('tenant_id', tenant('id'))
            ->where('brand_request_id', $brandRequest->id)
            ->where('status', BrandPaymentRequestStatus::Pending->value)
            ->findOrFail($paymentRequestId);
    }
}
