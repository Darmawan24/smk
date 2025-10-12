<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Ekstrakurikuler Model
 * 
 * Represents extracurricular activities
 */
class Ekstrakurikuler extends Model
{
    use HasFactory;

    protected $table = 'ekstrakurikuler';

    protected $fillable = [
        'nama',
        'deskripsi',
        'pembina_id',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'is_active',
    ];

    protected $casts = [
        'jam_mulai' => 'datetime:H:i',
        'jam_selesai' => 'datetime:H:i',
        'is_active' => 'boolean',
    ];

    /**
     * Get the pembina (supervisor).
     */
    public function pembina()
    {
        return $this->belongsTo(Guru::class, 'pembina_id');
    }

    /**
     * Get the nilai ekstrakurikuler.
     */
    public function nilaiEkstrakurikuler()
    {
        return $this->hasMany(NilaiEkstrakurikuler::class);
    }

    /**
     * Get siswa enrolled in this ekstrakurikuler.
     */
    public function siswa()
    {
        return $this->belongsToMany(Siswa::class, 'nilai_ekstrakurikuler')
                    ->withPivot(['predikat', 'keterangan', 'tahun_ajaran_id'])
                    ->withTimestamps();
    }

    /**
     * Scope a query to only include active ekstrakurikuler.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the time range.
     *
     * @return string
     */
    public function getTimeRangeAttribute()
    {
        return $this->jam_mulai->format('H:i') . ' - ' . $this->jam_selesai->format('H:i');
    }

    /**
     * Get the schedule description.
     *
     * @return string
     */
    public function getScheduleAttribute()
    {
        return $this->hari . ', ' . $this->time_range;
    }

    /**
     * Get count of active members for specific tahun ajaran.
     *
     * @param  int  $tahunAjaranId
     * @return int
     */
    public function getMemberCount($tahunAjaranId)
    {
        return $this->nilaiEkstrakurikuler()
                    ->where('tahun_ajaran_id', $tahunAjaranId)
                    ->count();
    }
}