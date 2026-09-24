<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\SystemConfig as Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // 1. إعدادات الطباعة
            ['key' => 'print_active', 'value' => '0', 'group' => 'print'],
            ['key' => 'print_play_invoice', 'value' => '1', 'group' => 'print'],
            ['key' => 'print_shift_close', 'value' => '1', 'group' => 'print'],
            ['key' => 'print_direct_sale', 'value' => '1', 'group' => 'print'],
            ['key' => 'print_again', 'value' => '1', 'group' => 'print'],
            ['key' => 'print_order_prepare', 'value' => '1', 'group' => 'print'],
            ['key' => 'print_multi_printers', 'value' => '1', 'group' => 'print'],
            ['key' => 'sys_invoice_printer', 'value' => null, 'group' => 'print'],
            ['key' => 'sys_order_printer', 'value' => null, 'group' => 'print'],
            ['key' => 'print_footer_message', 'value' => 'نتمنى أن نراك مرة أخرى .', 'group' => 'print'],
            ['key' => 'print_logo_path', 'value' => null, 'group' => 'print'],

            // 2. إعدادات التقريب
            ['key' => 'round_minutes_active', 'value' => '1', 'group' => 'round'],
            ['key' => 'round_minutes_up', 'value' => '1', 'group' => 'round'],
            ['key' => 'round_minutes_new', 'value' => '1', 'group' => 'round'],
            ['key' => 'round_minutes_add', 'value' => '1', 'group' => 'round'],
            ['key' => 'round_minutes_close', 'value' => '1', 'group' => 'round'],

            ['key' => 'round_money_active', 'value' => '1', 'group' => 'round'],
            ['key' => 'round_money_up', 'value' => '1', 'group' => 'round'],
            ['key' => 'round_money_new', 'value' => '1', 'group' => 'round'],
            ['key' => 'round_money_add', 'value' => '1', 'group' => 'round'],
            ['key' => 'round_money_s_play', 'value' => '1', 'group' => 'round'],
            ['key' => 'round_money_s_device', 'value' => '1', 'group' => 'round'],
            ['key' => 'round_money_close', 'value' => '1', 'group' => 'round'],

            // 3. إعدادات النظام
            ['key' => 'sys_control_active', 'value' => '1', 'group' => 'system'],
            ['key' => 'sys_iot_connection_type', 'value' => 'bridge', 'group' => 'system'],
            ['key' => 'sys_alarm_sound', 'value' => '1', 'group' => 'system'],
            ['key' => 'sys_qr_customer', 'value' => '1', 'group' => 'system'],
            ['key' => 'sys_shop_name', 'value' => 'PlayStation', 'group' => 'system'],
            ['key' => 'sys_tax_percent', 'value' => '14', 'group' => 'system'],
            ['key' => 'sys_service_percent', 'value' => '12', 'group' => 'system'],
            ['key' => 'sys_tax_on_direct_sales', 'value' => '0', 'group' => 'system'],
            ['key' => 'sys_tax_on_play_invoices', 'value' => '0', 'group' => 'system'],
            ['key' => 'sys_service_on_play_invoices', 'value' => '0', 'group' => 'system'],
            ['key' => 'sys_lock_device_time', 'value' => '15', 'group' => 'system'],
            ['key' => 'sys_cancel_order_time', 'value' => '5', 'group' => 'system'],
            ['key' => 'sys_locale_currency', 'value' => 'EGP', 'group' => 'system'],
            ['key' => 'sys_default_cashbox', 'value' => '1', 'group' => 'system'],
            ['key' => 'sys_default_inventory', 'value' => '1', 'group' => 'system'],
            ['key' => 'sys_system_time_zone', 'value' => 'Africa/Cairo', 'group' => 'system'],

            // نقاط العملاء
            ['key' => 'sys_customer_points_ratio', 'value' => '1', 'group' => 'system'],
            ['key' => 'sys_customer_points_redeem_ratio', 'value' => '1', 'group' => 'system'],
            ['key' => 'sys_customer_points_type', 'value' => 'all', 'group' => 'system'],

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

            // 4. إعدادات الإشعارات
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

            // 5. الواتساب والتنبيهات
            ['key' => 'sys_whatsapp_enabled', 'value' => '0', 'group' => 'whatsapp'],
            ['key' => 'sys_country_code', 'value' => '2', 'group' => 'whatsapp'],
            ['key' => 'sys_booking_alert_time', 'value' => '30', 'group' => 'whatsapp'],

            // 6. إعدادات الصفحة الرئيسية
            ['key' => 'landing_title', 'value' => 'أهلاً بكم في صالة بلاي ستيشن', 'group' => 'landing'],
            ['key' => 'landing_description', 'value' => null, 'group' => 'landing'],
            ['key' => 'landing_phone', 'value' => null, 'group' => 'landing'],
            ['key' => 'landing_whatsapp', 'value' => null, 'group' => 'landing'],
            ['key' => 'landing_address', 'value' => null, 'group' => 'landing'],
            ['key' => 'landing_facebook', 'value' => null, 'group' => 'landing'],
            ['key' => 'landing_instagram', 'value' => null, 'group' => 'landing'],
            ['key' => 'landing_about', 'value' => null, 'group' => 'landing'],
            ['key' => 'landing_gallery', 'value' => null, 'group' => 'landing'],
        ];

        foreach ($settings as $setting) {
            if ($setting['key'] === 'sys_iot_connection_type') {
                Setting::firstOrCreate(
                    ['key' => $setting['key']],
                    ['value' => $setting['value'], 'group' => $setting['group']]
                );

                continue;
            }

            Setting::updateOrCreate(
                [
                    'key' => $setting['key'],
                ],
                [
                    'value' => $setting['value'],
                    'group' => $setting['group'],
                ]
            );
        }
    }
}
