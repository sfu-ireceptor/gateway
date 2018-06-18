<?php

namespace Tests\Feature;

use Tests\TestCase;

class PagesOkTest extends TestCase
{
    /** @test */
    public function login()
    {
        $this->get('/login')->assertOk();
    }

    /** @test */
    public function home()
    {
        $u = factory(\App\User::class)->make();
        $this->actingAs($u)->get('/home')->assertOk();
    }

    /** @test */
    public function samples()
    {
        $u = factory(\App\User::class)->make();
        $this->actingAs($u)->get('/samples')->assertOk();
    }

    /** @test */
    public function sequences()
    {
        $u = factory(\App\User::class)->make();
        $this->actingAs($u)->followingRedirects()->get('/sequences')->assertOk();
    }
}
