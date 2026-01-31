<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\P5;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Trait untuk membangun data rapor P5 (payload detail + data PDF).
 * Dipakai oleh Admin\CetakRaporController dan WaliKelas\CetakRaporP5Controller.
 */
trait BuildsRaporP5Data
{
    /**
     * Build payload detail rapor P5 (dari DB, dipakai untuk API dan PDF).
     *
     * @param  Request  $request
     * @param  Siswa  $siswa
     * @return array{siswa: \App\Models\Siswa, p5_projects: array, tahun_ajaran_id: mixed}
     */
    protected function getDetailHasilP5Payload(Request $request, Siswa $siswa): array
    {
        $siswa->load(['user', 'kelas.jurusan']);
        $tahunAjaranId = $request->tahun_ajaran_id;
        $tahun = $request->tahun;

        $nilaiP5Query = $siswa->nilaiP5()->with(['p5.tahunAjaran', 'p5.koordinator.user']);
        if ($tahunAjaranId) {
            $nilaiP5Query->whereHas('p5', function ($q) use ($tahunAjaranId) {
                $q->where('tahun_ajaran_id', $tahunAjaranId);
            });
        } elseif ($tahun !== null && $tahun !== '') {
            $nilaiP5Query->whereHas('p5', function ($q) use ($tahun) {
                $q->whereHas('tahunAjaran', function ($qt) use ($tahun) {
                    $qt->where('tahun', $tahun);
                });
            });
        }
        $nilaiP5 = $nilaiP5Query->get();
        $p5Ids = $nilaiP5->pluck('p5_id')->unique()->values();

        $catatanByP5 = DB::table('p5_siswa')
            ->where('siswa_id', $siswa->id)
            ->whereIn('p5_id', $p5Ids)
            ->pluck('catatan_proses', 'p5_id')
            ->toArray();

        $p5List = P5::with(['koordinator', 'tahunAjaran', 'kelompok.guru', 'kelompok.siswa'])
            ->whereIn('id', $p5Ids)
            ->get()
            ->keyBy('id');

        $nilaiBySub = $nilaiP5->whereNotNull('sub_elemen')->keyBy(function ($n) {
            return $n->p5_id . '|' . $n->sub_elemen;
        });

        $deskripsiReferensi = $this->getP5SubElemenDeskripsi();

        $p5Projects = [];
        foreach ($p5Ids as $p5Id) {
            $p5 = $p5List->get($p5Id);
            if (! $p5) {
                continue;
            }
            $elemenSubRaw = $p5->elemen_sub;
            $elemenSub = is_array($elemenSubRaw) ? $elemenSubRaw : (is_string($elemenSubRaw) ? json_decode($elemenSubRaw, true) ?? [] : []);
            $subWithPredikat = [];
            foreach ($elemenSub as $es) {
                $es = is_array($es) ? $es : (array) $es;
                $sub = trim((string) ($es['sub_elemen'] ?? ''));
                if ($sub === '') {
                    continue;
                }
                $key = $p5->id . '|' . $sub;
                $nilaiRow = $nilaiBySub->get($key);
                $deskripsiTujuan = isset($es['deskripsi_tujuan']) ? trim((string) $es['deskripsi_tujuan']) : '';
                if ($deskripsiTujuan === '') {
                    $deskripsiTujuan = $deskripsiReferensi[$sub] ?? $this->findDeskripsiByKey($deskripsiReferensi, $sub);
                }
                $deskripsiTujuan = ($deskripsiTujuan !== null && trim((string) $deskripsiTujuan) !== '')
                    ? trim((string) $deskripsiTujuan)
                    : $sub;
                $subWithPredikat[] = [
                    'elemen' => $es['elemen'] ?? '-',
                    'sub_elemen' => $sub,
                    'deskripsi_tujuan' => $deskripsiTujuan,
                    'predikat' => $nilaiRow ? $nilaiRow->nilai : null,
                    'predikat_label' => $nilaiRow ? $nilaiRow->nilai_description : '-',
                ];
            }
            $fasilitator = $p5->koordinator ? $p5->koordinator->nama_lengkap : null;
            foreach ($p5->kelompok ?? [] as $kel) {
                $siswaIds = $kel->siswa->pluck('id')->toArray();
                if (in_array($siswa->id, $siswaIds) && $kel->guru) {
                    $fasilitator = $kel->guru->nama_lengkap;
                    break;
                }
            }
            $p5Projects[] = [
                'id' => $p5->id,
                'tema' => $p5->tema,
                'judul' => $p5->judul,
                'deskripsi' => $p5->deskripsi,
                'dimensi' => $p5->dimensi ?? '-',
                'koordinator' => $p5->koordinator ? ['nama' => $p5->koordinator->nama_lengkap] : null,
                'fasilitator_nama' => $fasilitator,
                'tahun_ajaran' => $p5->tahunAjaran ? [
                    'tahun' => $p5->tahunAjaran->tahun,
                    'semester' => $p5->tahunAjaran->semester,
                    'label' => "{$p5->tahunAjaran->tahun} - Semester {$p5->tahunAjaran->semester}",
                ] : null,
                'elemen_sub' => $subWithPredikat,
                'catatan_proses' => $catatanByP5[$p5->id] ?? null,
            ];
        }

        return [
            'siswa' => $siswa,
            'p5_projects' => $p5Projects,
            'tahun_ajaran_id' => $tahunAjaranId,
        ];
    }

