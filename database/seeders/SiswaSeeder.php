<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Siswa;
use App\Models\User;
use App\Models\Kelas;

class SiswaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $siswaData = [
            [
                'email' => 'andi.pratama@siswa.smk.sch.id',
                'nis' => '2024001',
                'nama_lengkap' => 'Andi Pratama',
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Cianjur',
                'tanggal_lahir' => '2007-01-15',
                'agama' => 'Islam',
                'alamat' => 'Jl. Raya Cianjur No. 45',
                'no_hp' => '081234567891',
                'nama_ayah' => 'Budi Pratama',
                'nama_ibu' => 'Siti Pratama',
                'pekerjaan_ayah' => 'Petani',
                'pekerjaan_ibu' => 'Ibu Rumah Tangga',
                'no_hp_ortu' => '081234567901',
                'kelas' => 'X RPL 1',
            ],
            [
                'email' => 'sari.dewi@siswa.smk.sch.id',
                'nis' => '2024002',
                'nama_lengkap' => 'Sari Dewi',
                'jenis_kelamin' => 'P',
                'tempat_lahir' => 'Bandung',
                'tanggal_lahir' => '2007-03-20',
                'agama' => 'Islam',
                'alamat' => 'Jl. Pahlawan No. 12',
                'no_hp' => '081234567892',
                'nama_ayah' => 'Ahmad Dewi',
                'nama_ibu' => 'Rina Dewi',
                'pekerjaan_ayah' => 'Pedagang',
                'pekerjaan_ibu' => 'Guru',
                'no_hp_ortu' => '081234567902',
                'kelas' => 'X RPL 1',
            ],
            [
                'email' => 'rizki.maulana@siswa.smk.sch.id',
                'nis' => '2024003',
                'nama_lengkap' => 'Rizki Maulana',
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => '2007-05-10',
                'agama' => 'Islam',
                'alamat' => 'Jl. Merdeka No. 33',
                'no_hp' => '081234567893',
                'nama_ayah' => 'Dedi Maulana',
                'nama_ibu' => 'Yuli Maulana',
                'pekerjaan_ayah' => 'Karyawan Swasta',
                'pekerjaan_ibu' => 'Wiraswasta',
                'no_hp_ortu' => '081234567903',
                'kelas' => 'X TKJ 1',
            ],
            [
                'email' => 'fitri.handayani@siswa.smk.sch.id',
                'nis' => '2024004',
                'nama_lengkap' => 'Fitri Handayani',
                'jenis_kelamin' => 'P',
                'tempat_lahir' => 'Bogor',
                'tanggal_lahir' => '2007-07-25',
                'agama' => 'Islam',
                'alamat' => 'Jl. Sudirman No. 18',
                'no_hp' => '081234567894',
                'nama_ayah' => 'Hadi Handayani',
                'nama_ibu' => 'Lina Handayani',
                'pekerjaan_ayah' => 'PNS',
                'pekerjaan_ibu' => 'Dokter',
                'no_hp_ortu' => '081234567904',
                'kelas' => 'X MM 1',
            ],
            [
                'email' => 'dedi.kurniawan@siswa.smk.sch.id',
                'nis' => '2024005',
                'nama_lengkap' => 'Dedi Kurniawan',
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Sukabumi',
                'tanggal_lahir' => '2007-09-12',
                'agama' => 'Islam',
                'alamat' => 'Jl. Veteran No. 22',
                'no_hp' => '081234567895',
                'nama_ayah' => 'Asep Kurniawan',
                'nama_ibu' => 'Neni Kurniawan',
                'pekerjaan_ayah' => 'Buruh',
                'pekerjaan_ibu' => 'Petani',
                'no_hp_ortu' => '081234567905',
                'kelas' => 'X OTKP 1',
            ],
        ];

        $kelas = Kelas::all()->keyBy('nama_kelas');

        foreach ($siswaData as $data) {
            $user = User::where('email', $data['email'])->first();
            $kelasId = $kelas[$data['kelas']]->id ?? null;

            if ($user) {
                Siswa::create([
                    'user_id' => $user->id,
                    'nis' => $data['nis'],
                    'nisn' => null, // Will be filled later
                    'nama_lengkap' => $data['nama_lengkap'],
                    'jenis_kelamin' => $data['jenis_kelamin'],
                    'tempat_lahir' => $data['tempat_lahir'],
                    'tanggal_lahir' => $data['tanggal_lahir'],
                    'agama' => $data['agama'],
                    'alamat' => $data['alamat'],
                    'no_hp' => $data['no_hp'],
                    'nama_ayah' => $data['nama_ayah'],
                    'nama_ibu' => $data['nama_ibu'],
                    'pekerjaan_ayah' => $data['pekerjaan_ayah'],
                    'pekerjaan_ibu' => $data['pekerjaan_ibu'],
                    'no_hp_ortu' => $data['no_hp_ortu'],
                    'kelas_id' => $kelasId,
                    'tanggal_masuk' => '2024-07-15',
                    'status' => 'aktif',
                ]);
            }
        }

        // Create demo siswa
        $demoUser = User::where('email', 'siswa@demo.com')->first();
        $demoKelas = $kelas['X RPL 1']->id ?? null;

        if ($demoUser) {
            Siswa::create([
                'user_id' => $demoUser->id,
                'nis' => 'DEMO001',
                'nisn' => null,
                'nama_lengkap' => 'Demo Siswa',
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => '2007-01-01',
                'agama' => 'Islam',
                'alamat' => 'Jl. Demo No. 1',
                'no_hp' => '081234567890',
                'nama_ayah' => 'Demo Ayah',
                'nama_ibu' => 'Demo Ibu',
                'pekerjaan_ayah' => 'Demo Job',
                'pekerjaan_ibu' => 'Demo Job',
                'no_hp_ortu' => '081234567890',
                'kelas_id' => $demoKelas,
                'tanggal_masuk' => '2024-07-15',
                'status' => 'aktif',
            ]);
        }
    }
}