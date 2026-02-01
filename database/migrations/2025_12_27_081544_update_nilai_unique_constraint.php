<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // For SQLite, we need to recreate the table to modify unique constraint
            DB::statement("PRAGMA foreign_keys=OFF");

            // Create new table with updated unique constraint
            DB::statement("
                CREATE TABLE nilai_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    siswa_id INTEGER NOT NULL,
                    mata_pelajaran_id INTEGER NOT NULL,
                    tahun_ajaran_id INTEGER NOT NULL,
                    guru_id INTEGER NOT NULL,
                    capaian_pembelajaran_id INTEGER,
                    semester TEXT,
                    nilai_sumatif_1 INTEGER,
                    nilai_sumatif_2 INTEGER,
                    nilai_sumatif_3 INTEGER,
                    nilai_sumatif_4 INTEGER,
                    nilai_sumatif_5 INTEGER,
                    nilai_akhir NUMERIC,
                    nilai_uts INTEGER,
                    nilai_uas INTEGER,
                    nilai_rapor NUMERIC,
                    predikat TEXT,
                    deskripsi TEXT,
                    created_at DATETIME,
                    updated_at DATETIME,
                    UNIQUE(siswa_id, mata_pelajaran_id, tahun_ajaran_id, capaian_pembelajaran_id, semester),
                    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
                    FOREIGN KEY (mata_pelajaran_id) REFERENCES mata_pelajaran(id) ON DELETE CASCADE,
                    FOREIGN KEY (tahun_ajaran_id) REFERENCES tahun_ajaran(id) ON DELETE CASCADE,
                    FOREIGN KEY (guru_id) REFERENCES guru(id) ON DELETE CASCADE,
                    FOREIGN KEY (capaian_pembelajaran_id) REFERENCES capaian_pembelajaran(id) ON DELETE CASCADE
                )
            ");

            // Copy data from old table
            DB::statement("
                INSERT INTO nilai_new
                SELECT * FROM nilai
            ");

            // Drop old table
            DB::statement("DROP TABLE nilai");

            // Rename new table
            DB::statement("ALTER TABLE nilai_new RENAME TO nilai");

            DB::statement("PRAGMA foreign_keys=ON");
        } else {
            // For MySQL/MariaDB: add new unique first (so FK still has an index), then drop old unique
            Schema::table('nilai', function (Blueprint $table) {
                // Add new unique constraint first (covers leftmost columns for FK)
                $table->unique(['siswa_id', 'mata_pelajaran_id', 'tahun_ajaran_id', 'capaian_pembelajaran_id', 'semester'], 'nilai_unique');
            });
            Schema::table('nilai', function (Blueprint $table) {
                // Now drop old unique (MySQL allows it because new index exists)
                $table->dropUnique(['siswa_id', 'mata_pelajaran_id', 'tahun_ajaran_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // Revert to old unique constraint
            DB::statement("PRAGMA foreign_keys=OFF");

            DB::statement("
                CREATE TABLE nilai_old (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    siswa_id INTEGER NOT NULL,
                    mata_pelajaran_id INTEGER NOT NULL,
                    tahun_ajaran_id INTEGER NOT NULL,
                    guru_id INTEGER NOT NULL,
                    capaian_pembelajaran_id INTEGER,
                    semester TEXT,
                    nilai_sumatif_1 INTEGER,
                    nilai_sumatif_2 INTEGER,
                    nilai_sumatif_3 INTEGER,
                    nilai_sumatif_4 INTEGER,
                    nilai_sumatif_5 INTEGER,
                    nilai_akhir NUMERIC,
                    nilai_uts INTEGER,
                    nilai_uas INTEGER,
                    nilai_rapor NUMERIC,
                    predikat TEXT,
                    deskripsi TEXT,
                    created_at DATETIME,
                    updated_at DATETIME,
                    UNIQUE(siswa_id, mata_pelajaran_id, tahun_ajaran_id),
                    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
                    FOREIGN KEY (mata_pelajaran_id) REFERENCES mata_pelajaran(id) ON DELETE CASCADE,
                    FOREIGN KEY (tahun_ajaran_id) REFERENCES tahun_ajaran(id) ON DELETE CASCADE,
                    FOREIGN KEY (guru_id) REFERENCES guru(id) ON DELETE CASCADE,
                    FOREIGN KEY (capaian_pembelajaran_id) REFERENCES capaian_pembelajaran(id) ON DELETE CASCADE
                )
            ");

            DB::statement("
                INSERT INTO nilai_old
                SELECT * FROM nilai
            ");

            DB::statement("DROP TABLE nilai");
            DB::statement("ALTER TABLE nilai_old RENAME TO nilai");

            DB::statement("PRAGMA foreign_keys=ON");
        } else {
            Schema::table('nilai', function (Blueprint $table) {
                $table->dropUnique('nilai_unique');
                $table->unique(['siswa_id', 'mata_pelajaran_id', 'tahun_ajaran_id']);
            });
        }
    }
};
