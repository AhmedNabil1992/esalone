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
        Schema::create('play_sessions', function (Blueprint $table) {
            $table->id();
            // ربط الجلسة بالجهاز اللي بدأت عليه
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();

            // كود فريد للجلسة (البديل لـ Session_ID القديم مثل DJ9ELE)
            $table->string('session_key')->unique();

            // حالة الجلسة الكبيرة (نشطة حالياً / انتهت وحسبنا فلوسها / ألغيت)
            // active / completed / cancelled
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('play_sessions');
    }
};
