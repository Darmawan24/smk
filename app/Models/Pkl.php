<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * PKL (Praktik Kerja Lapangan) Model
 * 
 * Represents internship/work practice data
 */
class Pkl extends Model
{
    use HasFactory;

    protected $table = 'pkl';

    protected $fillable = [
        'siswa_id',
        'nama_perusahaan',
        'alamat_perusahaan',
        'pembimbing_perusahaan',
        'pembimbing_sekolah_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'nilai_perusahaan',
        'nilai_sekolah',
        'keterangan',
        'tahun_ajaran_id',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    /**
     * Get the siswa.
     */
    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    /**
     * Get the pembimbing sekolah.
     */
    public function pembimbingSekolah()
    {
        return $this->belongsTo(Guru::class, 'pembimbing_sekolah_id');
    }

    /**
     * Get the tahun ajaran.
     */
    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    /**
     * Get the duration in days.
     *
     * @return int
     */
    public function getDurationInDaysAttribute()
    {
        return $this->tanggal_mulai->diffInDays($this->tanggal_selesai);
    }

    /**
     * Get the duration in months.
     *
     * @return float
     */
    public function getDurationInMonthsAttribute()
    {
        return round($this->duration_in_days / 30, 1);
    }

    /**
     * Get the final grade (average of company and school grades).
     *
     * @return string|null
     */
    public function getFinalGradeAttribute()
    {
        if (!$this->nilai_perusahaan || !$this->nilai_sekolah) {
            return null;
        }

        $gradeValues = ['A' => 4, 'B' => 3, 'C' => 2, 'D' => 1];
        $companyValue = $gradeValues[$this->nilai_perusahaan] ?? 0;
        $schoolValue = $gradeValues[$this->nilai_sekolah] ?? 0;
        
        $average = ($companyValue + $schoolValue) / 2;

        if ($average >= 3.5) return 'A';
        if ($average >= 2.5) return 'B';
        if ($average >= 1.5) return 'C';
        return 'D';
    }

    /**
     * Get status of PKL.
     *
     * @return string
     */
    public function getStatusAttribute()
    {
        $today = now();

        if ($today->lt($this->tanggal_mulai)) {
            return 'Belum Mulai';
        } elseif ($today->between($this->tanggal_mulai, $this->tanggal_selesai)) {
            return 'Sedang Berlangsung';
        } else {
            return 'Selesai';
        }
    }

    /**
     * Check if PKL is completed.
     *
     * @return bool
     */
    public function isCompleted()
    {
        return now()->gt($this->tanggal_selesai) && 
               $this->nilai_perusahaan && 
               $this->nilai_sekolah;
    }
}