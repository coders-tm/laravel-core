<?php

namespace Tests\Feature;

use Coderstm\Http\Middleware\ResolveIpAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;
use Tests\TestCase;

class ResolveIpAddressMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Define a test route using the middleware
        Route::middleware(ResolveIpAddress::class)->get('/_test/ip-resolution', function () {
            return response()->json([
                'ip_location' => request()->attributes->get('ip_location'),
            ]);
        });
    }

    #[Test]
    public function it_resolves_and_caches_ip_location()
    {
        $ip = '8.8.8.8';
        $position = new Position;
        $position->ip = $ip;
        $position->countryCode = 'US';
        $position->countryName = 'United States';

        // Mock Location service
        Location::shouldReceive('get')
            ->once()
            ->with($ip)
            ->andReturn($position);

        // First request: should call Location service and cache it
        $this->getJson('/_test/ip-resolution', ['REMOTE_ADDR' => $ip])
            ->assertOk()
            ->assertJson([
                'ip_location' => [
                    'ip' => $ip,
                    'countryCode' => 'US',
                ],
            ]);

        // Verify it is in cache
        $this->assertTrue(Cache::has("location.{$ip}"));
        $cachedLocation = Cache::get("location.{$ip}");
        $this->assertEquals('US', $cachedLocation->countryCode);

        // Second request: should use cache (Location service strict 'once' expectation ensures this)
        $this->getJson('/_test/ip-resolution', ['REMOTE_ADDR' => $ip])
            ->assertOk()
            ->assertJson([
                'ip_location' => [
                    'countryCode' => 'US',
                ],
            ]);

        // Verify macro usage
        $request = Request::create('/_test/ip-resolution', 'GET', [], [], [], ['REMOTE_ADDR' => $ip]);
        // Manually run middleware logic or rely on the previous functional tests
        // Since functional test confirms middleware sets logic, let's just test the macro on a request where we manually inject attribute for unit purpose,
        // OR rely on functional test if we can access request from response.
        // Let's create a unit test for macro.

        $request = new Request;
        $request->attributes->set('ip_location', (object) ['countryCode' => 'US']);
        $this->assertEquals('US', $request->ipLocation('countryCode'));
        $this->assertEquals('Default', $request->ipLocation('invalid', 'Default'));
    }

    #[Test]
    public function it_resolves_ip_from_cloudflare_header()
    {
        $ip = '1.1.1.1';
        $position = new Position;
        $position->countryCode = 'AU';

        Location::shouldReceive('get')
            ->with($ip)
            ->andReturn($position);

        $this->getJson('/_test/ip-resolution', ['CF-Connecting-IP' => $ip])
            ->assertOk()
            ->assertJson([
                'ip_location' => [
                    'countryCode' => 'AU',
                ],
            ]);
    }
}
