<?php

namespace Tests\Feature;

use Tests\TestCase;

class CanarieTest extends TestCase
{
    private $prefixes = ['platform', 'auth/service', 'computation/service'];

    private $html_routes = ['info', 'stats'];
    private $html_routes_redirect = ['doc', 'releasenotes', 'support', 'source', 'tryme', 'licence', 'provenance', 'factsheet'];
    private $json_routes = ['info', 'stats'];

    /** @test */
    public function test_html_routes()
    {
        foreach ($this->prefixes as $prefix) {
            foreach ($this->html_routes as $route) {
                $this->get($prefix . '/' . $route)->assertOk();
            }
        }
    }

    /** @test */
    public function test_html_routes_redirect()
    {
        foreach ($this->prefixes as $prefix) {
            foreach ($this->html_routes_redirect as $route) {
                $this->get($prefix . '/' . $route)->assertStatus(302);
            }
        }
    }

    /** @test */
    public function test_json_routes()
    {
        foreach ($this->prefixes as $prefix) {
            foreach ($this->json_routes  as $route) {
                $this->json('GET', $prefix . '/' . $route)->assertJson([]);
            }
        }
    }
}
