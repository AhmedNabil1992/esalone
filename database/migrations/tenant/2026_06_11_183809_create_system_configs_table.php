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
        Schema::create('system_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // اسم الإعداد مثل: tax_value أو shift_play
            $table->text('value')->nullable(); // القيمة وبتشيل نصوص، أرقام، أو قيم True/False
            $table->string('group')->nullable(); // لتقسيم الإعدادات (عام / ضرائب / شفتات) لتسهيل العرض
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_configs');
    }
};
