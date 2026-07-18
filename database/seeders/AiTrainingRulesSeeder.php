<?php

namespace Database\Seeders;

use App\Models\AiTrainingRule;
use Illuminate\Database\Seeder;

class AiTrainingRulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AiTrainingRule::truncate();

        AiTrainingRule::create([
            'pattern' => '(حجز النهارده|حجوزات النهارده|حجز اليوم|حجوزات اليوم)',
            'sql_query' => "SELECT r.start_time, r.end_time, d.name AS device_name, c.name AS customer_name, r.status FROM reservations r JOIN devices d ON r.device_id = d.id JOIN customers c ON r.customer_id = c.id WHERE DATE(r.start_time) = CURRENT_DATE() AND r.status != 'cancelled' ORDER BY r.start_time ASC",
            'description' => 'حجوزات اليوم الحالي',
        ]);

        AiTrainingRule::create([
            'pattern' => '(حجز|حجوزات|المحجوزة)',
            'sql_query' => "SELECT r.start_time, r.end_time, d.name AS device_name, c.name AS customer_name, r.status FROM reservations r JOIN devices d ON r.device_id = d.id JOIN customers c ON r.customer_id = c.id WHERE r.start_time >= NOW() AND r.status != 'cancelled' ORDER BY r.start_time ASC LIMIT 10",
            'description' => 'الحجوزات القادمة العامة',
        ]);

        AiTrainingRule::create([
            'pattern' => '(أرباح|الارباح|ربح|الربح|خسارة|خسائر)',
            'sql_query' => "SELECT ( (SELECT COALESCE(SUM(final_amount), 0) FROM invoices WHERE status = 'posted' AND MONTH(issued_at) = MONTH(CURRENT_DATE()) AND YEAR(issued_at) = YEAR(CURRENT_DATE())) - (SELECT COALESCE(SUM(amount), 0) FROM expense_histories WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())) - (SELECT COALESCE(SUM(total_amount), 0) FROM purchases WHERE status = 'posted' AND MONTH(purchased_at) = MONTH(CURRENT_DATE()) AND YEAR(purchased_at) = YEAR(CURRENT_DATE())) ) AS net_profit",
            'description' => 'الأرباح الشهرية الحالية',
        ]);

        AiTrainingRule::create([
            'pattern' => '(الشيفت الحالي|الوردية الحالية|الشيفت النهارده|الشيفت اليوم|الشيفت عامل ايه)',
            'sql_query' => "SELECT SUM(final_amount) as current_shift_revenue FROM invoices WHERE status = 'posted' AND shift_id = (SELECT id FROM shifts WHERE status = 'open' ORDER BY id DESC LIMIT 1)",
            'description' => 'إيراد الشيفت الحالي المفتوح',
        ]);

        AiTrainingRule::create([
            'pattern' => '(الشيفت اللي فات|الوردية السابقة|الشيفت السابق)',
            'sql_query' => "SELECT shift_no, expected_amount as expected_closing, actual_amount, status FROM shifts WHERE status = 'close' ORDER BY shift_no DESC LIMIT 1",
            'description' => 'تفاصيل الشيفت السابق المغلق',
        ]);

        AiTrainingRule::create([
            'pattern' => '(العملاء ليهم فلوس كام|أرصدة العملاء|ارصدة العملاء|العملاء ليهم كام|رصيد العملاء)',
            'sql_query' => 'SELECT name, phone, balance FROM customers WHERE balance != 0 ORDER BY balance DESC',
            'description' => 'أرصدة العملاء ومديونياتهم',
        ]);
    }
}
