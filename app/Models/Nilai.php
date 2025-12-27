<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Nilai Model
 * 
 * Represents student grades
 */
class Nilai extends Model
{
    use HasFactory;

    protected $table = 'nilai';

    protected $fillable = [
        'siswa_id',
        'mata_pelajaran_id',
        'tahun_ajaran_id',
        'guru_id',
        'capaian_pembelajaran_id',
        'semester',
        'nilai_sumatif_1',
        'nilai_sumatif_2',
        'nilai_sumatif_3',
        'nilai_sumatif_4',
        'nilai_sumatif_5',
        'nilai_akhir',
        'nilai_uts',
        'nilai_uas',
        'nilai_rapor',
        'predikat',
        'deskripsi',
    ];

    protected $casts = [
        'nilai_sumatif_1' => 'integer',
        'nilai_sumatif_2' => 'integer',
        'nilai_sumatif_3' => 'integer',
        'nilai_sumatif_4' => 'integer',
        'nilai_sumatif_5' => 'integer',
        'nilai_uts' => 'integer',
        'nilai_uas' => 'integer',
        'nilai_akhir' => 'decimal:2',
        'nilai_rapor' => 'decimal:2',
    ];

    /**
     * Get the siswa.
     */
    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    /**
     * Get the mata pelajaran.
     */
    public function mataPelajaran()
    {
        return $this->belongsTo(MataPelajaran::class);
    }

    /**
     * Get the tahun ajaran.
     */
    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    /**
     * Get the guru who input the nilai.
     */
    public function guru()
    {
        return $this->belongsTo(Guru::class);
    }

    /**
     * Get the capaian pembelajaran.
     */
    public function capaianPembelajaran()
    {
        return $this->belongsTo(CapaianPembelajaran::class);
    }

    /**
     * Calculate nilai akhir from sumatif grades.
     *
     * @return float
     */
    public function calculateNilaiAkhir()
    {
        $sumatifScores = array_filter([
            $this->nilai_sumatif_1,
            $this->nilai_sumatif_2,
            $this->nilai_sumatif_3,
            $this->nilai_sumatif_4,
            $this->nilai_sumatif_5,
        ]);

        if (empty($sumatifScores)) {
            return 0;
        }

        return round(array_sum($sumatifScores) / count($sumatifScores), 2);
    }

    /**
     * Calculate nilai rapor (40% akhir + 30% UTS + 30% UAS).
     *
     * @return float
     */
    public function calculateNilaiRapor()
    {
        $nilaiAkhir = $this->nilai_akhir ?? $this->calculateNilaiAkhir();
        $nilaiUts = $this->nilai_uts ?? 0;
        $nilaiUas = $this->nilai_uas ?? 0;

        return round(($nilaiAkhir * 0.4) + ($nilaiUts * 0.3) + ($nilaiUas * 0.3), 2);
    }

    /**
     * Determine predikat based on nilai rapor.
     *
     * @return string
     */
    public function determinePredikat()
    {
        $nilai = $this->nilai_rapor ?? $this->calculateNilaiRapor();

        if ($nilai >= 90) {
            return 'A';
        } elseif ($nilai >= 80) {
            return 'B';
        } elseif ($nilai >= 70) {
            return 'C';
        } else {
            return 'D';
        }
    }

    /**
     * Check if student passed (nilai >= KKM).
     *
     * @return bool
     */
    public function isPassed()
    {
        return $this->nilai_rapor >= $this->mataPelajaran->kkm;
    }

    /**
     * Get status text.
     *
     * @return string
     */
    public function getStatusAttribute()
    {
        return $this->isPassed() ? 'Tuntas' : 'Tidak Tuntas';
    }

    /**
     * Boot method to auto-calculate values.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($nilai) {
            if (is_null($nilai->nilai_akhir)) {
                $nilai->nilai_akhir = $nilai->calculateNilaiAkhir();
            }
            
            if (is_null($nilai->nilai_rapor)) {
                $nilai->nilai_rapor = $nilai->calculateNilaiRapor();
            }
            
            if (is_null($nilai->predikat)) {
                $nilai->predikat = $nilai->determinePredikat();
            }
        });
    }
}