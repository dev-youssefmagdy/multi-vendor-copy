<?php

namespace App\PaymentGateway\DTOs;

class PaymentResult
{
    public function __construct(
        public readonly bool    $success,
        public readonly ?string $redirectUrl    = null,
        public readonly ?string $transactionId  = null,
        public readonly ?string $rawResponse    = null,
        public readonly ?string $errorMessage   = null,
        public readonly bool    $needsRedirect  = false,
        public readonly bool    $needsView      = false,
        public readonly ?string $viewName       = null,
        public readonly array   $viewData       = [],
        public readonly array   $extra          = [],
    ) {}

    /** Gateway wants to redirect the user to an external checkout page. */
    public static function redirect(string $url): self
    {
        return new self(
            success:       true,
            redirectUrl:   $url,
            needsRedirect: true,
        );
    }

    /** Gateway needs to render a local view (e.g. hosted form). */
    public static function view(string $viewName, array $viewData = []): self
    {
        return new self(
            success:    true,
            needsView:  true,
            viewName:   $viewName,
            viewData:   $viewData,
        );
    }

    /** Payment was confirmed successfully (after callback). */
    public static function success(string $transactionId, ?string $rawResponse = null, array $extra = []): self
    {
        return new self(
            success:       true,
            transactionId: $transactionId,
            rawResponse:   $rawResponse,
            extra:         $extra,
        );
    }

    /** Payment failed or was cancelled. */
    public static function failure(string $message, array $extra = []): self
    {
        return new self(
            success:      false,
            errorMessage: $message,
            extra:        $extra,
        );
    }

    public function isRedirect(): bool
    {
        return $this->needsRedirect && $this->redirectUrl !== null;
    }

    public function isView(): bool
    {
        return $this->needsView && $this->viewName !== null;
    }

    public function toResponse(): \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
    {
        if ($this->isRedirect()) {
            return redirect($this->redirectUrl, 303);
        }

        if ($this->isView()) {
            return view($this->viewName, $this->viewData);
        }

        return response()->json([
            'success'        => $this->success,
            'transaction_id' => $this->transactionId,
            'message'        => $this->errorMessage,
        ]);
    }
}
