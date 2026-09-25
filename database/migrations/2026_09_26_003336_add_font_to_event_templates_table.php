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
        Schema::table('event_templates', function (Blueprint $table) {
            $table->string('font')->nullable()->default('Nunito/Nunito-Bold.ttf');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_templates', function (Blueprint $table) {
            $table->dropColumn('font');
        });
    }
};
