<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('drive_files', function (Blueprint $table) {
            $table->text('view_url')->nullable()->after('parent_id');
            $table->text('download_url')->nullable()->after('view_url');
            $table->text('preview_url')->nullable()->after('download_url');
            $table->index('user_id'); // Añadir índice para mejorar rendimiento de queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drive_files', function (Blueprint $table) {
            $table->dropColumn(['view_url', 'download_url', 'preview_url']);
            $table->dropIndex(['user_id']);
        });
    }
};
