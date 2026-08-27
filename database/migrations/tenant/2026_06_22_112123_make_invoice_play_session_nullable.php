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
        Schema::table('invoices', function (Blueprint $table): void {
            // 1. مسح المفتاح الأجنبي مؤقتاً لتجنب قيود MySQL
            $table->dropForeign(['play_session_id']);

            // 2. تعديل العمود ليقبل قيمة Null
            $table->unsignedBigInteger('play_session_id')->nullable()->change();

            // 3. إعادة إنشاء المفتاح الأجنبي
            $table->foreign('play_session_id')
                ->references('id')
                ->on('play_sessions')
                ->cascadeOnDelete(); // أو nullOnDelete() حسب البيزنس لوجيك بتاعك
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            // 1. مسح المفتاح الأجنبي مؤقتاً
            $table->dropForeign(['play_session_id']);

            // 2. إرجاع العمود ليرفض الـ Null (الحالة الأصلية)
            $table->unsignedBigInteger('play_session_id')->nullable(false)->change();

            // 3. إعادة إنشاء المفتاح الأجنبي
            $table->foreign('play_session_id')
                ->references('id')
                ->on('play_sessions')
                ->cascadeOnDelete();
        });
    }
};
