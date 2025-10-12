<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TahunAjaran;

class TahunAjaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Previous years
        TahunAjaran::create([
            'tahun' => '2022/2023',
            'semester' => '1',
            'is_active' => false,
        ]);

        TahunAjaran::create([
            'tahun' => '2022/2023',
            'semester' => '2',
            'is_active' => false,
        ]);

        TahunAjaran::create([
            'tahun' => '2023/2024',
            'semester' => '1',
            'is_active' => false,
        ]);

        TahunAjaran::create([
            'tahun' => '2023/2024',
            'semester' => '2',
            'is_active' => false,
        ]);

        // Current active academic year
        TahunAjaran::create([
            'tahun' => '2024/2025',
            'semester' => '1',
            'is_active' => true,
        ]);

        // Next semester (inactive)
        TahunAjaran::create([
            'tahun' => '2024/2025',
            'semester' => '2',
            'is_active' => false,
        ]);
    }
}