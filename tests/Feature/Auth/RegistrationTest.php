<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Public self-registration was removed on purpose in fe27e64
 * ("refactor(auth): remove public registration and update auth routes"):
 * the register routes are gone from routes/auth.php and RegisteredUserController
 * was deleted. Users are provisioned by an admin instead.
 *
 * These tests used to assert the Breeze behaviour and had been failing ever
 * since. They now assert the intended behaviour, so that re-introducing public
 * registration by accident fails here rather than shipping silently.
 */
test('the register route is not registered', function () {
    expect(Route::has('register'))->toBeFalse();
});

test('the registration screen is not reachable', function () {
    $this->get('/register')->assertNotFound();
});

test('new users can not self register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertNotFound();
    $this->assertGuest();
    expect(User::where('email', 'test@example.com')->exists())->toBeFalse();
});
