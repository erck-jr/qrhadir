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
            $table->boolean('print_only_name_type')->default(false);
            $table->string('text_color')->default('#000000');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_templates', function (Blueprint $table) {
            $table->dropColumn(['print_only_name_type', 'text_color']);
        });
    }
};
