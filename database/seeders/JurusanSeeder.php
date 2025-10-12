<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Jurusan;

class JurusanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jurusan = [
            [
                'kode_jurusan' => 'RPL',
                'nama_jurusan' => 'Rekayasa Perangkat Lunak',
                'deskripsi' => 'Program keahlian yang mempelajari pengembangan aplikasi dan sistem perangkat lunak',
            ],
            [
                'kode_jurusan' => 'TKJ',
                'nama_jurusan' => 'Teknik Komputer dan Jaringan',
                'deskripsi' => 'Program keahlian yang mempelajari instalasi, konfigurasi, dan maintenance sistem komputer dan jaringan',
            ],
            [
                'kode_jurusan' => 'MM',
                'nama_jurusan' => 'Multimedia',
                'deskripsi' => 'Program keahlian yang mempelajari desain grafis, animasi, video editing, dan produksi multimedia',
            ],
            [
                'kode_jurusan' => 'OTKP',
                'nama_jurusan' => 'Otomatisasi dan Tata Kelola Perkantoran',
                'deskripsi' => 'Program keahlian yang mempelajari administrasi perkantoran modern dan otomatisasi sistem kantor',
            ],
            [
                'kode_jurusan' => 'AKL',
                'nama_jurusan' => 'Akuntansi dan Keuangan Lembaga',
                'deskripsi' => 'Program keahlian yang mempelajari pencatatan, pelaporan, dan analisis keuangan',
            ],
            [
                'kode_jurusan' => 'BDP',
                'nama_jurusan' => 'Bisnis Daring dan Pemasaran',
                'deskripsi' => 'Program keahlian yang mempelajari strategi pemasaran digital dan e-commerce',
            ],
        ];

        foreach ($jurusan as $data) {
            Jurusan::create($data);
        }
    }
}