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

        // MySQL 1553: unique is used by FK on dimensi_id. Drop FK first (look up actual constraint name), then drop unique, then re-add FK.
        $fkName = DB::selectOne("
            SELECT CONSTRAINT_NAME AS name
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'nilai_p5' AND COLUMN_NAME = 'dimensi_id' AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        if ($fkName && $fkName->name) {
            Schema::table('nilai_p5', function (Blueprint $table) use ($fkName) {
                $table->dropForeign($fkName->name);
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

        Schema::table('nilai_p5', function (Blueprint $table) {
            $table->foreign('dimensi_id')->references('id')->on('dimensi_p5')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        $fkName = DB::selectOne("
            SELECT CONSTRAINT_NAME AS name
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'nilai_p5' AND COLUMN_NAME = 'dimensi_id' AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        if ($fkName && $fkName->name) {
            Schema::table('nilai_p5', function (Blueprint $table) use ($fkName) {
                $table->dropForeign($fkName->name);
            });
        }

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
            $table->foreign('dimensi_id')->references('id')->on('dimensi_p5')->onDelete('cascade');
        });

        Schema::table('nilai_p5', function (Blueprint $table) {
            $table->dropColumn('sub_elemen');
        });
    }
};
