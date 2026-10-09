<?php

declare(strict_types=1);

namespace Tests\Support;

use App\PaymentGateway\Contracts\PaymentGatewayInterface;
use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use App\PaymentGateway\Exceptions\PaymentException;
use App\PaymentGateway\Facades\Payment;
use App\PaymentGateway\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert;
use RuntimeException;
use Throwable;

/**
 * In-memory payment gateway for tests. Install it with
 *
 *     $gateway = FakePaymentGateway::install();            // fakes 'stripe' (the BuildsOrders default)
 *     $gateway = FakePaymentGateway::install('stripe', 'paypal');
 *
 * which swaps the container's PaymentManager for a FakePaymentManager that hands out this
 * instance for the given keys (other keys resolve normally). No DB rows / credentials needed.
 *
 * Configure the refund outcome (persistent until changed):
 *
 *     $gateway->succeed();                  // default — PaymentResult::success('re_fake_…')
 *     $gateway->fail('Insufficient funds'); // PaymentResult::failure(...)
 *     $gateway->notSupported();             // throws PaymentException::notSupported
 *     $gateway->throws(new \RuntimeException('Network down'));
 *     $gateway->advertisesRefunds = false;  // PaymentManager::supportsRefunds() → false (refund created as manual)
 *
 * Inspect: $gateway->refunds (list of calls), assertRefunded(), assertNothingRefunded().
 */
class FakePaymentGateway implements PaymentGatewayInterface
{
    public const SUCCESS = 'success';

    public const FAILURE = 'failure';

    public const NOT_SUPPORTED = 'not_supported';

    public const THROW = 'throw';

    /** Whether PaymentManager::supportsRefunds() reports true for the faked keys. */
    public bool $advertisesRefunds = true;

    /** @var list<array{transaction_id: string, amount: float, currency: string, context: array<string, mixed>, gateway_refund_id: string|null, outcome: string}> */
    public array $refunds = [];

    private string $mode = self::SUCCESS;

    private string $failureMessage = 'The card issuer declined the refund.';

    private ?Throwable $exception = null;

    public function __construct(private string $key = 'stripe') {}

    /** Bind a fake for the given gateway keys and return it. */
    public static function install(string ...$keys): self
    {
        $keys = $keys ?: ['stripe'];
        $gateway = new self($keys[0]);
        $manager = new FakePaymentManager($gateway, $keys);

        app()->instance('payment.manager', $manager);
        app()->instance(PaymentManager::class, $manager);
        Payment::clearResolvedInstance('payment.manager');

        return $gateway;
    }

    public function succeed(): static
    {
        $this->mode = self::SUCCESS;

        return $this;
    }

    public function fail(string $message = 'The card issuer declined the refund.'): static
    {
        $this->mode = self::FAILURE;
        $this->failureMessage = $message;

        return $this;
    }

    public function notSupported(): static
    {
        $this->mode = self::NOT_SUPPORTED;

        return $this;
    }

    public function throws(Throwable $exception): static
    {
        $this->mode = self::THROW;
        $this->exception = $exception;

        return $this;
    }

    public function refund(string $transactionId, float $amount, string $currency, array $context = []): PaymentResult
    {
        $call = [
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'currency' => $currency,
            'context' => $context,
            'gateway_refund_id' => null,
            'outcome' => $this->mode,
        ];

        switch ($this->mode) {
            case self::NOT_SUPPORTED:
                $this->refunds[] = $call;
                throw PaymentException::notSupported($this->key, 'refund');
            case self::THROW:
                $this->refunds[] = $call;
                throw $this->exception ?? new RuntimeException('Fake gateway exception');
            case self::FAILURE:
                $this->refunds[] = $call;

                return PaymentResult::failure($this->failureMessage);
            default:
                $call['gateway_refund_id'] = 're_fake_'.Str::lower(Str::random(12));
                $this->refunds[] = $call;

                return PaymentResult::success($call['gateway_refund_id'], json_encode(['id' => $call['gateway_refund_id'], 'amount' => $amount]));
        }
    }

    /** Successful refund calls. */
    public function successfulRefunds(): array
    {
        return array_values(array_filter($this->refunds, fn (array $call) => $call['outcome'] === self::SUCCESS));
    }

    public function assertRefunded(float $amount, ?string $transactionId = null): void
    {
        $match = array_filter(
            $this->successfulRefunds(),
            fn (array $call) => abs($call['amount'] - $amount) < 0.001
                && ($transactionId === null || $call['transaction_id'] === $transactionId),
        );

        Assert::assertNotEmpty($match, sprintf('No successful fake gateway refund of %.2f%s.', $amount, $transactionId ? " for {$transactionId}" : ''));
    }

    public function assertNothingRefunded(): void
    {
        Assert::assertSame([], $this->successfulRefunds(), 'The fake gateway refunded something.');
    }

    // ─── Unused by refunds, implemented for the contract ──────────────────────

    public function charge(PaymentCharge $charge): PaymentResult
    {
        return PaymentResult::success('ch_fake_'.Str::lower(Str::random(12)));
    }

    public function verify(Request $request): PaymentResult
    {
        return PaymentResult::success((string) $request->input('transaction_id', 'ch_fake'), null, [
            'order_id' => $request->input('order_id'),
        ]);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function createWebhook(): array
    {
        return [];
    }

    public function verifyWebhook(Request $request): bool
    {
        return true;
    }

    public function getSupportedCurrencies(): array
    {
        return ['USD'];
    }

    public function getSupportedCountries(): array
    {
        return [];
    }

    public function getCustomerCountries(): array
    {
        return [];
    }
}
