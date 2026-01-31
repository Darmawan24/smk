<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Support\Collection;

/**
 * Trait untuk menghitung nilai akhir rapor per mata pelajaran.
 * Rumus: rata-rata dari (rata-rata CP-1, CP-2, dst) dan nilai STS/SAS.
 * Sama dengan kolom "Nilai Akhir" di wali kelas > cek penilaian STS/SAS.
 */
trait CalculatesNilaiAkhirRapor
{
    /**
     * Hitung nilai akhir rapor dari koleksi Nilai (per mapel).
     * Rumus: (rata_rata_CP + nilai_STS/SAS) / 2
     *
     * @param  Collection  $nilaiGroup  Koleksi Nilai untuk satu mapel (CP-1, CP-2, STS/SAS)
     * @return float|string  Nilai numerik atau '-' jika tidak ada data
     */
    protected function calculateNilaiAkhirRapor(Collection $nilaiGroup)
    {
        if ($nilaiGroup->isEmpty()) {
            return '-';
        }

        $cpOnly = $nilaiGroup->filter(function ($n) {
            $kode = optional($n->capaianPembelajaran)->kode_cp ?? '';
            return $kode !== 'STS' && $kode !== 'SAS';
        });

        $sumatif = $nilaiGroup->first(function ($n) {
            $kode = optional($n->capaianPembelajaran)->kode_cp ?? '';
            return $kode === 'STS' || $kode === 'SAS';
        });

        $getNilai = fn ($n) => $n->nilai_akhir ?? $n->nilai_sumatif_1 ?? null;

        $cpValues = $cpOnly->map($getNilai)->filter(fn ($v) => $v !== null && $v !== '');
        $rataRataCP = $cpValues->isNotEmpty()
            ? $cpValues->avg()
            : null;

        $nilaiSumatif = $sumatif ? $getNilai($sumatif) : null;
        if ($nilaiSumatif !== null && $nilaiSumatif !== '') {
            $nilaiSumatif = (float) $nilaiSumatif;
        } else {
            $nilaiSumatif = null;
        }

        if ($rataRataCP !== null && $nilaiSumatif !== null) {
            return round(($rataRataCP + $nilaiSumatif) / 2, 2);
        }
        if ($rataRataCP !== null) {
            return round($rataRataCP, 2);
        }
        if ($nilaiSumatif !== null) {
            return round($nilaiSumatif, 2);
        }

        return '-';
    }
}
