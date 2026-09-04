<?php

use App\Models\Todo;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    public string $search = '';

    public string $filterStatus = 'all'; // all, active, completed

    public string $filterPriority = 'all'; // all, low, medium, high

    public ?int $editingTodoId = null;

    #[Validate('required|string|min:3|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    #[Validate('required|in:low,medium,high')]
    public string $priority = 'medium';

    #[Validate('nullable|date')]
    public ?string $due_date = null;

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public ?int $todoToDeleteId = null;

    public function openCreateModal(): void
    {
        $this->reset(['editingTodoId', 'title', 'description', 'due_date']);
        $this->priority = 'medium';
        $this->resetErrorBag();
        $this->showFormModal = true;
    }

    public function edit(int $id): void
    {
        $todo = Auth::user()->todos()->findOrFail($id);
        $this->editingTodoId = $todo->id;
        $this->title = $todo->title;
        $this->description = $todo->description ?? '';
        $this->priority = $todo->priority;
        $this->due_date = $todo->due_date ? $todo->due_date->format('Y-m-d') : null;
        $this->resetErrorBag();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingTodoId) {
            $todo = Auth::user()->todos()->findOrFail($this->editingTodoId);
            $todo->update([
                'title' => $this->title,
                'description' => $this->description ?: null,
                'priority' => $this->priority,
                'due_date' => $this->due_date ?: null,
            ]);
            Flux::toast(variant: 'success', text: __('Todo berhasil diperbarui.'));
        } else {
            Auth::user()->todos()->create([
                'title' => $this->title,
                'description' => $this->description ?: null,
                'priority' => $this->priority,
                'due_date' => $this->due_date ?: null,
                'is_completed' => false,
            ]);
            Flux::toast(variant: 'success', text: __('Todo baru berhasil ditambahkan.'));
        }

        $this->showFormModal = false;
        $this->reset(['editingTodoId', 'title', 'description', 'due_date']);
        $this->priority = 'medium';
    }

    public function toggleComplete(int $id): void
    {
        $todo = Auth::user()->todos()->find($id);

        if (! $todo) {
            abort(403);
        }

        $todo->update([
            'is_completed' => ! $todo->is_completed,
        ]);
    }

    public function confirmDelete(int $id): void
    {
        $todo = Auth::user()->todos()->findOrFail($id);
        $this->todoToDeleteId = $todo->id;
        $this->showDeleteModal = true;
    }

    public function delete(?int $id = null): void
    {
        $targetId = $id ?? $this->todoToDeleteId;
        $todo = Auth::user()->todos()->find($targetId);

        if (! $todo) {
            abort(403);
        }

        $todo->delete();
        $this->showDeleteModal = false;
        $this->todoToDeleteId = null;
        Flux::toast(variant: 'success', text: __('Todo berhasil dihapus.'));
    }

    public function with(): array
    {
        $query = Auth::user()->todos()->latest();

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->filterStatus === 'active') {
            $query->where('is_completed', false);
        } elseif ($this->filterStatus === 'completed') {
            $query->where('is_completed', true);
        }

        if ($this->filterPriority !== 'all') {
            $query->where('priority', $this->filterPriority);
        }

        $allTodos = Auth::user()->todos;

        return [
            'todos' => $query->get(),
            'totalCount' => $allTodos->count(),
            'completedCount' => $allTodos->where('is_completed', true)->count(),
            'pendingCount' => $allTodos->where('is_completed', false)->count(),
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Header & Statistics -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('My Todo List') }}</flux:heading>
            <flux:subheading>{{ __('Kelola rencana tugas, prioritas, dan progress belajar cloud computing Anda.') }}</flux:subheading>
        </div>
        <div>
            <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                {{ __('Tambah Todo') }}
            </flux:button>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-4 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">
            <div class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Total Tugas') }}</div>
            <div class="text-2xl font-bold mt-1 text-neutral-900 dark:text-white">{{ $totalCount }}</div>
        </div>
        <div class="p-4 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">
            <div class="text-sm font-medium text-amber-600 dark:text-amber-400">{{ __('Tertunda / Aktif') }}</div>
            <div class="text-2xl font-bold mt-1 text-amber-700 dark:text-amber-300">{{ $pendingCount }}</div>
        </div>
        <div class="p-4 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">
            <div class="text-sm font-medium text-emerald-600 dark:text-emerald-400">{{ __('Selesai') }}</div>
            <div class="text-2xl font-bold mt-1 text-emerald-700 dark:text-emerald-300">{{ $completedCount }}</div>
        </div>
    </div>

    <!-- Filter & Search Controls -->
    <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Cari judul atau catatan...') }}" icon="magnifying-glass" clearable />
        </div>
        <div class="flex flex-wrap gap-2 items-center">
            <div class="flex items-center gap-1 bg-neutral-100 dark:bg-neutral-800 p-1 rounded-lg">
                <button type="button" wire:click="$set('filterStatus', 'all')" class="px-3 py-1 text-xs font-medium rounded-md transition-colors {{ $filterStatus === 'all' ? 'bg-white dark:bg-neutral-700 shadow-sm text-neutral-900 dark:text-white' : 'text-neutral-500 hover:text-neutral-900 dark:hover:text-white' }}">
                    {{ __('Semua') }}
                </button>
                <button type="button" wire:click="$set('filterStatus', 'active')" class="px-3 py-1 text-xs font-medium rounded-md transition-colors {{ $filterStatus === 'active' ? 'bg-white dark:bg-neutral-700 shadow-sm text-neutral-900 dark:text-white' : 'text-neutral-500 hover:text-neutral-900 dark:hover:text-white' }}">
                    {{ __('Aktif') }}
                </button>
                <button type="button" wire:click="$set('filterStatus', 'completed')" class="px-3 py-1 text-xs font-medium rounded-md transition-colors {{ $filterStatus === 'completed' ? 'bg-white dark:bg-neutral-700 shadow-sm text-neutral-900 dark:text-white' : 'text-neutral-500 hover:text-neutral-900 dark:hover:text-white' }}">
                    {{ __('Selesai') }}
                </button>
            </div>

            <select wire:model.live="filterPriority" class="text-xs rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 px-3 py-1.5 focus:ring-1 focus:ring-primary-500">
                <option value="all">{{ __('Semua Prioritas') }}</option>
                <option value="low">{{ __('Low Priority') }}</option>
                <option value="medium">{{ __('Medium Priority') }}</option>
                <option value="high">{{ __('High Priority') }}</option>
            </select>
        </div>
    </div>

    <!-- Todo List Items -->
    <div class="space-y-3">
        @forelse ($todos as $todo)
            <div class="flex items-start justify-between p-4 rounded-xl border {{ $todo->is_completed ? 'border-neutral-200 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-900/30 opacity-75' : 'border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800' }} transition-all">
                <div class="flex items-start gap-3 flex-1 min-w-0">
                    <button type="button" wire:click="toggleComplete({{ $todo->id }})" class="mt-0.5 text-neutral-400 hover:text-emerald-500 focus:outline-none">
                        @if ($todo->is_completed)
                            <flux:icon name="check-circle" class="size-6 text-emerald-500" variant="solid" />
                        @else
                            <div class="size-5 rounded-full border-2 border-neutral-300 dark:border-neutral-600 hover:border-emerald-500"></div>
                        @endif
                    </button>

                    <div class="flex-1 min-w-0 pr-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-base font-medium text-neutral-900 dark:text-white {{ $todo->is_completed ? 'line-through text-neutral-400 dark:text-neutral-500' : '' }}">
                                {{ $todo->title }}
                            </h3>

                            <!-- Priority Badge -->
                            @if ($todo->priority === 'high')
                                <flux:badge color="red" size="sm">{{ __('High') }}</flux:badge>
                            @elseif ($todo->priority === 'medium')
                                <flux:badge color="amber" size="sm">{{ __('Medium') }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ __('Low') }}</flux:badge>
                            @endif

                            <!-- Due Date Badge -->
                            @if ($todo->due_date)
                                <div class="flex items-center gap-1 text-xs {{ $todo->due_date->isPast() && !$todo->is_completed ? 'text-red-500 font-semibold' : 'text-neutral-500 dark:text-neutral-400' }}">
                                    <flux:icon name="calendar" class="size-3.5" />
                                    <span>{{ $todo->due_date->translatedFormat('d M Y') }}</span>
                                    @if ($todo->due_date->isPast() && !$todo->is_completed)
                                        <span>({{ __('Overdue') }})</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        @if ($todo->description)
                            <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400 whitespace-pre-line {{ $todo->is_completed ? 'line-through opacity-75' : '' }}">
                                {{ $todo->description }}
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Actions Menu / Buttons -->
                <div class="flex items-center gap-1">
                    <flux:button variant="ghost" size="sm" icon="pencil-square" wire:click="edit({{ $todo->id }})" title="{{ __('Edit') }}" />
                    <flux:button variant="ghost" size="sm" icon="trash" wire:click="confirmDelete({{ $todo->id }})" class="text-red-500 hover:text-red-600" title="{{ __('Hapus') }}" />
                </div>
            </div>
        @empty
            <div class="p-8 text-center rounded-xl border border-dashed border-neutral-300 dark:border-neutral-700">
                <flux:icon name="clipboard-document-list" class="size-12 mx-auto text-neutral-400 mb-2" />
                <p class="text-neutral-600 dark:text-neutral-400 font-medium">{{ __('Tidak ada data todo yang ditemukan.') }}</p>
                <flux:button variant="primary" size="sm" icon="plus" wire:click="openCreateModal" class="mt-4">
                    {{ __('Buat Todo Sekarang') }}
                </flux:button>
            </div>
        @endforelse
    </div>

    <!-- Create / Edit Modal -->
    <flux:modal wire:model="showFormModal" class="max-w-md">
        <form wire:submit="save" class="space-y-4">
            <div>
                <flux:heading size="lg">{{ $editingTodoId ? __('Edit Todo') : __('Tambah Todo Baru') }}</flux:heading>
                <flux:subheading>{{ __('Isi informasi detail tugas Anda di bawah ini.') }}</flux:subheading>
            </div>

            <flux:field>
                <flux:label>{{ __('Judul Tugas') }}</flux:label>
                <flux:input wire:model="title" placeholder="{{ __('Misal: Setup Proxmox LXC Container') }}" />
                <flux:error name="title" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Deskripsi / Catatan') }}</flux:label>
                <flux:textarea wire:model="description" rows="3" placeholder="{{ __('Catatan langkah-langkah atau checklist detail...') }}" />
                <flux:error name="description" />
            </flux:field>

            <div class="grid grid-cols-2 gap-3">
                <flux:field>
                    <flux:label>{{ __('Prioritas') }}</flux:label>
                    <flux:select wire:model="priority">
                        <flux:select.option value="low">{{ __('Low') }}</flux:select.option>
                        <flux:select.option value="medium">{{ __('Medium') }}</flux:select.option>
                        <flux:select.option value="high">{{ __('High') }}</flux:select.option>
                    </flux:select>
                    <flux:error name="priority" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Batas Waktu (Due Date)') }}</flux:label>
                    <flux:input type="date" wire:model="due_date" />
                    <flux:error name="due_date" />
                </flux:field>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Batal') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">
                    {{ $editingTodoId ? __('Simpan Perubahan') : __('Tambah Todo') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal wire:model="showDeleteModal" class="max-w-sm">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Hapus Todo?') }}</flux:heading>
            <flux:subheading>{{ __('Apakah Anda yakin ingin menghapus todo ini? Tindakan ini tidak dapat dibatalkan.') }}</flux:subheading>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Batal') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Hapus Sekarang') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>