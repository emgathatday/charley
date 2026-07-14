<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PhaseOneRouteScopeFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_one_uses_inline_library_domain_routes_and_keeps_deferred_files_inactive(): void
    {
        $routes = collect(Route::getRoutes())->map(fn ($route): array => ['name' => (string) $route->getName(), 'uri' => $route->uri()]);

        $this->assertTrue($routes->contains(fn ($route): bool => $route['name'] === 'api.v1.library.knowledge-domains.index'));
        $this->assertTrue($routes->contains(fn ($route): bool => $route['name'] === 'api.v1.library.quizzes.index'));
        $this->assertTrue($routes->contains(fn ($route): bool => $route['name'] === 'api.v1.library.domain-points.index'));
        $this->assertTrue($routes->contains(fn ($route): bool => $route['name'] === 'admin.dashboard.library.knowledge-domains.index'));
        $this->assertTrue($routes->contains(fn ($route): bool => $route['name'] === 'admin.dashboard.library.hotspots.index'));

        $this->assertFalse($routes->contains(fn ($route): bool => str_starts_with($route['name'], 'api.v1.handbook.')));
        $this->assertFalse($routes->contains(fn ($route): bool => str_contains($route['uri'], 'dashboard/quiz')));
        $this->assertFalse($routes->contains(fn ($route): bool => str_contains($route['uri'], 'reputation')));
    }
}
