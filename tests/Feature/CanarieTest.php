<?php

namespace Tests\Feature;

use Tests\TestCase;

class CanarieTest extends TestCase
{
    private $prefixes = ['platform', 'auth/service', 'computation/service'];

    /** @test */
    public function test_all_canarie_routes()
    {
        foreach ($this->prefixes as $prefix) {
            // html
            $routes = ['info', 'stats', 'doc', 'releasenotes', 'support', 'source', 'tryme', 'licence', 'provenance', 'factsheet'];
            foreach ($routes as $route) {
                $this->get($prefix . '/' . $route)->assertOk();
            }

            // json
            $routes = ['info', 'stats'];
            foreach ($routes as $route) {
                $this->json('GET', $prefix . '/' . $route)->assertJson([]);
            }
        }
    }
}
