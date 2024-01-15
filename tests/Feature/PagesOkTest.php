<?php

namespace Tests\Feature;

use App\User;
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
        $u = User::factory()->make();
        $this->actingAs($u)->get('/home')->assertOk();
    }

    /** @test */
    public function samples()
    {
        $u = User::factory()->make();
        $this->actingAs($u)->get('/samples')->assertOk();
    }

    /** @test */
    public function sequences()
    {
        $u = User::factory()->make();
        $this->actingAs($u)->followingRedirects()->get('/sequences')->assertOk();
    }

    /** @test */
    public function sequences_quick_search()
    {
        $u = User::factory()->make();
        $this->actingAs($u)->get('/sequences-quick-search')->assertOk();
    }

    /** @test */
    public function bookmarks()
    {
        $u = User::factory()->make();
        $this->actingAs($u)->get('/bookmarks')->assertOk();
    }

    /** @test */
    public function systems()
    {
        $u = User::factory()->make();
        $this->actingAs($u)->get('/systems')->assertOk();
    }

    /** @test */
    public function jobs()
    {
        $u = User::factory()->make();
        $this->actingAs($u)->get('/jobs')->assertOk();
    }

    /** @test */
    public function downloads()
    {
        $u = User::factory()->make();
        $this->actingAs($u)->get('/downloads')->assertOk();
    }

    /** @test */
    // public function account()
    // {
    //     $u = User::factory()->make();
    //     $this->actingAs($u)->get('/user/account')->assertOk();
    // }

    /** @test */
    // public function change_personal_info()
    // {
    //     $u = User::factory()->make();
    //     $this->actingAs($u)->get('/user/change-personal-info')->assertOk();
    // }

    /** @test */
    public function change_password()
    {
        $u = User::factory()->make();
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
        $u = User::factory()->make();
        $this->actingAs($u)->get('/admin/databases')->assertStatus(401);
    }

    /** @test */
    public function news()
    {
        $u = User::factory()->make();
        $this->actingAs($u)->get('/admin/news')->assertStatus(401);
    }

    /** @test */
    public function queues()
    {
        $u = User::factory()->make();
        $this->actingAs($u)->get('/admin/queues')->assertStatus(401);
    }

    /** @test */
    // public function users()
    // {
    //     $u = User::factory()->make();
    //     $this->actingAs($u)->get('/admin/users')->assertStatus(401);
    // }

    /** @test */
    public function field_names()
    {
        $u = User::factory()->make();
        $this->actingAs($u)->get('/admin/field-names')->assertStatus(401);
    }

    /** @test */
    public function queries()
    {
        $u = User::factory()->make();
        $this->actingAs($u)->get('/admin/queries')->assertStatus(401);
    }
}
