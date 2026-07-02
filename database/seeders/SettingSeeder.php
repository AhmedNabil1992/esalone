<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\SystemConfig as Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // 1. إعدادات الطباعة (الصورة الأولى)
            ['key' => 'print_active', 'value' => '0', 'group' => 'print'],
            ['key' => 'print_play_invoice', 'value' => '1', 'group' => 'print'],
            ['key' => 'print_shift_close', 'value' => '1', 'group' => 'print'],
            ['key' => 'print_direct_sale', 'value' => '1', 'group' => 'print'],
            ['key' => 'print_again', 'value' => '1', 'group' => 'print'],
            ['key' => 'print_order_prepare', 'value' => '1', 'group' => 'print'],
            ['key' => 'print_multi_printers', 'value' => '1', 'group' => 'print'],
            ['key' => 'print_footer_message', 'value' => 'نتمنى أن نراك مرة أخرى .', 'group' => 'print'],
            ['key' => 'print_logo_path', 'value' => null, 'group' => 'print'],

            // 2. إعدادات التقريب (الصورة الثانية)
            ['key' => 'round_minutes_active', 'value' => '1', 'group' => 'round'],
            ['key' => 'round_minutes_up', 'value' => '1', 'group' => 'round'],

            ['key' => 'round_minutes_new', 'value' => '1', 'group' => 'round'],
            ['key' => 'round_minutes_add', 'value' => '1', 'group' => 'round'],
            // ['key' => 'round_minutes_s_play', 'value' => '1', 'group' => 'round'],
            // ['key' => 'round_minutes_s_device', 'value' => '1', 'group' => 'round'],
            ['key' => 'round_minutes_close', 'value' => '1', 'group' => 'round'],

            ['key' => 'round_money_active', 'value' => '1', 'group' => 'round'],
            ['key' => 'round_money_up', 'value' => '1', 'group' => 'round'],

            ['key' => 'round_money_new', 'value' => '1', 'group' => 'round'],
            ['key' => 'round_money_add', 'value' => '1', 'group' => 'round'],
            ['key' => 'round_money_s_play', 'value' => '1', 'group' => 'round'],
            ['key' => 'round_money_s_device', 'value' => '1', 'group' => 'round'],
            ['key' => 'round_money_close', 'value' => '1', 'group' => 'round'],

            // 3. إعدادات النظام (الصورة الثالثة)
            ['key' => 'sys_control_active', 'value' => '1', 'group' => 'system'],
            ['key' => 'sys_alarm_sound', 'value' => '1', 'group' => 'system'],
            ['key' => 'sys_qr_customer', 'value' => '1', 'group' => 'system'],
            // ['key' => 'sys_iot_ip', 'value' => '192.168.1.1', 'group' => 'system'],
            // ['key' => 'sys_iot_qr_protection', 'value' => '0', 'group' => 'system'],
            ['key' => 'sys_shop_name', 'value' => 'PlayStation', 'group' => 'system'],
            ['key' => 'sys_tax_percent', 'value' => '14', 'group' => 'system'],
            ['key' => 'sys_service_percent', 'value' => '12', 'group' => 'system'],
            ['key' => 'sys_tax_on_direct_sales', 'value' => '0', 'group' => 'system'],
            ['key' => 'sys_tax_on_play_invoices', 'value' => '0', 'group' => 'system'],
            ['key' => 'sys_service_on_play_invoices', 'value' => '0', 'group' => 'system'],
            // ['key' => 'sys_font_size_inventory', 'value' => '15', 'group' => 'system'],
            ['key' => 'sys_lock_device_time', 'value' => '15', 'group' => 'system'],
            ['key' => 'sys_default_cashbox', 'value' => '1', 'group' => 'system'],
            ['key' => 'sys_default_inventory', 'value' => '1', 'group' => 'system'],

            // حسابات تقفيل الشفت - الإيرادات
            ['key' => 'shift_inc_play', 'value' => '1', 'group' => 'system'],
            ['key' => 'shift_inc_drinks', 'value' => '1', 'group' => 'system'],
            ['key' => 'shift_inc_customer_balance', 'value' => '1', 'group' => 'system'],
            ['key' => 'shift_inc_other', 'value' => '1', 'group' => 'system'],
            ['key' => 'shift_inc_tax', 'value' => '1', 'group' => 'system'],
            ['key' => 'shift_inc_service', 'value' => '1', 'group' => 'system'],
            // حسابات تقفيل الشفت - المصروفات
            ['key' => 'shift_exp_customer_discount', 'value' => '1', 'group' => 'system'],
            ['key' => 'shift_exp_customer_credit', 'value' => '1', 'group' => 'system'],
            ['key' => 'shift_exp_expenses', 'value' => '1', 'group' => 'system'],
            ['key' => 'shift_exp_discount', 'value' => '1', 'group' => 'system'],
            ['key' => 'shift_exp_purchasing', 'value' => '1', 'group' => 'system'],

            // إعدادات غلق البلاي ستيشن
            ['key' => 'sys_close_send_freq', 'value' => '1', 'group' => 'system'],
            ['key' => 'sys_close_duration', 'value' => '2', 'group' => 'system'],
            ['key' => 'sys_close_order_count', 'value' => '15', 'group' => 'system'],

            // 4. إعدادات الإشعارات (الصورة الرابعة)
            ['key' => 'notify_active', 'value' => '0', 'group' => 'notification'],
            ['key' => 'notify_email', 'value' => '', 'group' => 'notification'],
            ['key' => 'notify_email_title', 'value' => 'تقرير الشيفت عن الفرع الأول', 'group' => 'notification'],
            ['key' => 'notify_rep_play', 'value' => '1', 'group' => 'notification'],
            ['key' => 'notify_rep_sales', 'value' => '1', 'group' => 'notification'],
            ['key' => 'notify_rep_discounts', 'value' => '1', 'group' => 'notification'],
            ['key' => 'notify_rep_purchases', 'value' => '1', 'group' => 'notification'],
            ['key' => 'notify_rep_expenses', 'value' => '1', 'group' => 'notification'],
            ['key' => 'notify_rep_other_inc', 'value' => '1', 'group' => 'notification'],
            ['key' => 'notify_rep_service', 'value' => '1', 'group' => 'notification'],
            ['key' => 'notify_rep_tax', 'value' => '1', 'group' => 'notification'],
            ['key' => 'notify_rep_customers', 'value' => '1', 'group' => 'notification'],
            ['key' => 'notify_stock_alert', 'value' => '1', 'group' => 'notification'],
            // ['key' => 'notify_smtp_secure', 'value' => 'ssl', 'group' => 'notification'],
            // ['key' => 'notify_smtp_port', 'value' => '465', 'group' => 'notification'],
            // ['key' => 'notify_smtp_protocol', 'value' => 'smtp', 'group' => 'notification'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                // 1. مصفوفة البحث: بندور بالـ key ده الأول في الجدول
                [
                    'key' => $setting['key'],
                ],
                // 2. مصفوفة البيانات: لو لقى الـ key هيحدث الـ value والـ group، ولو ملعقوش هيكريت سطر جديد بيهم
                [
                    'value' => $setting['value'],
                    'group' => $setting['group'],
                ]
            );
        }
    }
}
