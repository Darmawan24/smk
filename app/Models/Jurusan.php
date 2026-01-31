<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Jurusan Model
 * 
 * Represents study programs/departments in the school
 */
class Jurusan extends Model
{
    use HasFactory;

    protected $table = 'jurusan';

    protected $fillable = [
        'kode_jurusan',
        'nama_jurusan',
        'deskripsi',
        'kepala_jurusan_id',
    ];

    /**
     * Get the kepala jurusan (guru).
     */
    public function kepalaJurusan()
    {
        return $this->belongsTo(Guru::class, 'kepala_jurusan_id');
    }

    /**
     * Get the kelas for this jurusan.
     */
    public function kelas()
    {
        return $this->hasMany(Kelas::class);
    }

    /**
     * Get the UKK for this jurusan.
     */
    public function ukk()
    {
        return $this->hasMany(Ukk::class);
    }

    /**
     * Get all siswa in this jurusan through kelas.
     */
    public function siswa()
    {
        return $this->hasManyThrough(Siswa::class, Kelas::class);
    }

    /**
     * Get count of active students in this jurusan.
     *
     * @return int
     */
    public function getActiveSiswaCountAttribute()
    {
        return $this->siswa()->where('status', 'aktif')->count();
    }

    /**
     * Get count of classes in this jurusan.
     *
     * @return int
     */
    public function getKelasCountAttribute()
    {
        return $this->kelas()->count();
    }
}