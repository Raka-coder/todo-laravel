<?php

use App\Models\Todo;
use App\Models\User;

test('todo belongs to a user and has expected attributes', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->create([
        'user_id' => $user->id,
        'title' => 'Test Todo',
        'description' => 'Test Description',
        'is_completed' => false,
        'priority' => 'high',
        'due_date' => now()->addDays(2)->toDateString(),
    ]);

    expect($todo->user->id)->toBe($user->id);
    expect($user->todos)->toHaveCount(1);
    expect($todo->title)->toBe('Test Todo');
    expect($todo->priority)->toBe('high');
    expect($todo->is_completed)->toBeFalse();
});
