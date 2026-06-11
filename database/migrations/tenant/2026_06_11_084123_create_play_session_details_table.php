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
        Schema::create('play_session_details', function (Blueprint $table) {
            $table->id();
            // الربط بالجدول الأم بالاسم الجديد النظيف
            $table->foreignId('play_session_id')->constrained('play_sessions')->cascadeOnDelete();

            // ربط الحركة بالجهاز الحالي
            $table->foreignId('device_id')->constrained('devices');

            // الحقول التشغيلية المتغيرة (التي ورثناها من جدول الأجهزة القديم)
            $table->string('play_type');       // فردي / مالتي
            $table->string('play_category');   // مفتوح / محدد
            $table->decimal('play_price', 8, 2); // سعر الساعة للحركة دي

            // حقول تتبع الوقت والحسابات
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->integer('period')->nullable();     // المدة بالدقائق
            $table->decimal('cost', 8, 2)->nullable(); // التكلفة المحسوبة للفترة دي

            // حقول الحالة والترتيب (من نسخة الديسكتوب)
            $table->string('status_img')->nullable(); // مغلق / مشغول
            $table->string('status');                 // متاح / مشغول
            $table->integer('order_no')->nullable();
            $table->string('kind')->default('Time');  // Time / Order

            // ربط الكاشير والوردية
            $table->foreignId('user_id')->constrained('users');
            $table->integer('shift_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('play_session_details');
    }
};
