<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Helpers\TenantNavigation;
use App\Models\Tenant\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

class PanelRouteCoverageTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;

    /** Route names whose `.data` endpoint needs a route param we don't generate generically. */
    private const DATA_ROUTE_FIXTURES = [
        'tenant.customers.payments.data' => ['customerId' => null], // filled in setUp
    ];

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

    public function test_every_visible_navigation_link_opens(): void
    {
        $links = $this->collectVisibleNavigationLinks();

        $this->assertNotEmpty($links, 'Expected at least one visible navigation link.');

        foreach ($links as $link) {
            $url = $this->tenantUrl($link['path']);

            $response = $this->actingAsTenantAdmin()->get($url);

            $this->assertContains(
                $response->getStatusCode(),
                [200, 302],
                "Route [{$link['route']}] ({$url}) returned unexpected status {$response->getStatusCode()}."
            );

            if ($response->getStatusCode() === 302) {
                // A redirect is only acceptable when it is the documented
                // "please finish setup / pick a badge" kind of redirect —
                // never a bounce back to login (which would mean auth failed).
                $target = $response->headers->get('Location');
                $this->assertStringNotContainsString(
                    'tenant.login',
                    (string) $target,
                    "Route [{$link['route']}] unexpectedly redirected to login: {$target}"
                );
            }
        }
    }

    public function test_every_data_table_route_returns_the_yajra_shape(): void
    {
        foreach ($this->dataRoutes() as $name => $uri) {
            $response = $this->actingAsTenantAdmin()
                ->get($this->tenantUrl($uri), ['Accept' => 'application/json']);

            $response->assertOk();
            $json = $response->json();

            foreach (['draw', 'recordsTotal', 'recordsFiltered', 'data'] as $key) {
                $this->assertArrayHasKey($key, $json, "Route [{$name}] JSON is missing \"{$key}\". Body: " . $response->getContent());
            }
        }
    }

    public function test_every_export_route_streams_a_csv_with_utf8_bom(): void
    {
        foreach ($this->exportRoutes() as $name => $uri) {
            $response = $this->actingAsTenantAdmin()->get($this->tenantUrl($uri));

            $response->assertOk();
            $contentType = $response->headers->get('Content-Type');
            $this->assertStringContainsString('text/csv', (string) $contentType, "Route [{$name}] content-type was {$contentType}");

            $content = $response->streamedContent();
            $this->assertStringStartsWith("\xEF\xBB\xBF", $content, "Route [{$name}] body does not start with a UTF-8 BOM.");
        }
    }

    /**
     * @return array<int, array{route:string, path:string}>
     */
    private function collectVisibleNavigationLinks(): array
    {
        $links = [];

        tenancy()->initialize($this->tenant);
        auth('tenant')->login($this->admin);

        try {
            foreach (TenantNavigation::visibleSections() as $section) {
                foreach ($section['items'] as $item) {
                    $this->collectItem($item, $links);

                    foreach ($item['children'] ?? [] as $child) {
                        $this->collectItem($child, $links);
                    }
                }
            }
        } finally {
            auth('tenant')->logout();
            tenancy()->end();
        }

        return $links;
    }

    private function collectItem(array $item, array &$links): void
    {
        if (($item['type'] ?? 'link') !== 'link') {
            return;
        }

        if (!Route::has($item['route'])) {
            return;
        }

        $path = route($item['route'], $item['routeParameters'] ?? [], false);

        $links[] = ['route' => $item['route'], 'path' => $path];
    }

    /** @return array<string, string> route name => relative URI */
    private function dataRoutes(): array
    {
        $customer = Customer::query()->first();

        $routes = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if (!$name || !str_starts_with($name, 'tenant.') || !str_ends_with($name, '.data')) {
                continue;
            }

            if (!in_array('GET', $route->methods(), true)) {
                continue;
            }

            $params = $this->fillRouteParameters($route, $customer);

            if ($params === null) {
                continue; // could not satisfy required params — documented skip
            }

            $routes[$name] = $this->buildUri($route, $params);
        }

        return $routes;
    }

    /** @return array<string, string> route name => relative URI */
    private function exportRoutes(): array
    {
        $routes = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if (!$name || !str_starts_with($name, 'tenant.') || !str_ends_with($name, '.export')) {
                continue;
            }

            if (!in_array('GET', $route->methods(), true)) {
                continue;
            }

            $params = $this->fillRouteParameters($route, null);

            if ($params === null) {
                continue;
            }

            $routes[$name] = $this->buildUri($route, $params);
        }

        return $routes;
    }

    /** @return array<string,mixed>|null null when a required param can't be satisfied */
    private function fillRouteParameters($route, ?Customer $customer): ?array
    {
        $params = [];

        foreach ($route->parameterNames() as $paramName) {
            if ($paramName === 'customerId' && $customer) {
                $params[$paramName] = $customer->id;
                continue;
            }

            if (in_array($paramName, ['countryId'], true)) {
                // optional, leave empty
                continue;
            }

            // Any other required param we don't have a fixture for.
            return null;
        }

        return $params;
    }

    private function buildUri($route, array $params): string
    {
        $uri = $route->uri();

        foreach ($params as $key => $value) {
            $uri = str_replace(['{' . $key . '}', '{' . $key . '?}'], (string) $value, $uri);
        }

        // Strip any remaining optional placeholders.
        $uri = preg_replace('/\{[a-zA-Z0-9_]+\?\}/', '', $uri);

        return '/' . ltrim($uri, '/');
    }
}
