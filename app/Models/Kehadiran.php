<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Kehadiran Model
 * 
 * Represents student attendance records
 */
class Kehadiran extends Model
{
    use HasFactory;

    protected $table = 'kehadiran';

    protected $fillable = [
        'siswa_id',
        'tahun_ajaran_id',
        'sakit',
        'izin',
        'tanpa_keterangan',
    ];

    protected $casts = [
        'sakit' => 'integer',
        'izin' => 'integer',
        'tanpa_keterangan' => 'integer',
    ];

    /**
     * Get the siswa.
     */
    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    /**
     * Get the tahun ajaran.
     */
    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    /**
     * Get total absence days.
     *
     * @return int
     */
    public function getTotalAbsenceAttribute()
    {
        return $this->sakit + $this->izin + $this->tanpa_keterangan;
    }

    /**
     * Get attendance percentage (assuming 180 effective school days).
     *
     * @param  int  $totalDays
     * @return float
     */
    public function getAttendancePercentage($totalDays = 180)
    {
        $presentDays = $totalDays - $this->total_absence;
        return round(($presentDays / $totalDays) * 100, 2);
    }

    /**
     * Check if attendance meets minimum requirement (default 75%).
     *
     * @param  float  $minimumPercentage
     * @return bool
     */
    public function meetsMinimumAttendance($minimumPercentage = 75.0)
    {
        return $this->getAttendancePercentage() >= $minimumPercentage;
    }

    /**
     * Get attendance status.
     *
     * @return string
     */
    public function getStatusAttribute()
    {
        $percentage = $this->getAttendancePercentage();

        if ($percentage >= 95) {
            return 'Sangat Baik';
        } elseif ($percentage >= 85) {
            return 'Baik';
        } elseif ($percentage >= 75) {
            return 'Cukup';
        } else {
            return 'Kurang';
        }
    }

    /**
     * Increment absence by type.
     *
     * @param  string  $type
     * @param  int  $count
     * @return void
     */
    public function incrementAbsence($type, $count = 1)
    {
        if (in_array($type, ['sakit', 'izin', 'tanpa_keterangan'])) {
            $this->increment($type, $count);
        }
    }
}