<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Guru Model
 * 
 * Represents teacher data
 */
class Guru extends Model
{
    use HasFactory;

    protected $table = 'guru';

    protected $fillable = [
        'user_id',
        'nip',
        'nama_lengkap',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'agama',
        'alamat',
        'no_hp',
        'pendidikan_terakhir',
        'bidang_studi',
        'tanggal_masuk',
        'status',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tanggal_masuk' => 'date',
    ];

    /**
     * Get the user account.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the jadwal pelajaran.
     */
    public function jadwalPelajaran()
    {
        return $this->hasMany(JadwalPelajaran::class);
    }

    /**
     * Get the nilai that this guru inputs.
     */
    public function nilai()
    {
        return $this->hasMany(Nilai::class);
    }

    /**
     * Get ekstrakurikuler where this guru is pembina.
     */
    public function ekstrakurikuler()
    {
        return $this->hasMany(Ekstrakurikuler::class, 'pembina_id');
    }

    /**
     * Get PKL where this guru is pembimbing.
     */
    public function pklAsPembimbing()
    {
        return $this->hasMany(Pkl::class, 'pembimbing_sekolah_id');
    }

    /**
     * Get P5 where this guru is koordinator.
     */
    public function p5AsKoordinator()
    {
        return $this->hasMany(P5::class, 'koordinator_id');
    }

    /**
     * Get UKK where this guru is penguji internal.
     */
    public function ukkAsPenguji()
    {
        return $this->hasMany(Ukk::class, 'penguji_internal_id');
    }

    /**
     * Scope a query to only include active guru.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }

    /**
     * Get the mata pelajaran that this guru teaches.
     */
    public function mataPelajaran()
    {
        return $this->belongsToMany(MataPelajaran::class, 'jadwal_pelajaran')
                    ->distinct();
    }

    /**
     * Get the kelas that this guru teaches.
     */
    public function kelas()
    {
        return $this->belongsToMany(Kelas::class, 'jadwal_pelajaran')
                    ->distinct();
    }

    /**
     * Check if guru is wali kelas.
     *
     * @return bool
     */
    public function isWaliKelas()
    {
        return $this->user->kelasAsWali()->exists();
    }

    /**
     * Get the kelas where this guru is wali kelas.
     *
     * @return \App\Models\Kelas|null
     */
    public function getKelasAsWaliAttribute()
    {
        return $this->user->kelasAsWali()->first();
    }

    /**
     * Get years of service.
     *
     * @return int
     */
    public function getYearsOfServiceAttribute()
    {
        return $this->tanggal_masuk->diffInYears(now());
    }
}