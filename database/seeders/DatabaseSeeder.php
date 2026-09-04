<?php

namespace Database\Seeders;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $demoTodos = [
            [
                'title' => 'Setup Proxmox VE Server',
                'description' => 'Instalasi Proxmox VE 8.x pada server fisik / bare-metal dan konfigurasi repository no-subscription.',
                'priority' => 'high',
                'is_completed' => true,
                'due_date' => now()->subDays(3)->toDateString(),
            ],
            [
                'title' => 'Konfigurasi Network Bridge vmbr0 & DNS',
                'description' => 'Pengaturan subnet IP statis 192.168.1.0/24 dan DNS resolver Cloudflare (1.1.1.1).',
                'priority' => 'high',
                'is_completed' => true,
                'due_date' => now()->subDays(2)->toDateString(),
            ],
            [
                'title' => 'Install Docker & Docker Compose di Ubuntu VM',
                'description' => 'Install Docker Engine resmi via get.docker.com dan konfigurasi user group non-root.',
                'priority' => 'high',
                'is_completed' => false,
                'due_date' => now()->toDateString(),
            ],
            [
                'title' => 'Deploy Aplikasi Todo CRUD (Nginx + PHP-FPM + MySQL)',
                'description' => 'Jalankan docker compose up -d --build untuk orkestrasi 3 container aplikasi dan database.',
                'priority' => 'medium',
                'is_completed' => false,
                'due_date' => now()->addDay()->toDateString(),
            ],
            [
                'title' => 'Hardening SSH & Setup UFW Firewall',
                'description' => 'Nonaktifkan PasswordAuthentication pada sshd_config dan buka hanya port 22, 80, 443.',
                'priority' => 'medium',
                'is_completed' => false,
                'due_date' => now()->addDays(3)->toDateString(),
            ],
            [
                'title' => 'Konfigurasi Scheduled Snapshot Backup Proxmox',
                'description' => 'Jadwalkan backup otomatis mingguan dengan kompresi ZSTD ke storage local-lvm.',
                'priority' => 'low',
                'is_completed' => false,
                'due_date' => now()->addDays(7)->toDateString(),
            ],
        ];

        foreach ($demoTodos as $todoData) {
            Todo::firstOrCreate(
                [
                    'user_id' => $demoUser->id,
                    'title' => $todoData['title'],
                ],
                [
                    'description' => $todoData['description'],
                    'priority' => $todoData['priority'],
                    'is_completed' => $todoData['is_completed'],
                    'due_date' => $todoData['due_date'],
                ]
            );
        }
    }
}
