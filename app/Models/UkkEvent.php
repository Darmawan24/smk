<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UkkEvent extends Model
{
    protected $table = 'ukk_events';

    protected $fillable = [
        'tahun_ajaran_id',
        'jurusan_id',
        'kelas_id',
        'nama_du_di',
        'tanggal_ujian',
        'penguji_internal_id',
        'penguji_eksternal',
    ];

    protected $casts = [
        'tanggal_ujian' => 'date',
    ];

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function jurusan()
    {
        return $this->belongsTo(Jurusan::class);
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    public function pengujiInternal()
    {
        return $this->belongsTo(Guru::class, 'penguji_internal_id');
    }

    public function ukk()
    {
        return $this->hasMany(Ukk::class, 'ukk_event_id');
    }
}
