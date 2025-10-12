<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * DimensiP5 Model
 * 
 * Represents dimensions of Profil Pelajar Pancasila
 */
class DimensiP5 extends Model
{
    use HasFactory;

    protected $table = 'dimensi_p5';

    protected $fillable = [
        'nama_dimensi',
        'deskripsi',
    ];

    /**
     * Get the nilai P5 for this dimensi.
     */
    public function nilaiP5()
    {
        return $this->hasMany(NilaiP5::class, 'dimensi_id');
    }

    /**
     * Get P5 projects that assess this dimensi.
     */
    public function p5Projects()
    {
        return $this->belongsToMany(P5::class, 'nilai_p5', 'dimensi_id', 'p5_id')
                    ->distinct();
    }

    /**
     * Get siswa assessed in this dimensi.
     */
    public function siswa()
    {
        return $this->belongsToMany(Siswa::class, 'nilai_p5', 'dimensi_id', 'siswa_id')
                    ->distinct();
    }

    /**
     * Get the short name of dimensi.
     *
     * @return string
     */
    public function getShortNameAttribute()
    {
        $words = explode(' ', $this->nama_dimensi);
        $shortName = '';
        
        foreach ($words as $word) {
            $shortName .= strtoupper(substr($word, 0, 1));
        }
        
        return $shortName;
    }
}