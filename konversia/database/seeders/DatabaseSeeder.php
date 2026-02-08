<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\WhatsAppNumbersSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Desabilitado - usamos WhatsAppNumbersSeeder para dados de demo
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Executar seeder específico do projeto
        $this->call([
            WhatsAppNumbersSeeder::class,
        ]);
    }
}
