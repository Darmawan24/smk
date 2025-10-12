<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Guru;
use App\Models\TahunAjaran;

class JadwalPelajaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tahunAjaran = TahunAjaran::where('is_active', true)->first();
        $kelas = Kelas::all();
        $mataPelajaran = MataPelajaran::all();
        $guru = Guru::all();

        if (!$tahunAjaran || $kelas->isEmpty() || $mataPelajaran->isEmpty() || $guru->isEmpty()) {
            return;
        }

        // Sample schedule for each class
        $jadwalTemplate = [
            'Senin' => [
                ['jam_mulai' => '07:00', 'jam_selesai' => '08:30'],
                ['jam_mulai' => '08:30', 'jam_selesai' => '10:00'],
                ['jam_mulai' => '10:15', 'jam_selesai' => '11:45'],
                ['jam_mulai' => '12:30', 'jam_selesai' => '14:00'],
            ],
            'Selasa' => [
                ['jam_mulai' => '07:00', 'jam_selesai' => '08:30'],
                ['jam_mulai' => '08:30', 'jam_selesai' => '10:00'],
                ['jam_mulai' => '10:15', 'jam_selesai' => '11:45'],
                ['jam_mulai' => '12:30', 'jam_selesai' => '14:00'],
            ],
            'Rabu' => [
                ['jam_mulai' => '07:00', 'jam_selesai' => '08:30'],
                ['jam_mulai' => '08:30', 'jam_selesai' => '10:00'],
                ['jam_mulai' => '10:15', 'jam_selesai' => '11:45'],
                ['jam_mulai' => '12:30', 'jam_selesai' => '14:00'],
            ],
            'Kamis' => [
                ['jam_mulai' => '07:00', 'jam_selesai' => '08:30'],
                ['jam_mulai' => '08:30', 'jam_selesai' => '10:00'],
                ['jam_mulai' => '10:15', 'jam_selesai' => '11:45'],
                ['jam_mulai' => '12:30', 'jam_selesai' => '14:00'],
            ],
            'Jumat' => [
                ['jam_mulai' => '07:00', 'jam_selesai' => '08:30'],
                ['jam_mulai' => '08:30', 'jam_selesai' => '10:00'],
                ['jam_mulai' => '10:15', 'jam_selesai' => '11:45'],
            ],
        ];

        // Create schedule for each class (limit to first 5 classes for demo)
        foreach ($kelas->take(5) as $kelasItem) {
            $mapelCounter = 0;
            
            foreach ($jadwalTemplate as $hari => $jamList) {
                foreach ($jamList as $jam) {
                    $mapel = $mataPelajaran->get($mapelCounter % $mataPelajaran->count());
                    $guruItem = $guru->get($mapelCounter % $guru->count());

                    JadwalPelajaran::create([
                        'kelas_id' => $kelasItem->id,
                        'mata_pelajaran_id' => $mapel->id,
                        'guru_id' => $guruItem->id,
                        'tahun_ajaran_id' => $tahunAjaran->id,
                        'hari' => $hari,
                        'jam_mulai' => $jam['jam_mulai'],
                        'jam_selesai' => $jam['jam_selesai'],
                        'ruangan' => 'R' . str_pad(($mapelCounter % 10) + 1, 2, '0', STR_PAD_LEFT),
                    ]);

                    $mapelCounter++;
                }
            }
        }
    }
}