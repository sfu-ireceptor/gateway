<?php

namespace Tests\Feature;

use Tests\TestCase;

class PagesOkTest extends TestCase
{
    /** @test */
    public function login()
    {
        $this->get('/login')->assertSuccessful();
    }

    /** @test */
    public function home()
    {
        $u = factory(\App\User::class)->make();
        $this->actingAs($u)->get('/home')->assertSuccessful();
    }

    /** @test */
    public function samples()
    {
        $u = factory(\App\User::class)->make();
        $this->actingAs($u)->get('/samples')->assertSuccessful();
    }

    /** @test */
    public function sequences()
    {
        $u = factory(\App\User::class)->make();
        // $this->actingAs($u)->followingRedirects()->get('/sequences')->assertSuccessful();
    }
}
