<?php

use App\Models\Todo;
use App\Models\User;
use Livewire\Livewire;

test('authenticated user can view dashboard and see their todos', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create(['title' => 'Personal Task']);

    $otherUser = User::factory()->create();
    $otherTodo = Todo::factory()->for($otherUser)->create(['title' => 'Secret Task']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Personal Task')
        ->assertDontSee('Secret Task');
});

test('user can create a new todo via livewire', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::todo.todo-manager')
        ->set('title', 'Belajar Docker Proxmox')
        ->set('description', 'Membaca dokumentasi networking proxmox')
        ->set('priority', 'high')
        ->set('due_date', now()->addDay()->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    expect(Todo::where('title', 'Belajar Docker Proxmox')->where('user_id', $user->id)->exists())->toBeTrue();
});

test('user can toggle completion status of their todo', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create(['is_completed' => false]);

    $this->actingAs($user);

    Livewire::test('pages::todo.todo-manager')
        ->call('toggleComplete', $todo->id);

    expect($todo->fresh()->is_completed)->toBeTrue();
});

test('user can edit their todo', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create(['title' => 'Old Title']);

    $this->actingAs($user);

    Livewire::test('pages::todo.todo-manager')
        ->call('edit', $todo->id)
        ->set('title', 'New Updated Title')
        ->call('save')
        ->assertHasNoErrors();

    expect($todo->fresh()->title)->toBe('New Updated Title');
});

test('user can delete their todo', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test('pages::todo.todo-manager')
        ->call('delete', $todo->id);

    expect(Todo::find($todo->id))->toBeNull();
});

test('user cannot edit or delete another user todo', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherTodo = Todo::factory()->for($otherUser)->create();

    $this->actingAs($user);

    Livewire::test('pages::todo.todo-manager')
        ->call('toggleComplete', $otherTodo->id)
        ->assertForbidden();
});
