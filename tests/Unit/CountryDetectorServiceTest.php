<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\CountryDetectorService;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Visitor-country detection order (client report 2026-11-15: "Sold in countries" looked broken
 * when testing with a VPN, because the first detected country stayed cached for the session).
 */
class CountryDetectorServiceTest extends TestCase
{
    private function request(Store $session, array $server = [], array $query = []): Request
    {
        $request = Request::create('/', 'GET', $query, [], [], $server);
        $request->setLaravelSession($session);

        return $request;
    }

    private function iso(Request $request): ?string
    {
        $method = new \ReflectionMethod(CountryDetectorService::class, 'extractIso2');

        return $method->invoke(new CountryDetectorService(), $request);
    }

    #[Test]
    public function cloudflare_country_is_re_read_on_every_request(): void
    {
        $session = new Store('test', new ArraySessionHandler(10));

        $this->assertSame('MA', $this->iso($this->request($session, ['HTTP_CF_IPCOUNTRY' => 'MA'])));
        // Same session, visitor switches on a VPN in Russia: re-detected immediately.
        $this->assertSame('RU', $this->iso($this->request($session, ['HTTP_CF_IPCOUNTRY' => 'RU'])));
        // No IP header on a later request: the last detection is reused.
        $this->assertSame('RU', $this->iso($this->request($session)));
    }

    #[Test]
    public function query_override_wins_over_ip_for_the_whole_session(): void
    {
        $session = new Store('test', new ArraySessionHandler(10));

        $this->assertSame('EG', $this->iso($this->request($session, ['HTTP_CF_IPCOUNTRY' => 'MA'], ['country' => 'eg'])));
        $this->assertSame('EG', $this->iso($this->request($session, ['HTTP_CF_IPCOUNTRY' => 'MA'])));
    }

    #[Test]
    public function unknown_and_tor_cloudflare_codes_are_ignored(): void
    {
        foreach (['XX', 'T1'] as $code) {
            $session = new Store('test', new ArraySessionHandler(10));
            $this->assertNull($this->iso($this->request($session, ['HTTP_CF_IPCOUNTRY' => $code])));
        }
    }
}
