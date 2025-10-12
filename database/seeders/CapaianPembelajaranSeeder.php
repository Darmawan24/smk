<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CapaianPembelajaran;
use App\Models\MataPelajaran;

class CapaianPembelajaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample Capaian Pembelajaran for key subjects
        $mataPelajaran = MataPelajaran::all()->keyBy('kode_mapel');

        $capaianData = [
            // Bahasa Indonesia
            [
                'mapel' => 'BIN',
                'kode_cp' => 'CP.BIN.1',
                'deskripsi' => 'Peserta didik mampu memahami, menganalisis, dan mengevaluasi berbagai jenis teks untuk mengembangkan pemahaman dan wawasan.',
                'fase' => 'F',
                'elemen' => 'pemahaman',
            ],
            [
                'mapel' => 'BIN',
                'kode_cp' => 'CP.BIN.2',
                'deskripsi' => 'Peserta didik mampu memproduksi berbagai jenis teks tertulis dan lisan dengan struktur yang jelas dan bahasa yang efektif.',
                'fase' => 'F',
                'elemen' => 'keterampilan',
            ],

            // Matematika
            [
                'mapel' => 'MTK',
                'kode_cp' => 'CP.MTK.1',
                'deskripsi' => 'Peserta didik mampu menunjukkan kemampuan berpikir logis, kritis, kreatif, inovatif dan mandiri.',
                'fase' => 'F',
                'elemen' => 'pemahaman',
            ],
            [
                'mapel' => 'MTK',
                'kode_cp' => 'CP.MTK.2',
                'deskripsi' => 'Peserta didik mampu memecahkan masalah menggunakan konsep matematika yang sesuai.',
                'fase' => 'F',
                'elemen' => 'keterampilan',
            ],

            // Pemrograman Berorientasi Objek
            [
                'mapel' => 'PBO',
                'kode_cp' => 'CP.PBO.1',
                'deskripsi' => 'Peserta didik mampu memahami konsep dasar pemrograman berorientasi objek.',
                'fase' => 'F',
                'elemen' => 'pemahaman',
            ],
            [
                'mapel' => 'PBO',
                'kode_cp' => 'CP.PBO.2',
                'deskripsi' => 'Peserta didik mampu mengimplementasikan program menggunakan paradigma berorientasi objek.',
                'fase' => 'F',
                'elemen' => 'keterampilan',
            ],

            // Basis Data
            [
                'mapel' => 'BD',
                'kode_cp' => 'CP.BD.1',
                'deskripsi' => 'Peserta didik mampu memahami konsep sistem basis data dan model data.',
                'fase' => 'F',
                'elemen' => 'pemahaman',
            ],
            [
                'mapel' => 'BD',
                'kode_cp' => 'CP.BD.2',
                'deskripsi' => 'Peserta didik mampu merancang dan mengimplementasikan basis data.',
                'fase' => 'F',
                'elemen' => 'keterampilan',
            ],

            // Pemrograman Web
            [
                'mapel' => 'WEB',
                'kode_cp' => 'CP.WEB.1',
                'deskripsi' => 'Peserta didik mampu memahami teknologi web dan protokol internet.',
                'fase' => 'F',
                'elemen' => 'pemahaman',
            ],
            [
                'mapel' => 'WEB',
                'kode_cp' => 'CP.WEB.2',
                'deskripsi' => 'Peserta didik mampu mengembangkan aplikasi web responsif dan interaktif.',
                'fase' => 'F',
                'elemen' => 'keterampilan',
            ],

            // Jaringan Komputer
            [
                'mapel' => 'JARKOM',
                'kode_cp' => 'CP.JARKOM.1',
                'deskripsi' => 'Peserta didik mampu memahami konsep dasar jaringan komputer dan protokol komunikasi.',
                'fase' => 'F',
                'elemen' => 'pemahaman',
            ],
            [
                'mapel' => 'JARKOM',
                'kode_cp' => 'CP.JARKOM.2',
                'deskripsi' => 'Peserta didik mampu mengkonfigurasi dan mengelola jaringan komputer.',
                'fase' => 'F',
                'elemen' => 'keterampilan',
            ],
        ];

        foreach ($capaianData as $data) {
            $mapelId = $mataPelajaran[$data['mapel']]->id ?? null;

            if ($mapelId) {
                CapaianPembelajaran::create([
                    'mata_pelajaran_id' => $mapelId,
                    'kode_cp' => $data['kode_cp'],
                    'deskripsi' => $data['deskripsi'],
                    'fase' => $data['fase'],
                    'elemen' => $data['elemen'],
                ]);
            }
        }
    }
}