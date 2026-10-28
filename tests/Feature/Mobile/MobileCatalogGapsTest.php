<?php

declare(strict_types=1);

namespace Tests\Feature\Mobile;

use App\Http\Controllers\Mobile\PageController;
use App\Models\Tenant\Order;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

class MobileCatalogGapsTest extends TestCase
{
    use SetsUpTenantPanel, BuildsOrders;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantPanel();
    }

    protected function tearDown(): void
    {
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    private function h(): array
    {
        return ['X-Tenant' => $this->tenant->id, 'Accept' => 'application/json'];
    }

    public function test_product_detail_has_gallery_related_rating_and_no_url(): void
    {
        $variant = $this->tenant->run(fn () => $this->createOwnProductVariant(stock: 5, price: 50.0));
        $slug = $variant->product->slug;

        $res = $this->getJson("/api/mobile/v1/products/$slug", $this->h())->assertOk();
        $p = $res->json('data.product');
        $this->assertIsArray($p['images']);
        $this->assertNotEmpty($p['images']);
        $this->assertStringStartsWith('http', $p['images'][0]);
        $this->assertIsArray($p['related']);
        $this->assertArrayNotHasKey('url', $p);
        $this->assertArrayHasKey('rating', $p);
        $this->assertSame(0, $p['reviews_summary']['count']);

        $this->getJson("/api/mobile/v1/products/$slug/related", $this->h())->assertOk()->assertJsonStructure(['data']);
        $this->getJson('/api/mobile/v1/products?per_page=5', $this->h())->assertOk()
            ->assertJsonStructure(['data' => ['products' => ['data' => [['rating', 'reviews_summary' => ['average', 'count']]]]]]);
    }

    public function test_page_html_is_sanitized_and_plain_text_kept(): void
    {
        $html = '<p onclick="x()">Hello <b>world</b></p><script>alert(1)</script><a href="javascript:evil()">l</a>';
        $out = PageController::sanitizeHtml($html);
        $this->assertStringNotContainsString('script', $out);
        $this->assertStringNotContainsString('onclick', $out);
        $this->assertStringNotContainsString('javascript:', $out);
        $this->assertStringContainsString('<b>world</b>', $out);
    }

    public function test_order_items_include_id(): void
    {
        $variant = $this->tenant->run(fn () => $this->createOwnProductVariant(stock: 5, price: 50.0));
        $reg = $this->postJson('/api/mobile/v1/auth/register', [
            'name' => 'B', 'email' => 'b@example.com', 'password' => 'password123', 'password_confirmation' => 'password123',
        ], $this->h())->assertCreated();
        $token = $reg->json('data.token');
        $customer = $this->tenant->run(fn () => \App\Models\Tenant\Customer::where('email', 'b@example.com')->first());
        $order = $this->tenant->run(fn () => $this->createOrder($customer, [[$variant, 1]]));
        $this->getJson("/api/mobile/v1/orders/{$order->uuid}", $this->h() + ['Authorization' => "Bearer $token"])
            ->assertOk()->assertJsonPath('data.order.items.0.id', $order->items()->first()->id);
    }
}
