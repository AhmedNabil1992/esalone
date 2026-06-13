<?php

namespace App\Filament\Pages;

use App\Models\Tenant\SystemConfig as Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class SystemConfig extends Page
{
    // protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $title = 'إعدادات النظام';

    protected static ?string $navigationLabel = 'إعدادات النظام';

    protected string $view = 'filament.pages.system-config';

    // مصفوفة لتخزين البيانات مؤقتاً في الـ Form
    public ?array $data = [];

    public function mount(): void
    {
        // سحب القيم وتحويل الـ "1" و "0" لقيم Boolean عشان الـ Toggle يشتغل صح
        $savedSettings = Setting::pluck('value', 'key')->toArray();
        foreach ($savedSettings as $key => $value) {
            if ($value === '1') {
                $savedSettings[$key] = true;
            }
            if ($value === '0') {
                $savedSettings[$key] = false;
            }
        }
        $this->form->fill($savedSettings);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Tabs::make('SystemSettingsTabs')
                    ->tabs([

                        // 1. تاب إعدادات الطباعة
                        Tabs\Tab::make('إعدادات الطباعة')
                            ->icon('heroicon-o-printer')
                            ->schema([
                                Grid::make(2)->schema([
                                    Section::make('خواص التفعيل للطباعة')
                                        ->description('تفعيل خاصية طباعة فواتير المبيعات، تقرير الشيفت، تقرير المخزون')
                                        ->columnSpan(1)
                                        ->schema([
                                            Toggle::make('print_active')->label('تفعيل الطباعة'),
                                            Toggle::make('print_play_invoice')->label('طباعة فاتورة اللعب'),
                                            Toggle::make('print_shift_close')->label('طباعة تقفيل الشيفت'),
                                            Toggle::make('print_direct_sale')->label('طباعة فاتورة البيع المباشر'),
                                            Toggle::make('print_again')->label('طباعة مرة أخرى'),
                                            Toggle::make('print_order_prepare')->label('طباعة تجهيز الأوردرات'),
                                            Toggle::make('print_multi_printers')->label('تعدد الطابعات'),
                                        ]),

                                    Section::make('مظهر الفاتورة واللوجو')
                                        ->columnSpan(1)
                                        ->schema([
                                            TextInput::make('print_footer_message')
                                                ->label('الجملة أسفل فاتورة المبيعات :')
                                                ->maxLength(255),
                                            FileUpload::make('print_logo_path')
                                                ->label('لوجو ( يظهر في فاتورة المبيعات و تقرير تقفيل الشيفت ) .')
                                                ->image()
                                                ->disk('tenant'),
                                        ]),
                                ]),
                            ]),

                        // 2. تاب إعدادات التقريب
                        Tabs\Tab::make('إعدادات التقريب')
                            ->icon('heroicon-o-scale')
                            ->schema([
                                Section::make('* يجب أن يكون تقريب الدقائق من 1 فأكثر , في حالة عدم تفعيل أي من أنواع التبديل يكون الناتج الطبيعي للعملية')
                                    ->schema([
                                        Grid::make(5)->schema([
                                            Toggle::make('round_minutes_active')->label('تفعيل تقريب الدقائق'),
                                            Toggle::make('round_minutes_up')->label('تقريب الدقائق لأعلى'),
                                            // Toggle::make('round_minutes_s_play')->label('تبديل لعب'),
                                            // Toggle::make('round_minutes_s_device')->label('تبديل أجهزة'),
                                            // Toggle::make('round_minutes_active_time')->label('تفعيل وقت'),
                                        ]),
                                        Grid::make(3)->schema([
                                            TextInput::make('round_minutes_new')->label('وقت جديد')->numeric()->minValue(1),
                                            TextInput::make('round_minutes_add')->label('إضافة وقت')->numeric()->minValue(1),
                                            TextInput::make('round_minutes_close')->label('تقفيل الوقت')->numeric()->minValue(1),
                                        ]),
                                    ]),

                                Section::make('يجب أن يكون تقريب المبالغ من 0.1 فأكثر , في حالة عدم تفعيل أي نوع من أنواع التقريب يكون الناتج الطبيعي للعملية')
                                    ->schema([
                                        Grid::make(5)->schema([
                                            Toggle::make('round_money_active')->label('تفعيل تقريب المبالغ'),
                                            Toggle::make('round_money_up')->label('تقريب المبالغ لأعلى'),
                                            // Toggle::make('round_money_s_play')->label('تبديل لعب'),
                                            // Toggle::make('round_money_s_device')->label('تبديل أجهزة'),
                                            // Toggle::make('round_money_active_time')->label('تفعيل وقت'),
                                        ]),
                                        Grid::make(5)->schema([
                                            TextInput::make('round_money_new')->label('وقت جديد')->numeric()->minValue(0.1),
                                            TextInput::make('round_money_add')->label('إضافة وقت')->numeric()->minValue(0.1),
                                            TextInput::make('round_money_s_play')->label('تبديل لعب')->numeric()->minValue(0.1),
                                            TextInput::make('round_money_s_device')->label('تبديل أجهزة')->numeric()->minValue(0.1),
                                            TextInput::make('round_money_close')->label('تقفيل الوقت')->numeric()->minValue(0.1),
                                        ]),
                                        Placeholder::make('note')->content('* ملحوظة : إذا كان التقريب إلي أعلى المبلغ أو الدقائق 2 و التقريب إلي 5 سوف يكون الناتج 5'),
                                    ]),
                            ]),

                        // 3. تاب إعدادات النظام
                        Tabs\Tab::make('إعدادات النظام')
                            ->icon('heroicon-o-cpu-chip')
                            ->schema([
                                Grid::make(3)->schema([
                                    Toggle::make('sys_control_active')->label('تفعيل نظام التحكم'),
                                    Toggle::make('sys_alarm_sound')->label('تفعيل إنذار الصوت'),
                                    Toggle::make('sys_qr_customer')->label('تفعيل QR للعميل'),
                                ]),
                                Grid::make(3)->schema([
                                    // TextInput::make('sys_iot_ip')->label('IP Address :')->placeholder('192.168.1.1'),
                                    // Toggle::make('sys_iot_qr_protection')->label('حماية الـ QR'),
                                    TextInput::make('sys_shop_name')->label('اسم المحل :')->required(),
                                    TextInput::make('sys_tax_percent')->label('نسبة الضريبة :')->numeric(),
                                    TextInput::make('sys_service_percent')->label('نسبة الخدمة :')->numeric(),
                                ]),
                                Grid::make(3)->schema([
                                    Toggle::make('sys_tax_on_direct_sales')->label('تطبيق الضريبة على : فواتير البيع المباشر'),
                                    Toggle::make('sys_tax_on_play_invoices')->label('تطبيق الضريبة على : فواتير اللعب'),
                                    Toggle::make('sys_service_on_play_invoices')->label('تطبيق نسبة الخدمة على قيمة : فواتير اللعب'),
                                ]),
                                Grid::make(1)->schema([
                                    // TextInput::make('sys_font_size_inventory')->label('حجم الخط ( مجموعات الصنف , الأصناف ) :')->numeric(),
                                    TextInput::make('sys_lock_device_time')->label('وقت سماح لتقفيل الجهاز :')->numeric(),
                                    Select::make('sys_locale_currency')->label('العملة المحلية :')->options([
                                        'EGP' => 'جنيه مصري',
                                        'USD' => 'دولار أمريكي',
                                        'EUR' => 'يورو',
                                        'SAR' => 'ريال سعودي',
                                        'AED' => 'درهم إماراتي',
                                        // أضف المزيد من العملات حسب الحاجة
                                    ]),
                                ]),

                                Section::make('حساب تقفيل الشيفت')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Section::make('الإيرادات')->schema([
                                                Toggle::make('shift_inc_play')->label('إيراد اللعب'),
                                                Toggle::make('shift_inc_drinks')->label('إيراد المشروبات'),
                                                Toggle::make('shift_inc_customer_balance')->label('إضافة رصيد العملاء'),
                                                Toggle::make('shift_inc_other')->label('إيرادات أخرى'),
                                                Toggle::make('shift_inc_tax')->label('ضريبة'),
                                                Toggle::make('shift_inc_service')->label('خدمة'),
                                            ]),
                                            Section::make('المصروفات')->schema([
                                                Toggle::make('shift_exp_customer_discount')->label('خصم رصيد العملاء'),
                                                Toggle::make('shift_exp_customer_credit')->label('مديونية العملاء'),
                                                Toggle::make('shift_exp_expenses')->label('مصروفات'),
                                                Toggle::make('shift_exp_discount')->label('خصم'),
                                                Toggle::make('shift_exp_purchasing')->label('مشتريات'),
                                            ]),
                                        ]),
                                    ]),

                                Section::make('إعدادات غلق البلاي ستيشن')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            Toggle::make('sys_close_send_freq')->label('تفعيل معدل الإرسال'),
                                            TextInput::make('sys_close_duration')->label('المدة :')->numeric(),
                                            TextInput::make('sys_close_order_count')->label('عدد الطلبات :')->numeric(),
                                        ]),
                                    ]),
                            ]),

                        // 4. تاب إعدادات الإشعارات
                        Tabs\Tab::make('إعدادات الإشعارات')
                            ->icon('heroicon-o-bell')
                            ->schema([
                                Toggle::make('notify_active')->label('تفعيل الإشعارات'),
                                TextInput::make('notify_email')
                                    ->label('البريد الإلكتروني :')
                                    ->email()
                                    ->helperText('يرجى وضع علامة ( , ) بين كل بريد إلكتروني'),
                                TextInput::make('notify_email_title')
                                    ->label('عنوان البريد الإلكتروني :')
                                    ->placeholder('مثال : تقرير الشيفت عن فرع رقم 1'),

                                Section::make('تقارير سجلات الإرسال الفوري للبريد')
                                    ->schema([
                                        Grid::make(4)->schema([
                                            Toggle::make('notify_rep_play')->label('تقرير سجل اللعب'),
                                            Toggle::make('notify_rep_sales')->label('تقرير سجل المبيعات'),
                                            Toggle::make('notify_rep_discounts')->label('تقرير سجل الخصومات'),
                                            Toggle::make('notify_rep_purchases')->label('تقرير سجل المشتريات'),
                                            Toggle::make('notify_rep_expenses')->label('تقرير سجل المصروفات'),
                                            Toggle::make('notify_rep_other_inc')->label('تقرير الإيرادات الأخرى'),
                                            Toggle::make('notify_rep_service')->label('تقرير سجل الخدمة'),
                                            Toggle::make('notify_rep_tax')->label('تقرير سجل الضريبة'),
                                            Toggle::make('notify_rep_customers')->label('تقرير سجل العملاء'),
                                            Toggle::make('notify_stock_alert')->label('تنبيه المخزون'),
                                        ]),
                                    ]),

                                // Grid::make(4)->schema([
                                //     Select::make('notify_smtp_secure')->label('Secure Socket')->options(['ssl' => 'SSL', 'tls' => 'TLS']),
                                //     TextInput::make('notify_smtp_port')->label('Port')->numeric()->placeholder('465'),
                                //     Select::make('notify_smtp_protocol')->label('Protocol')->options(['smtp' => 'SMTP', 'mail' => 'Mail']),
                                //     Actions::make([
                                //         Action::make('test_email')
                                //             ->label('تجربة الإرسال')
                                //             ->button()
                                //             ->color('warning'),
                                //     ])->verticallyAlignEnd(),
                                // ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($state as $key => $value) {
            // تحويل قيم الـ Boolean لـ "1" أو "0" للنظافة في الحفظ جوه الـ DB
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Notification::make()->title('تم حفظ كافة إعدادات الصالة بنجاح')->success()->send();
    }
}
