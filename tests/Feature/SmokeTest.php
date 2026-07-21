<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Smoke test — verifies the application boots without errors.
 * The real feature tests live in Auth/, Admin/, Frontend/ subdirectories
 * built during Phase 1+ of the plan.
 */
use Illuminate\Foundation\Testing\RefreshDatabase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;
    /**
     * The application should boot and return a response (any response).
     * We assert < 500 to confirm no unhandled exception on boot.
     */
    public function test_application_boots_without_error(): void
    {
        $response = $this->get('/');

        // 200, 302 (redirect to login), or 404 are all fine at this stage.
        // What we do NOT want is a 500 server error.
        $this->assertLessThan(500, $response->status(), 'Application threw an unhandled 500 error on boot.');
    }
}
