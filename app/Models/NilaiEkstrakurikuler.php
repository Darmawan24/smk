<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * NilaiEkstrakurikuler Model
 * 
 * Represents extracurricular grades
 */
class NilaiEkstrakurikuler extends Model
{
    use HasFactory;

    protected $table = 'nilai_ekstrakurikuler';

    protected $fillable = [
        'siswa_id',
        'ekstrakurikuler_id',
        'tahun_ajaran_id',
        'predikat',
        'keterangan',
    ];

    /**
     * Get the siswa.
     */
    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    /**
     * Get the ekstrakurikuler.
     */
    public function ekstrakurikuler()
    {
        return $this->belongsTo(Ekstrakurikuler::class);
    }

    /**
     * Get the tahun ajaran.
     */
    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    /**
     * Get predikat description.
     *
     * @return string
     */
    public function getPredikatDescriptionAttribute()
    {
        $descriptions = [
            'A' => 'Sangat Baik',
            'B' => 'Baik',
            'C' => 'Cukup',
            'D' => 'Perlu Bimbingan',
        ];

        return $descriptions[$this->predikat] ?? $this->predikat;
    }
}