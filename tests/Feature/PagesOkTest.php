<?php

namespace Tests\Feature;

use Tests\TestCase;

class PagesOkTest extends TestCase
{
    /*
    |--------------------------------------------------------------------------
    | Public
    |--------------------------------------------------------------------------
    */
    /** @test */
    public function login()
    {
        $this->get('/login')->assertOk();
    }

    /** @test */
    public function forgot_password()
    {
        $this->get('/user/forgot-password')->assertOk();
    }

    /** @test */
    public function about()
    {
        $this->get('/about')->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | Require authentication
    |--------------------------------------------------------------------------
    */
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

    /** @test */
    public function sequences_quick_search()
    {
        $u = factory(\App\User::class)->make();
        $this->actingAs($u)->get('/sequences-quick-search')->assertOk();
    }

    /** @test */
    public function bookmarks()
    {
        $u = factory(\App\User::class)->make();
        $this->actingAs($u)->get('/bookmarks')->assertOk();
    }

        /** @test */
    public function systems()
    {
        $u = factory(\App\User::class)->make();
        $this->actingAs($u)->get('/systems')->assertOk();
    }

    /** @test */
    public function jobs()
    {
        $u = factory(\App\User::class)->make();
        $this->actingAs($u)->get('/jobs')->assertOk();
    }

    /** @test */
    // public function account()
    // {
    //     $u = factory(\App\User::class)->make();
    //     $this->actingAs($u)->get('/user/account')->assertOk();
    // }

    /** @test */
    // public function change_personal_info()
    // {
    //     $u = factory(\App\User::class)->make();
    //     $this->actingAs($u)->get('/user/change-personal-info')->assertOk();
    // }

    /** @test */
    public function change_password()
    {
        $u = factory(\App\User::class)->make();
        $this->actingAs($u)->get('/user/change-password')->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | Admin
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function databases()
    {
        $u = factory(\App\User::class)->make();
        $this->actingAs($u)->get('/admin/databases')->assertOk();
    }

    /** @test */
    public function news()
    {
        $u = factory(\App\User::class)->make();
        $this->actingAs($u)->get('/admin/news')->assertOk();
    }

    /** @test */
    public function queues()
    {
        $u = factory(\App\User::class)->make();
        $this->actingAs($u)->get('/admin/queues')->assertOk();
    }

    /** @test */
    // public function users()
    // {
    //     $u = factory(\App\User::class)->make();
    //     $this->actingAs($u)->get('/admin/users')->assertOk();
    // }

    /** @test */
    public function field_names()
    {
        $u = factory(\App\User::class)->make();
        $this->actingAs($u)->get('/admin/field-names')->assertOk();
    }

    /** @test */
    public function queries()
    {
        $u = factory(\App\User::class)->make();
        $this->actingAs($u)->get('/admin/queries')->assertOk();
    }
}
