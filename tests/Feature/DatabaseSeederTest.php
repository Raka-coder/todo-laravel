<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;

test('database seeder seeds demo user and initial todos', function () {
    $this->seed(DatabaseSeeder::class);

    $demoUser = User::where('email', 'demo@example.com')->first();
    expect($demoUser)->not->toBeNull();
    expect($demoUser->name)->toBe('Demo User');
    expect($demoUser->todos)->not->toBeEmpty();
    expect($demoUser->todos->count())->toBeGreaterThanOrEqual(6);
});
