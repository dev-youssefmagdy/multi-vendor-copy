<?php

namespace App\Livewire\Tenant\Storefront;

use App\Models\Tenant\AdminUser;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\Mail\TemplateMailService;
use Livewire\Component;
use Throwable;

/**
 * "Contact support" form shown at the bottom of the storefront Contact Us page.
 * Emails the store's first admin (the owner); replies go straight to the customer.
 */
class SupportRequestForm extends Component
{
    public const TYPES = [
        'order' => 'Order issue',
        'payment' => 'Payment',
        'shipping' => 'Shipping & delivery',
        'returns' => 'Returns & refunds',
        'product' => 'Product question',
        'other' => 'Other',
    ];

    public string $type = 'order';
    public string $name = '';
    public string $email = '';
    public string $message = '';
    public bool $sent = false;

    public function mount(): void
    {
        $customer = auth('storefront')->user();
        if ($customer) {
            $this->name = (string) ($customer->name ?? '');
            $this->email = (string) ($customer->email ?? '');
        }
    }

    public function send(): void
    {
        $data = $this->validate([
            'type' => ['required', 'in:' . implode(',', array_keys(self::TYPES))],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $recipient = AdminUser::query()->whereNotNull('email')->orderBy('id')->value('email');
        if (blank($recipient)) {
            $this->addError('message', __('Support is not available right now. Please try again later.'));

            return;
        }

        $storeName = app(StorefrontRepository::class)->storeName();
        $typeLabel = __(self::TYPES[$data['type']]);

        $body = '<h2>' . e(__('New support request')) . '</h2>'
            . '<p><strong>' . e(__('Support type')) . ':</strong> ' . e($typeLabel) . '</p>'
            . '<p><strong>' . e(__('Name')) . ':</strong> ' . e($data['name']) . '</p>'
            . '<p><strong>' . e(__('Email')) . ':</strong> ' . e($data['email']) . '</p>'
            . '<p><strong>' . e(__('Message')) . ':</strong></p>'
            . '<p>' . nl2br(e($data['message'])) . '</p>';

        try {
            // Through the store's own mail settings (panel → Mail Configurations), then central.
            app(TemplateMailService::class)->sendTenantMessage(
                $recipient,
                trim("{$storeName} — {$typeLabel}", ' —'),
                $body,
                $data['email'],
                $data['name'],
            );
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('message', __('Unable to send your message right now. Please try again later.'));

            return;
        }

        $this->reset(['message']);
        $this->resetErrorBag();
        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.tenant.storefront.support-request-form', ['types' => self::TYPES]);
    }
}
