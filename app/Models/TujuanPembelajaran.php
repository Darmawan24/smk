<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * TujuanPembelajaran Model
 * 
 * Represents learning objectives under capaian pembelajaran
 */
class TujuanPembelajaran extends Model
{
    use HasFactory;

    protected $table = 'tujuan_pembelajaran';

    protected $fillable = [
        'capaian_pembelajaran_id',
        'kode_tp',
        'deskripsi',
    ];

    /**
     * Get the capaian pembelajaran.
     */
    public function capaianPembelajaran()
    {
        return $this->belongsTo(CapaianPembelajaran::class);
    }

    /**
     * Get the mata pelajaran through capaian pembelajaran.
     */
    public function mataPelajaran()
    {
        return $this->hasOneThrough(
            MataPelajaran::class,
            CapaianPembelajaran::class,
            'id',
            'id',
            'capaian_pembelajaran_id',
            'mata_pelajaran_id'
        );
    }

    /**
     * Get the full code (CP + TP).
     *
     * @return string
     */
    public function getFullCodeAttribute()
    {
        return $this->capaianPembelajaran->kode_cp . '.' . $this->kode_tp;
    }
}