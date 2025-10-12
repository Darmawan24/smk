<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * JadwalPelajaran Model
 * 
 * Represents class schedules
 */
class JadwalPelajaran extends Model
{
    use HasFactory;

    protected $table = 'jadwal_pelajaran';

    protected $fillable = [
        'kelas_id',
        'mata_pelajaran_id',
        'guru_id',
        'tahun_ajaran_id',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'ruangan',
    ];

    protected $casts = [
        'jam_mulai' => 'datetime:H:i',
        'jam_selesai' => 'datetime:H:i',
    ];

    /**
     * Get the kelas.
     */
    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    /**
     * Get the mata pelajaran.
     */
    public function mataPelajaran()
    {
        return $this->belongsTo(MataPelajaran::class);
    }

    /**
     * Get the guru.
     */
    public function guru()
    {
        return $this->belongsTo(Guru::class);
    }

    /**
     * Get the tahun ajaran.
     */
    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    /**
     * Scope a query to filter by day.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $hari
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHari($query, $hari)
    {
        return $query->where('hari', $hari);
    }

    /**
     * Scope a query to filter by active tahun ajaran.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActiveTahunAjaran($query)
    {
        return $query->whereHas('tahunAjaran', function ($q) {
            $q->where('is_active', true);
        });
    }

    /**
     * Get the duration in minutes.
     *
     * @return int
     */
    public function getDurationAttribute()
    {
        return $this->jam_mulai->diffInMinutes($this->jam_selesai);
    }

    /**
     * Get the formatted time range.
     *
     * @return string
     */
    public function getTimeRangeAttribute()
    {
        return $this->jam_mulai->format('H:i') . ' - ' . $this->jam_selesai->format('H:i');
    }

    /**
     * Check if schedule conflicts with another time.
     *
     * @param  string  $jamMulai
     * @param  string  $jamSelesai
     * @return bool
     */
    public function conflictsWith($jamMulai, $jamSelesai)
    {
        return !($jamSelesai <= $this->jam_mulai || $jamMulai >= $this->jam_selesai);
    }
}