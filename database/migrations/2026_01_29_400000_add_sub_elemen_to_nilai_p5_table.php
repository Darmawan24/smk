<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('nilai_p5', 'sub_elemen')) {
            Schema::table('nilai_p5', function (Blueprint $table) {
                $table->string('sub_elemen', 500)->nullable()->after('dimensi_id');
            });
        }

        // Add index on dimensi_id first so FK still has an index after we drop the unique (MySQL 1553)
        $indexExists = collect(DB::select("SHOW INDEX FROM nilai_p5 WHERE Key_name = 'nilai_p5_dimensi_id_index'"))->isNotEmpty();
        if (!$indexExists) {
            Schema::table('nilai_p5', function (Blueprint $table) {
                $table->index('dimensi_id');
            });
        }

        $oldUniqueExists = collect(DB::select("SHOW INDEX FROM nilai_p5 WHERE Key_name = 'nilai_p5_siswa_id_p5_id_dimensi_id_unique'"))->isNotEmpty();
        if ($oldUniqueExists) {
            Schema::table('nilai_p5', function (Blueprint $table) {
                $table->dropUnique(['siswa_id', 'p5_id', 'dimensi_id']);
            });
        }

        Schema::table('nilai_p5', function (Blueprint $table) {
            $table->unsignedBigInteger('dimensi_id')->nullable()->change();
        });

        $newUniqueExists = collect(DB::select("SHOW INDEX FROM nilai_p5 WHERE Key_name = 'nilai_p5_siswa_id_p5_id_sub_elemen_unique'"))->isNotEmpty();
        if (!$newUniqueExists) {
            Schema::table('nilai_p5', function (Blueprint $table) {
                $table->unique(['siswa_id', 'p5_id', 'sub_elemen']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('nilai_p5', function (Blueprint $table) {
            $table->dropUnique(['siswa_id', 'p5_id', 'sub_elemen']);
        });

        Schema::table('nilai_p5', function (Blueprint $table) {
            $table->unsignedBigInteger('dimensi_id')->nullable(false)->change();
        });

        Schema::table('nilai_p5', function (Blueprint $table) {
            $table->unique(['siswa_id', 'p5_id', 'dimensi_id']);
        });

        Schema::table('nilai_p5', function (Blueprint $table) {
            $table->dropIndex(['dimensi_id']);
        });

        Schema::table('nilai_p5', function (Blueprint $table) {
            $table->dropColumn('sub_elemen');
        });
    }
};
