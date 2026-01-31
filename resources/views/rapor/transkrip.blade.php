<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Transkrip Hasil Belajar - {{ $siswa->nama_lengkap ?? 'Siswa' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #333; padding: 6px 8px; }
        th { background: #eee; }
        .header-dua-kolom { display: table; width: 100%; margin-bottom: 12px; }
        .header-kiri, .header-kanan { display: table-cell; width: 50%; vertical-align: top; }
        .header-kiri table, .header-kanan table { width: auto; }
        .header-kiri td:first-child { width: 100px; }
        .header-kanan td:first-child { width: 90px; }
        table.identitas-tabel { border: none !important; }
        table.identitas-tabel th, table.identitas-tabel td { border: none !important; }
        table.identitas-tabel td { padding: 2px 8px 2px 0; }
        .judul-transkrip { text-align: center; font-size: 12pt; font-weight: bold; margin: 16px 0 12px; }
        .kelompok-label { margin-top: 10px; font-weight: bold; font-size: 10pt; }
        hr { border: none; border-top: 1px solid #333; margin: 12px 0; }
        .ttd { margin-top: 32px; }
        .ttd table { width: 100%; border: none; }
        .ttd td { border: none; padding: 4px; vertical-align: top; }
        .ttd .nama { font-weight: bold; margin-top: 4px; }
        .ttd-tanggal { margin-bottom: 8px; }
        .ttd-left { width: 50%; }
        .ttd-right { width: 50%; text-align: left; }
        .dotted-line { border-bottom: 1px dotted #333; min-width: 180px; display: inline-block; margin-top: 48px; }
    </style>
</head>
<body>
    <div class="header-dua-kolom">
        <div class="header-kiri">
            <table class="identitas-tabel">
                <tr><td>Nama</td><td>: {{ $siswa->nama_lengkap ?? '-' }}</td></tr>
                <tr><td>NIS/NISN</td><td>: {{ $siswa->nis ?? '-' }} / {{ $siswa->nisn ?? '-' }}</td></tr>
                <tr><td>Nama Sekolah</td><td>: {{ strtoupper($nama_sekolah ?? '-') }}</td></tr>
                <tr><td>Alamat</td><td>: {{ $alamat_sekolah ?? '-' }}</td></tr>
            </table>
        </div>
        <div class="header-kanan">
            <table class="identitas-tabel">
                <tr><td>Kelas</td><td>: {{ $kelas_display ?? (optional($siswa->kelas)->nama_kelas ?? '-') }}</td></tr>
                <tr><td>Fase</td><td>: {{ $fase ?? 'E' }}</td></tr>
                <tr><td>Periode</td><td>: {{ $periode ?? '-' }}</td></tr>
                <tr><td>Semester</td><td>: {{ $semester_romawi ?? $semester ?? '-' }}</td></tr>
                <tr><td>Tahun Pelajaran</td><td>: {{ $tahun_pelajaran_label ?? (optional($tahun_ajaran)->tahun ?? '-') }}</td></tr>
            </table>
        </div>
    </div>

    <hr>

    <div class="judul-transkrip">TRANSKRIP HASIL BELAJAR</div>

    @php
        $kelompokLabel = [
            'umum' => 'Mata Pelajaran Umum',
            'kejuruan' => 'Mata Pelajaran Kejuruan',
            'muatan_lokal' => 'Muatan Lokal',
        ];
    @endphp

    @foreach ($nilai_by_kelompok ?? [] as $kelompok => $items)
        @php $itemsList = is_array($items) ? $items : (method_exists($items, 'all') ? $items->all() : []); @endphp
        @if (!empty($itemsList))
            <div class="kelompok-label">{{ $kelompokLabel[$kelompok] ?? $kelompok }}</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 8%;" class="text-center">No</th>
                        <th style="width: 72%;">Mata Pelajaran</th>
                        <th style="width: 20%;" class="text-center">Nilai Akhir</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($itemsList as $idx => $row)
                        @php
                            $namaMapel = is_array($row) ? ($row['nama_mapel'] ?? '-') : (optional($row->mataPelajaran ?? null)->nama_mapel ?? '-');
                            $nilaiAkhir = is_array($row) ? ($row['nilai_rapor'] ?? '-') : ($row->nilai_rapor ?? '-');
                            if ($namaMapel === '' || $namaMapel === null) { $namaMapel = '-'; }
                            if ($nilaiAkhir === '' || $nilaiAkhir === null) { $nilaiAkhir = '-'; }
                        @endphp
                        <tr>
                            <td class="text-center">{{ $idx + 1 }}</td>
                            <td>{{ $namaMapel }}</td>
                            <td class="text-center">{{ $nilaiAkhir }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach

    @if (empty($nilai_by_kelompok) || (is_array($nilai_by_kelompok) && count(array_filter($nilai_by_kelompok)) === 0))
        <p style="margin: 8px 0; padding: 8px; background: #fff3cd; border: 1px solid #ffc107; font-size: 9pt;">Nilai untuk periode ini belum diisi.</p>
    @endif

    <div class="ttd">
        <table>
            <tr>
                <td class="ttd-left"></td>
                <td class="ttd-right">
                    <div class="ttd-tanggal">Cianjur, {{ isset($tanggal_rapor) ? (is_object($tanggal_rapor) && method_exists($tanggal_rapor, 'translatedFormat') ? $tanggal_rapor->translatedFormat('d F Y') : \Carbon\Carbon::parse($tanggal_rapor)->locale('id')->translatedFormat('d F Y')) : \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y') }}</div>
                    Wali Kelas,<br><br>
                    <span class="dotted-line"></span><br>
                    <div class="nama">{{ optional($wali_kelas)->nama_lengkap ?? '-' }}</div>
                    NUPTK : {{ optional($wali_kelas)->nuptk ?? '-' }}
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
