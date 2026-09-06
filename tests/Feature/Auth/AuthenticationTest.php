<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

/**
 * Login redirects by role, not to a single dashboard: see
 * AuthService::getRedirectPath(), which AuthenticatedSessionController::store()
 * calls. bootstrap/app.php and the /dashboard closure in routes/web.php agree.
 *
 * This test previously carried the stock Breeze expectation of /dashboard and
 * had been failing ever since the role-based redirect was introduced. Both
 * branches of the match are covered now; only the default arm is unreachable
 * through login, since users.role is a non-null column.
 */
test('an employee is redirected to the POS after logging in', function () {
    $user = User::factory()->create(['role' => 'employee']);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/pos/open');
});

test('an admin is redirected to the admin area after logging in', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/admin');
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
