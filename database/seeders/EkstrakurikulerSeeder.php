<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ekstrakurikuler;
use App\Models\Guru;

class EkstrakurikulerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guru = Guru::all();

        $ekstrakurikuler = [
            [
                'nama' => 'Pramuka',
                'deskripsi' => 'Kegiatan kepramukaan untuk membentuk karakter dan kemandirian siswa',
                'hari' => 'Jumat',
                'jam_mulai' => '14:00',
                'jam_selesai' => '16:00',
            ],
            [
                'nama' => 'PMR (Palang Merah Remaja)',
                'deskripsi' => 'Kegiatan kesehatan dan pertolongan pertama',
                'hari' => 'Sabtu',
                'jam_mulai' => '08:00',
                'jam_selesai' => '10:00',
            ],
            [
                'nama' => 'Futsal',
                'deskripsi' => 'Olahraga futsal untuk mengembangkan bakat dan minat siswa',
                'hari' => 'Selasa',
                'jam_mulai' => '15:30',
                'jam_selesai' => '17:00',
            ],
            [
                'nama' => 'English Club',
                'deskripsi' => 'Klub bahasa Inggris untuk meningkatkan kemampuan berbahasa',
                'hari' => 'Rabu',
                'jam_mulai' => '14:30',
                'jam_selesai' => '16:00',
            ],
            [
                'nama' => 'Robotika',
                'deskripsi' => 'Klub robotika untuk mengembangkan kemampuan teknologi',
                'hari' => 'Kamis',
                'jam_mulai' => '15:00',
                'jam_selesai' => '17:00',
            ],
            [
                'nama' => 'Seni Tari',
                'deskripsi' => 'Kegiatan seni tari tradisional dan modern',
                'hari' => 'Jumat',
                'jam_mulai' => '15:00',
                'jam_selesai' => '16:30',
            ],
            [
                'nama' => 'Karate',
                'deskripsi' => 'Bela diri karate untuk kebugaran dan disiplin',
                'hari' => 'Sabtu',
                'jam_mulai' => '10:00',
                'jam_selesai' => '12:00',
            ],
            [
                'nama' => 'Jurnalistik',
                'deskripsi' => 'Kegiatan jurnalistik dan penulisan untuk majalah sekolah',
                'hari' => 'Rabu',
                'jam_mulai' => '14:00',
                'jam_selesai' => '15:30',
            ],
        ];

        foreach ($ekstrakurikuler as $index => $ekskul) {
            $pembina = $guru->get($index % $guru->count());

            Ekstrakurikuler::create([
                'nama' => $ekskul['nama'],
                'deskripsi' => $ekskul['deskripsi'],
                'pembina_id' => $pembina->id,
                'hari' => $ekskul['hari'],
                'jam_mulai' => $ekskul['jam_mulai'],
                'jam_selesai' => $ekskul['jam_selesai'],
                'is_active' => true,
            ]);
        }
    }
}