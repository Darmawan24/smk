<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Kelas;
use App\Models\Kehadiran;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait untuk membangun data legger (payload + download Excel).
 * Dipakai oleh Admin\CetakRaporController dan WaliKelas\CetakLeggerController.
 */
trait BuildsLeggerData
{
    /**
     * Build payload legger untuk satu kelas (untuk JSON API dan Excel).
     *
     * @param  Request  $request
     * @param  Kelas  $kelas
     * @return array{kelas?: \App\Models\Kelas, tahun_ajaran?: mixed, mata_pelajaran?: \Illuminate\Support\Collection, legger?: array, message?: string}
     */
    protected function getLeggerPayload(Request $request, Kelas $kelas): array
    {
        $tahunAjaranId = $request->get('tahun_ajaran_id');

        if (! $tahunAjaranId) {
            $tahunAjaran = TahunAjaran::where('is_active', true)->first();
            if (! $tahunAjaran) {
                return ['message' => 'Tahun ajaran aktif tidak ditemukan'];
            }
            $tahunAjaranId = $tahunAjaran->id;
        }

        $siswa = $kelas->siswa()->where('status', 'aktif')->orderBy('nama_lengkap')->get();

        $mataPelajaran = $kelas->mataPelajaran()
            ->where('mata_pelajaran.is_active', true)
            ->orderBy('nama_mapel')
            ->get();

        $nilai = Nilai::whereIn('siswa_id', $siswa->pluck('id'))
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->with(['siswa.user', 'mataPelajaran'])
            ->get();

        if ($mataPelajaran->isEmpty() && $nilai->isNotEmpty()) {
            $mapelIds = $nilai->pluck('mata_pelajaran_id')->unique()->filter()->values();
            $mataPelajaran = MataPelajaran::whereIn('id', $mapelIds)
                ->where('is_active', true)
                ->orderBy('nama_mapel')
                ->get();
        }

        $legger = [];
        foreach ($siswa as $s) {
            $siswaNilai = $nilai->where('siswa_id', $s->id)
                ->groupBy('mata_pelajaran_id')
                ->map(fn ($nilaiGroup) => $nilaiGroup->first());

            $nilaiMap = [];
            foreach ($siswaNilai as $mapelId => $nilaiItem) {
                $nilaiMap[$mapelId] = $nilaiItem;
            }

            $legger[] = [
                'siswa' => $s->load('user'),
                'nilai' => $nilaiMap,
            ];
        }

        return [
            'kelas' => $kelas->load('jurusan'),
            'tahun_ajaran' => TahunAjaran::find($tahunAjaranId),
            'mata_pelajaran' => $mataPelajaran,
            'legger' => $legger,
        ];
    }

