<?php

declare(strict_types=1);

use App\Models\User;

test('login page loads successfully', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('authenticated user can access dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertStatus(200);
});
