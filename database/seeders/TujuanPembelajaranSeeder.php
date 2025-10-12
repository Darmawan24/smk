<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TujuanPembelajaran;
use App\Models\CapaianPembelajaran;

class TujuanPembelajaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $capaianPembelajaran = CapaianPembelajaran::all();

        foreach ($capaianPembelajaran as $cp) {
            // Create 2-3 tujuan pembelajaran for each capaian pembelajaran
            $tujuanData = [];

            switch ($cp->kode_cp) {
                case 'CP.BIN.1':
                    $tujuanData = [
                        ['kode_tp' => 'TP.1', 'deskripsi' => 'Mengidentifikasi struktur dan unsur kebahasaan teks'],
                        ['kode_tp' => 'TP.2', 'deskripsi' => 'Menganalisis isi dan makna teks'],
                        ['kode_tp' => 'TP.3', 'deskripsi' => 'Mengevaluasi kualitas dan kredibilitas teks'],
                    ];
                    break;

                case 'CP.BIN.2':
                    $tujuanData = [
                        ['kode_tp' => 'TP.1', 'deskripsi' => 'Menyusun teks dengan struktur yang sistematis'],
                        ['kode_tp' => 'TP.2', 'deskripsi' => 'Menggunakan bahasa yang efektif dan komunikatif'],
                    ];
                    break;

                case 'CP.MTK.1':
                    $tujuanData = [
                        ['kode_tp' => 'TP.1', 'deskripsi' => 'Menerapkan konsep logika dalam pemecahan masalah'],
                        ['kode_tp' => 'TP.2', 'deskripsi' => 'Menganalisis pola dan hubungan matematis'],
                    ];
                    break;

                case 'CP.MTK.2':
                    $tujuanData = [
                        ['kode_tp' => 'TP.1', 'deskripsi' => 'Mengidentifikasi masalah matematis dalam konteks nyata'],
                        ['kode_tp' => 'TP.2', 'deskripsi' => 'Menyelesaikan masalah menggunakan strategi yang tepat'],
                    ];
                    break;

                case 'CP.PBO.1':
                    $tujuanData = [
                        ['kode_tp' => 'TP.1', 'deskripsi' => 'Memahami konsep class dan object'],
                        ['kode_tp' => 'TP.2', 'deskripsi' => 'Memahami inheritance dan polymorphism'],
                        ['kode_tp' => 'TP.3', 'deskripsi' => 'Memahami encapsulation dan abstraction'],
                    ];
                    break;

                case 'CP.PBO.2':
                    $tujuanData = [
                        ['kode_tp' => 'TP.1', 'deskripsi' => 'Membuat class dan object dalam program'],
                        ['kode_tp' => 'TP.2', 'deskripsi' => 'Mengimplementasikan inheritance dalam program'],
                    ];
                    break;

                case 'CP.BD.1':
                    $tujuanData = [
                        ['kode_tp' => 'TP.1', 'deskripsi' => 'Memahami entity relationship diagram'],
                        ['kode_tp' => 'TP.2', 'deskripsi' => 'Memahami normalisasi database'],
                    ];
                    break;

                case 'CP.BD.2':
                    $tujuanData = [
                        ['kode_tp' => 'TP.1', 'deskripsi' => 'Merancang struktur database'],
                        ['kode_tp' => 'TP.2', 'deskripsi' => 'Mengoperasikan database dengan SQL'],
                    ];
                    break;

                case 'CP.WEB.1':
                    $tujuanData = [
                        ['kode_tp' => 'TP.1', 'deskripsi' => 'Memahami HTML, CSS, dan JavaScript'],
                        ['kode_tp' => 'TP.2', 'deskripsi' => 'Memahami protokol HTTP dan HTTPS'],
                    ];
                    break;

                case 'CP.WEB.2':
                    $tujuanData = [
                        ['kode_tp' => 'TP.1', 'deskripsi' => 'Membuat website responsif'],
                        ['kode_tp' => 'TP.2', 'deskripsi' => 'Mengintegrasikan frontend dan backend'],
                    ];
                    break;

                case 'CP.JARKOM.1':
                    $tujuanData = [
                        ['kode_tp' => 'TP.1', 'deskripsi' => 'Memahami model OSI dan TCP/IP'],
                        ['kode_tp' => 'TP.2', 'deskripsi' => 'Memahami topologi jaringan'],
                    ];
                    break;

                case 'CP.JARKOM.2':
                    $tujuanData = [
                        ['kode_tp' => 'TP.1', 'deskripsi' => 'Mengkonfigurasi router dan switch'],
                        ['kode_tp' => 'TP.2', 'deskripsi' => 'Mengelola keamanan jaringan'],
                    ];
                    break;

                default:
                    $tujuanData = [
                        ['kode_tp' => 'TP.1', 'deskripsi' => 'Tujuan pembelajaran default 1'],
                        ['kode_tp' => 'TP.2', 'deskripsi' => 'Tujuan pembelajaran default 2'],
                    ];
                    break;
            }

            foreach ($tujuanData as $tp) {
                TujuanPembelajaran::create([
                    'capaian_pembelajaran_id' => $cp->id,
                    'kode_tp' => $tp['kode_tp'],
                    'deskripsi' => $tp['deskripsi'],
                ]);
            }
        }
    }
}