<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DimensiP5;

class DimensiP5Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dimensi = [
            [
                'nama_dimensi' => 'Beriman, Bertakwa kepada Tuhan Yang Maha Esa, dan Berakhlak Mulia',
                'deskripsi' => 'Pelajar Indonesia yang beriman, bertakwa kepada Tuhan YME, dan berakhlak mulia adalah pelajar yang berakhlak dalam hubungannya dengan Tuhan Yang Maha Esa. Ia memahami ajaran agama dan kepercayaannya serta menerapkan pemahaman tersebut dalam kehidupannya sehari-hari.',
            ],
            [
                'nama_dimensi' => 'Berkebinekaan Global',
                'deskripsi' => 'Pelajar Indonesia mempertahankan budaya luhur, lokalitas dan identitasnya, dan tetap berpikiran terbuka dalam berinteraksi dengan budaya lain, sehingga menumbuhkan rasa saling menghargai dan kemungkinan terbentuknya budaya baru yang positif dan tidak bertentangan dengan budaya luhur bangsa.',
            ],
            [
                'nama_dimensi' => 'Bergotong Royong',
                'deskripsi' => 'Pelajar Indonesia memiliki kemampuan bergotong-royong, yaitu kemampuan untuk melakukan kegiatan secara bersama-sama dengan suka rela agar kegiatan yang dikerjakan dapat berjalan lancar, mudah dan ringan.',
            ],
            [
                'nama_dimensi' => 'Mandiri',
                'deskripsi' => 'Pelajar Indonesia merupakan pelajar mandiri, yaitu pelajar yang bertanggung jawab atas proses dan hasil belajarnya. Kemandiran dalam belajar ini dibentuk oleh inisiatif sendiri (menyadari dan memahami tujuan belajar), melakukan evaluasi terhadap proses belajar, dan melakukan refleksi serta perbaikan.',
            ],
            [
                'nama_dimensi' => 'Bernalar Kritis',
                'deskripsi' => 'Pelajar yang bernalar kritis mampu secara objektif memproses informasi baik kualitatif maupun kuantitatif, membangun keterkaitan antara berbagai informasi, menganalisis informasi, mengevaluasi dan menyimpulkannya.',
            ],
            [
                'nama_dimensi' => 'Kreatif',
                'deskripsi' => 'Pelajar yang kreatif mampu memodifikasi dan menghasilkan sesuatu yang orisinal, bermakna, bermanfaat, dan berdampak. Kreativitas dapat diwujudkan sebagai fleksibilitas berpikir dalam mencari alternatif solusi permasalahan.',
            ],
        ];

        foreach ($dimensi as $data) {
            DimensiP5::create($data);
        }
    }
}