    /**
     * Build dan return response download Excel legger.
     *
     * @param  array  $payload  From getLeggerPayload
     * @param  Kelas  $kelas
     * @return Response
     */
    protected function downloadLeggerAsExcel(array $payload, Kelas $kelas): Response
    {
        $kelasModel = $kelas->load('jurusan');
        $tahunAjaran = $payload['tahun_ajaran'] ?? null;
        $mataPelajaran = $payload['mata_pelajaran'] ?? collect();
        $legger = $payload['legger'] ?? [];

        $tahunAjaranId = is_array($tahunAjaran) ? ($tahunAjaran['id'] ?? null) : ($tahunAjaran->id ?? null);
        $siswaIds = array_map(function ($item) {
            $s = $item['siswa'] ?? $item;

            return is_array($s) ? ($s['id'] ?? null) : $s->id;
        }, $legger);
        $siswaIds = array_filter($siswaIds);

        $kehadiranBySiswa = [];
        if ($tahunAjaranId && ! empty($siswaIds)) {
            $kehadiran = Kehadiran::whereIn('siswa_id', $siswaIds)
                ->where('tahun_ajaran_id', $tahunAjaranId)
                ->get();
            foreach ($kehadiran as $k) {
                $sid = is_array($k->siswa_id) ? $k->siswa_id : $k->siswa_id;
                $kehadiranBySiswa[$sid] = $k;
            }
        }

        $namaSekolah = config('app.school_name', 'SMK');
        $namaKelas = $kelasModel->nama_kelas ?? $kelas->nama_kelas;
        $tahunLabel = is_array($tahunAjaran) ? ($tahunAjaran['tahun'] ?? '') : ($tahunAjaran->tahun ?? '');
        $semester = is_array($tahunAjaran) ? ($tahunAjaran['semester'] ?? '1') : ($tahunAjaran->semester ?? '1');
        $semesterText = ($semester == '2') ? 'GENAP' : 'GANJIL';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Legger Nilai');

        $numMapel = count($mataPelajaran);
        $lastCol = 4 + $numMapel + 3;
        $colLetterLast = Coordinate::stringFromColumnIndex($lastCol);

        $title1 = 'LEGER NILAI RAPOR SISWA TAHUN PELAJARAN ' . $tahunLabel . ' ' . $semesterText;
        $sheet->setCellValue('A1', $title1);
        $sheet->mergeCells("A1:{$colLetterLast}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'SEKOLAH');
        $sheet->setCellValue('C2', ': ' . strtoupper($namaSekolah));
        $sheet->getStyle('A2')->getFont()->setBold(true);

        $sheet->setCellValue('A3', 'Kelas');
        $sheet->setCellValue('C3', ': ' . $namaKelas);
        $sheet->getStyle('A3')->getFont()->setBold(true);

        $headerRow = 5;
        $sheet->setCellValue('A' . $headerRow, 'NO');
        $sheet->setCellValue('B' . $headerRow, 'NAMA SISWA');
        $sheet->setCellValue('C' . $headerRow, 'NISN');
        $sheet->setCellValue('D' . $headerRow, 'NIS');
        $col = 5;
        foreach ($mataPelajaran as $mapel) {
            $letter = Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($letter . $headerRow, $mapel['nama_mapel'] ?? $mapel->nama_mapel ?? '');
            $col++;
        }
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . $headerRow, 'Sakit');
        $col++;
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . $headerRow, 'Izin');
        $col++;
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . $headerRow, 'Alpa');
        $headerRange = "A{$headerRow}:{$colLetterLast}{$headerRow}";
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');

        $dataRow = $headerRow + 1;
        foreach ($legger as $index => $item) {
            $siswa = $item['siswa'] ?? $item;
            $nilaiMap = $item['nilai'] ?? [];
            $siswaId = is_array($siswa) ? ($siswa['id'] ?? null) : $siswa->id;
            $kehadiran = $kehadiranBySiswa[$siswaId] ?? null;

            $sheet->setCellValue('A' . $dataRow, $index + 1);
            $sheet->setCellValue('B' . $dataRow, $siswa['nama_lengkap'] ?? $siswa->nama_lengkap ?? '');
            $sheet->setCellValue('C' . $dataRow, $siswa['nisn'] ?? $siswa->nisn ?? '');
            $sheet->setCellValue('D' . $dataRow, $siswa['nis'] ?? $siswa->nis ?? '');
            $col = 5;
            foreach ($mataPelajaran as $mapel) {
                $mapelId = $mapel['id'] ?? $mapel->id ?? null;
                $nilaiItem = $nilaiMap[$mapelId] ?? null;
                $nilai = null;
                if ($nilaiItem) {
                    $nilai = $nilaiItem['nilai_akhir'] ?? $nilaiItem['nilai_rapor'] ?? $nilaiItem->nilai_akhir ?? $nilaiItem->nilai_rapor ?? null;
                }
                $letter = Coordinate::stringFromColumnIndex($col);
                $sheet->setCellValue($letter . $dataRow, $nilai !== null ? (float) $nilai : '');
                $col++;
            }
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . $dataRow, $kehadiran ? (int) $kehadiran->sakit : 0);
            $col++;
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . $dataRow, $kehadiran ? (int) $kehadiran->izin : 0);
            $col++;
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . $dataRow, $kehadiran ? (int) $kehadiran->tanpa_keterangan : 0);
            $dataRange = "A{$dataRow}:{$colLetterLast}{$dataRow}";
            $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $dataRow++;
        }

        foreach (range(1, $lastCol) as $c) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }

        $filename = 'Legger-Nilai-' . preg_replace('/[^a-zA-Z0-9\-_]/', '-', $namaKelas) . '.xlsx';

        $writer = new XlsxWriter($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'legger_');
        $writer->save($tempFile);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $writer);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
