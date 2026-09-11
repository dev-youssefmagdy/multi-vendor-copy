<?php

declare(strict_types=1);

namespace App\Exceptions\Tenant;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class PanelActionException extends RuntimeException implements Responsable
{
    public function __construct(
        string $message,
        private readonly int $status = 422,
        private readonly ?string $redirect = null,
        private readonly string $toastType = 'error',
    ) {
        parent::__construct($message);
    }

    public function toResponse($request): JsonResponse|RedirectResponse
    {
        /** @var Request $request */
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $this->getMessage(),
                'redirect' => $this->redirect,
                'toast_type' => $this->toastType,
            ], $this->status);
        }

        return back()
            ->with('status', $this->getMessage())
            ->with('status_type', $this->toastType);
    }
}
