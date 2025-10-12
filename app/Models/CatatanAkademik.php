<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * CatatanAkademik Model
 * 
 * Represents academic notes from homeroom teacher
 */
class CatatanAkademik extends Model
{
    use HasFactory;

    protected $table = 'catatan_akademik';

    protected $fillable = [
        'siswa_id',
        'tahun_ajaran_id',
        'wali_kelas_id',
        'catatan',
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
     * Get the wali kelas who wrote the note.
     */
    public function waliKelas()
    {
        return $this->belongsTo(User::class, 'wali_kelas_id');
    }

    /**
     * Get shortened catatan for preview.
     *
     * @param  int  $length
     * @return string
     */
    public function getPreviewAttribute($length = 100)
    {
        return strlen($this->catatan) > $length 
            ? substr($this->catatan, 0, $length) . '...'
            : $this->catatan;
    }

    /**
     * Get word count of catatan.
     *
     * @return int
     */
    public function getWordCountAttribute()
    {
        return str_word_count($this->catatan);
    }
}