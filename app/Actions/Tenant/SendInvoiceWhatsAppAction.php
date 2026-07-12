<?php

namespace App\Actions\Tenant;

use App\Models\Tenant\Invoice;
use App\Models\Tenant\SystemConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use WallaceMartinss\FilamentEvolution\Services\EvolutionClient;
use WallaceMartinss\FilamentEvolution\Services\WhatsappService;
use Barryvdh\DomPDF\Facade\Pdf;

class SendInvoiceWhatsAppAction
{
    public function execute(Invoice $invoice, ?string $manualPhone = null): void
    {
        // 1. Check if WhatsApp is enabled in settings
        $whatsappEnabled = (bool) SystemConfig::get('sys_whatsapp_enabled', '0');
        if (! $whatsappEnabled) {
            return;
        }

        // 2. Resolve Phone Number
        $phone = $manualPhone ?: $invoice->customer?->phone;
        if (! $phone) {
            return;
        }

        // 3. Format Phone Number with Country Code
        $phone = preg_replace('/\D/', '', $phone); // Remove non-digits
        if (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        }
        if (empty($phone)) {
            return;
        }

        $countryCode = SystemConfig::get('sys_country_code', '');
        $countryCode = preg_replace('/\D/', '', $countryCode);

        // Smart helper: if country code is '2' and it's an Egyptian mobile number, correct it to '20'
        if ($countryCode === '2' && (str_starts_with($phone, '01') || str_starts_with($phone, '1'))) {
            $countryCode = '20';
        }

        // If the number doesn't start with the country code, prepend it
        if ($countryCode && ! str_starts_with($phone, $countryCode)) {
            if (str_starts_with($phone, '0')) {
                $phone = substr($phone, 1);
            }
            $phone = $countryCode.$phone;
        }

        // 4. Get first open WhatsApp instance
        $whatsappService = app(WhatsappService::class);
        $instance = $whatsappService->getConnectedInstances()->first();
        if (! $instance) {
            Log::warning('No connected WhatsApp instance found to send invoice.');
            return;
        }

        // 5. Construct Invoice Message (Caption for the PDF)
        $shopName = SystemConfig::get('sys_shop_name', 'gameOver');
        $dateTimeString = now()->format('Y-m-d h:i A');
        $currency = SystemConfig::get('sys_locale_currency', 'EGP');

        $message = "شكراً لزيارتكم صالة *{$shopName}*! ❤️\n\n";
        $message .= "🧾 *تفاصيل الفاتورة مٌرفقة في الملف*\n";
        $message .= "• *رقم الفاتورة:* #{$invoice->invoice_no}\n";
        $message .= "• *التاريخ:* {$dateTimeString}\n";
        $message .= '• *الصافي المطلوب:* '.number_format((float) $invoice->final_amount, 2)." {$currency}\n";
        $message .= "\nيسعدنا خدمتكم دائماً! 😊";

        // 6. Send via WhatsApp (Generating PDF dynamically)
        try {
            $pdfBase64 = $this->getInvoicePdfBase64($invoice);
            
            // جلب روابط السيرفر من قاعدة بياناتك (أو من ملف .env إذا كانت ثابتة)
            $evolutionUrl = rtrim((string) SystemConfig::get('sys_evolution_api_url', 'http://127.0.0.1:8080'), '/'); 
            $evolutionApiKey = (string) SystemConfig::get('sys_evolution_api_key', 'your_global_apikey'); 

            if ($pdfBase64) {
                // إرسال مباشر لسيرفر Evolution (حل جراحي مضموم 100%)
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'apikey' => $evolutionApiKey,
                    'Content-Type' => 'application/json',
                ])->post("{$evolutionUrl}/message/sendMedia/{$instance->name}", [
                    'number' => $phone,
                    'options' => [
                        'delay' => 1200, // تأخير بسيط ليظهر للعميل كأن أحد يكتب
                        'presence' => 'composing'
                    ],
                    'mediaMessage' => [
                        'mediatype' => 'document',
                        'fileName' => "Invoice_{$invoice->invoice_no}.pdf",
                        'caption' => $message,
                        'media' => $pdfBase64 // نمرر الـ Base64 الصافي هنا
                    ]
                ]);

                if ($response->successful()) {
                    Log::info("WhatsApp invoice PDF sent to {$phone} successfully (Direct HTTP).");
                } else {
                    Log::error("Evolution API failed to send PDF: " . $response->body());
                    // Fallback to text message if Media upload fails for any reason
                    \Illuminate\Support\Facades\Http::withHeaders([
                        'apikey' => $evolutionApiKey,
                        'Content-Type' => 'application/json',
                    ])->post("{$evolutionUrl}/message/sendText/{$instance->name}", [
                        'number' => $phone,
                        'text' => $message
                    ]);
                }
            } else {
                Log::warning("PDF generation failed, falling back to text message.");
                \Illuminate\Support\Facades\Http::withHeaders([
                    'apikey' => $evolutionApiKey,
                    'Content-Type' => 'application/json',
                ])->post("{$evolutionUrl}/message/sendText/{$instance->name}", [
                    'number' => $phone,
                    'text' => $message
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp invoice: '.$e->getMessage());
        }
    }

    /**
     * Generate PDF from Blade view and return as Base64 string for WhatsApp API.
     */
    private function getInvoicePdfBase64(Invoice $invoice): ?string
    {
        try {
            // Load necessary relationships
            $invoice->load([
                'device.type',
                'customer',
                'cashBox',
                'shift',
                'createdBy',
                'playSessionDetails.device',
                'orderSessionDetails.catalogItem',
                'payments.cashBox',
                'payments.createdBy',
            ]);

            $logoPath = (string) SystemConfig::get('print_logo_path', '');
            $logoDataUri = '';

            // Embed logo as base64 securely and check file size
            if ($logoPath && Storage::disk('public')->exists($logoPath)) {
                $fileSize = Storage::disk('public')->size($logoPath);
                
                // لو اللوجو حجمه أكبر من 500 كيلوبايت، هنتجاهل إرفاقه في الفاتورة عشان منبوظش الـ API
                // (500 كيلوبايت = 512000 بايت)
                if ($fileSize < 512000) {
                    $logoContent = Storage::disk('public')->get($logoPath);
                    $logoMime = mime_content_type(Storage::disk('public')->path($logoPath)) ?: 'image/png';
                    $logoDataUri = 'data:' . $logoMime . ';base64,' . base64_encode($logoContent);
                } else {
                    Log::warning("Logo file size is too large ({$fileSize} bytes). Skipping logo in PDF to prevent payload errors.");
                }
            }

            // Generate PDF using DomPDF
            $pdf = Pdf::loadView('tenant.prints.play-invoice', [
                'invoice' => $invoice,
                'logoPath' => $logoDataUri, // Send the Base64 image to Blade
                'footerMessage' => (string) SystemConfig::get('print_footer_message', ''),
                'shopName' => (string) SystemConfig::get('sys_shop_name', 'PS Web'),
            ]);

            // Optimize PDF for A4 or Receipt size (Optional: Default is A4)
            // If it's a thermal receipt, you can adjust the paper size:
            // $pdf->setPaper([0, 0, 226.77, 800], 'portrait'); // Example for 80mm roll

            // Output PDF content
            $pdfContent = $pdf->output();

            // Return strictly formatted base64 with Data URI scheme required by Evolution API
            // return 'data:application/pdf;base64,' . base64_encode($pdfContent);

            return base64_encode($pdfContent);
        } catch (\Exception $e) {
            Log::warning('Failed to generate invoice PDF: ' . $e->getMessage());
            return null;
        }
    }
}