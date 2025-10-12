<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MataPelajaran;

class MataPelajaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mataPelajaran = [
            // Mata Pelajaran Umum
            [
                'kode_mapel' => 'PAI',
                'nama_mapel' => 'Pendidikan Agama Islam',
                'kelompok' => 'umum',
                'kkm' => 75,
            ],
            [
                'kode_mapel' => 'PKN',
                'nama_mapel' => 'Pendidikan Pancasila dan Kewarganegaraan',
                'kelompok' => 'umum',
                'kkm' => 75,
            ],
            [
                'kode_mapel' => 'BIN',
                'nama_mapel' => 'Bahasa Indonesia',
                'kelompok' => 'umum',
                'kkm' => 75,
            ],
            [
                'kode_mapel' => 'MTK',
                'nama_mapel' => 'Matematika',
                'kelompok' => 'umum',
                'kkm' => 75,
            ],
            [
                'kode_mapel' => 'SEJ',
                'nama_mapel' => 'Sejarah Indonesia',
                'kelompok' => 'umum',
                'kkm' => 75,
            ],
            [
                'kode_mapel' => 'BIG',
                'nama_mapel' => 'Bahasa Inggris',
                'kelompok' => 'umum',
                'kkm' => 75,
            ],
            [
                'kode_mapel' => 'SBDP',
                'nama_mapel' => 'Seni Budaya',
                'kelompok' => 'umum',
                'kkm' => 75,
            ],
            [
                'kode_mapel' => 'PJOK',
                'nama_mapel' => 'Pendidikan Jasmani Olahraga dan Kesehatan',
                'kelompok' => 'umum',
                'kkm' => 75,
            ],

            // Mata Pelajaran Kejuruan RPL
            [
                'kode_mapel' => 'PBO',
                'nama_mapel' => 'Pemrograman Berorientasi Objek',
                'kelompok' => 'kejuruan',
                'kkm' => 75,
            ],
            [
                'kode_mapel' => 'BD',
                'nama_mapel' => 'Basis Data',
                'kelompok' => 'kejuruan',
                'kkm' => 75,
            ],
            [
                'kode_mapel' => 'WEB',
                'nama_mapel' => 'Pemrograman Web',
                'kelompok' => 'kejuruan',
                'kkm' => 75,
            ],
            [
                'kode_mapel' => 'MOBILE',
                'nama_mapel' => 'Pemrograman Mobile',
                'kelompok' => 'kejuruan',
                'kkm' => 75,
            ],
            [
                'kode_mapel' => 'RPL',
                'nama_mapel' => 'Rekayasa Perangkat Lunak',
                'kelompok' => 'kejuruan',
                'kkm' => 75,
            ],

            // Mata Pelajaran Kejuruan TKJ
            [
                'kode_mapel' => 'JARKOM',
                'nama_mapel' => 'Jaringan Komputer',
                'kelompok' => 'kejuruan',
                'kkm' => 75,
            ],
            [
                'kode_mapel' => 'SO',
                'nama_mapel' => 'Sistem Operasi',
                'kelompok' => 'kejuruan',
                'kkm' => 75,
            ],
            [
                'kode_mapel' => 'ADMIN',
                'nama_mapel' => 'Administrasi Sistem Jaringan',
                'kelompok' => 'kejuruan',
                'kkm' => 75,
            ],
            [
                'kode_mapel' => 'KEAMANAN',
                'nama_mapel' => 'Keamanan Jaringan',
                'kelompok' => 'kejuruan',
                'kkm' => 75,
            ],

            // Mata Pelajaran Kejuruan MM
            [
                'kode_mapel' => 'DG',
                'nama_mapel' => 'Desain Grafis',
                'kelompok' => 'kejuruan',
                'kkm' => 75,
            ],
            [
                'kode_mapel' => 'ANIMASI',
                'nama_mapel' => 'Animasi 2D/3D',
                'kelompok' => 'kejuruan',
                'kkm' => 75,
            ],
            [
                'kode_mapel' => 'VIDEO',
                'nama_mapel' => 'Produksi Video',
                'kelompok' => 'kejuruan',
                'kkm' => 75,
            ],
            [
                'kode_mapel' => 'FOTOGRAFI',
                'nama_mapel' => 'Fotografi',
                'kelompok' => 'kejuruan',
                'kkm' => 75,
            ],

            // Mata Pelajaran Muatan Lokal
            [
                'kode_mapel' => 'BJSD',
                'nama_mapel' => 'Bahasa Jawa/Sunda',
                'kelompok' => 'muatan_lokal',
                'kkm' => 75,
            ],
            [
                'kode_mapel' => 'KEWIRAUSAHAAN',
                'nama_mapel' => 'Kewirausahaan',
                'kelompok' => 'muatan_lokal',
                'kkm' => 75,
            ],
        ];

        foreach ($mataPelajaran as $mapel) {
            MataPelajaran::create($mapel);
        }
    }
}