    /**
     * Build data for rapor P5 PDF view.
     *
     * @param  array  $payload  From detailHasilP5 response
     * @param  Siswa  $siswa
     * @return array
     */
    protected function buildRaporP5PdfData(array $payload, Siswa $siswa): array
    {
        $bulanIndo = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $now = now();
        $tanggalCetak = $now->format('d') . ' ' . $bulanIndo[(int) $now->format('n')] . ' ' . $now->format('Y');
        $waktuUnduh = $now->format('H.i') . ' ' . $now->format('d') . ' ' . $bulanIndo[(int) $now->format('n')] . ' ' . $now->format('Y');

        $tahunAjaranAktif = TahunAjaran::where('is_active', true)->first();
        $tahunPelajaran = $tahunAjaranAktif ? $tahunAjaranAktif->tahun : '';

        $tingkat = $siswa->kelas->tingkat ?? null;
        $fase = 'E';
        if ($tingkat !== null && $tingkat !== '') {
            $t = (string) $tingkat;
            if (in_array($t, ['11', '12', 'XI', 'xI', 'XII', 'xII'])) {
                $fase = 'F';
            } elseif (in_array($t, ['10', 'X', 'x'])) {
                $fase = 'E';
            }
        }

        return [
            'nama_sekolah' => config('app.school_name', 'SMKS Progresia Cianjur'),
            'tahun_pelajaran' => $tahunPelajaran,
            'fase' => $fase,
            'siswa' => $payload['siswa'] ?? $siswa,
            'tanggal_cetak' => $tanggalCetak,
            'waktu_unduh' => $waktuUnduh,
            'p5_projects' => $payload['p5_projects'] ?? [],
        ];
    }

    /**
     * Load referensi deskripsi sub-elemen P5 (dari file config).
     *
     * @return array<string, string>
     */
    protected function getP5SubElemenDeskripsi(): array
    {
        $path = base_path('config/p5_sub_elemen_deskripsi.php');
        if (file_exists($path)) {
            $ref = require $path;
            if (is_array($ref) && ! empty($ref)) {
                return $ref;
            }
        }
        $ref = config('p5_sub_elemen_deskripsi');

        return is_array($ref) ? $ref : [];
    }

    /**
     * Cari deskripsi dengan key yang mirip (normalisasi spasi, case-insensitive).
     *
     * @param  array<string, string>  $referensi
     * @param  string  $sub
     * @return string|null
     */
    protected function findDeskripsiByKey(array $referensi, string $sub): ?string
    {
        $normalize = function (string $s): string {
            $s = trim(preg_replace('/\s+/', ' ', $s));

            return mb_strtolower($s);
        };
        $keyNorm = $normalize($sub);
        foreach ($referensi as $k => $v) {
            if ($normalize($k) === $keyNorm) {
                return $v;
            }
        }

        return null;
    }
}
