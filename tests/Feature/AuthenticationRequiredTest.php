<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthenticationRequiredTest extends TestCase
{
    /**
     * Check redirect '/' -> '/home'.
     */
    public function testRedirectRootToHome()
    {
        $response = $this->get('/');
        $response->assertRedirect('/home');
    }

    /**
     * Check redirect to login page -> '/login'.
     */
    public function testRedirectToLogin()
    {
        $l = [];

        $l[] = '/home';
        $l[] = '/samples';
        $l[] = '/sequences';

        $l[] = '/samples/json';
        $l[] = '/sequences-quick-search';

        $l[] = '/user/account';
        $l[] = '/user/change-password';
        $l[] = '/user/change-personal-info';

        $l[] = '/bookmarks';

        $l[] = '/systems';
        $l[] = '/systems/delete/1';

        $l[] = '/jobs';
        $l[] = '/jobs/job-data/1';
        $l[] = '/jobs/job-list-grouped-by-month';
        $l[] = '/jobs/view/1';
        $l[] = '/jobs/agave-history/1';
        $l[] = '/jobs/status/1';
        $l[] = '/jobs/delete/1';

        $l[] = '/admin/queues';
        $l[] = '/admin/databases';
        $l[] = '/admin/users';
        $l[] = '/admin/add-user';
        $l[] = '/admin/edit-user/raul_endymion';
        $l[] = '/admin/delete-user/raul_endymion';
        $l[] = '/admin/samples/update-cache';
        $l[] = '/admin/field-names';
        $l[] = '/admin/queries';
        $l[] = '/admin/queries/all';
        $l[] = '/admin/queries/1';

        foreach ($l as $k => $v) {
            $response = $this->get($v);
            $response->assertRedirect('/login');
        }
    }
}
