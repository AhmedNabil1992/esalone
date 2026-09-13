<?php

namespace App\Actions\Tenant;

use App\Models\Tenant\Invoice;
use App\Models\Tenant\SystemConfig;
use App\Support\Tenant\PublicInvoiceToken;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use WallaceMartinss\FilamentEvolution\Services\EvolutionClient;
use WallaceMartinss\FilamentEvolution\Services\WhatsappService;

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

        $countryCode = SystemConfig::get('sys_country_code', '2');
        $countryCode = preg_replace('/\D/', '', (string) $countryCode);
        if (empty($countryCode)) {
            $countryCode = '2';
        }

        // Smart helper: if country code is '2' and it's an Egyptian mobile number, correct it to '20'
        if ($countryCode === '2' && (str_starts_with($phone, '01') || (strlen($phone) === 10 && str_starts_with($phone, '1')))) {
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

        // 5. Construct Invoice Message
        $shopName = SystemConfig::get('sys_shop_name', 'gameOver');
        $dateTimeString = now()->format('Y-m-d h:i A');
        $currency = SystemConfig::get('sys_locale_currency', 'EGP');

        $token = app(PublicInvoiceToken::class)->generate($invoice->getKey());
        $domain = tenant('domains')?->first()?->domain;
        $publicUrl = $domain
            ? tenant_route($domain, 'tenant.invoices.public-view', ['invoice' => $invoice->id, 'token' => $token])
            : route('tenant.invoices.public-view', ['invoice' => $invoice->id, 'token' => $token]);

        $message = "شكراً لزيارتكم صالة *{$shopName}*! ❤️\n\n";
        $message .= "🧾 *تفاصيل الفاتورة مٌرفقة*\n";
        $message .= "• *رقم الفاتورة:* #{$invoice->invoice_no}\n";
        $message .= "• *التاريخ والوقت:* {$dateTimeString}\n";
        $message .= '• *الصافي المطلوب:* '.number_format((float) $invoice->final_amount, 2)." {$currency}\n\n";
        $message .= "🌐 *رابط عرض الفاتورة أونلاين:*\n{$publicUrl}\n\n";
        $message .= 'يسعدنا خدمتكم دائماً! 😊';

        // 6. Send via WhatsApp (Try Image first, fallback to PDF)
        try {
            $evolutionClient = app(EvolutionClient::class);

            // Attempt Image Screenshot (User preferred format)
            $imageBase64 = $this->getInvoiceImageBase64($invoice);
            if ($imageBase64) {
                try {
                    $evolutionClient->sendImage(
                        $instance->name,
                        $phone,
                        $imageBase64,
                        $message,
                        [
                            'delay' => 1200,
                            'presence' => 'composing',
                        ]
                    );
                    Log::info("WhatsApp invoice image sent to {$phone} successfully via EvolutionClient.");

                    return;
                } catch (\Exception $e) {
                    Log::error('Evolution API failed to send Image: '.$e->getMessage());
                }
            }

            // Graceful fallback to PDF (Pure PHP DomPDF, works everywhere)
            Log::info("Falling back to PDF format for invoice #{$invoice->invoice_no}");
            $pdfBase64 = $this->getInvoicePdfBase64($invoice);
            if ($pdfBase64) {
                try {
                    $evolutionClient->sendDocument(
                        $instance->name,
                        $phone,
                        $pdfBase64,
                        "Invoice_{$invoice->invoice_no}.pdf",
                        $message,
                        [
                            'delay' => 1200,
                            'presence' => 'composing',
                        ]
                    );
                    Log::info("WhatsApp invoice PDF sent to {$phone} successfully via EvolutionClient fallback.");

                    return;
                } catch (\Exception $e) {
                    Log::error('Evolution API failed to send PDF: '.$e->getMessage());
                }
            }

            // Final fallback to text message
            Log::warning('Both image and PDF generation failed, sending plain text message via EvolutionClient.');
            $evolutionClient->sendText(
                $instance->name,
                $phone,
                $message,
                [
                    'delay' => 1200,
                    'presence' => 'composing',
                ]
            );

        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp invoice: '.$e->getMessage());
        }
    }

    /**
     * Generate PNG image from Blade view using headless Chrome/Chromium and return as Base64 string.
     */
    private function getInvoiceImageBase64(Invoice $invoice): ?string
    {
        try {
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
                if ($fileSize < 512000) {
                    $logoContent = Storage::disk('public')->get($logoPath);
                    $logoMime = mime_content_type(Storage::disk('public')->path($logoPath)) ?: 'image/png';
                    $logoDataUri = 'data:'.$logoMime.';base64,'.base64_encode($logoContent);
                }
            }

            $html = view('tenant.prints.play-invoice', [
                'invoice' => $invoice,
                'logoPath' => $logoDataUri,
                'footerMessage' => (string) SystemConfig::get('print_footer_message', ''),
                'shopName' => (string) SystemConfig::get('sys_shop_name', 'PS Web'),
            ])->render();

            // Strip scripts to prevent window.print() from firing
            $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);

            // Allow body to expand freely for full-page screenshot and fix RTL viewport alignment
            $html = str_replace(
                '</style>',
                'html,body{overflow:visible!important;height:auto!important;direction:ltr!important;}div.receipt{direction:rtl!important;}</style>',
                $html
            );

            // Locate installed Chrome/Chromium (works on Windows & Linux/Ubuntu)
            $chromePaths = array_filter([
                config('services.chrome.path'),
                'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
                'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
                '/usr/bin/google-chrome',
                '/usr/bin/chromium-browser',
                '/usr/bin/chromium',
                '/usr/bin/chrome',
            ]);

            $chromePath = null;
            foreach ($chromePaths as $path) {
                if (file_exists($path)) {
                    $chromePath = $path;
                    break;
                }
            }

            if (! $chromePath) {
                Log::warning('Chrome/Chromium not found; skipping invoice screenshot.');

                return null;
            }

            // Write rendered HTML to a temp file
            $uniqueId = uniqid('inv_', true);
            $tempDir = sys_get_temp_dir();
            $htmlFile = $tempDir.DIRECTORY_SEPARATOR.$uniqueId.'.html';
            $pngFile = $tempDir.DIRECTORY_SEPARATOR.$uniqueId.'.png';

            file_put_contents($htmlFile, $html);

            $fileUrl = 'file:///'.ltrim(str_replace('\\', '/', $htmlFile), '/');

            // Headless screenshot command
            $cmd = sprintf(
                '"%s" --headless=new --no-sandbox --disable-gpu --disable-software-rasterizer --ignore-certificate-errors --hide-scrollbars --window-size=600,10000 --virtual-time-budget=5000 --screenshot="%s" "%s" 2>&1',
                $chromePath,
                $pngFile,
                $fileUrl
            );

            exec($cmd, $cmdOutput, $exitCode);
            @unlink($htmlFile);

            if (file_exists($pngFile)) {
                $imageData = $this->cropAndEncodeScreenshot($pngFile);
                @unlink($pngFile);

                return $imageData;
            }

            Log::warning('Chrome headless screenshot failed (exit '.$exitCode.'): '.implode(' | ', $cmdOutput));

        } catch (\Exception $e) {
            Log::warning('Failed to generate invoice screenshot: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Crop bottom whitespace from a PNG using GD and return base64-encoded result.
     */
    private function cropAndEncodeScreenshot(string $pngFile): ?string
    {
        if (! function_exists('imagecreatefrompng')) {
            return base64_encode(file_get_contents($pngFile));
        }

        $image = @imagecreatefrompng($pngFile);
        if (! $image) {
            return base64_encode(file_get_contents($pngFile));
        }

        $width = imagesx($image);
        $height = imagesy($image);

        // Scan from bottom to find the last non-white row
        $lastContentRow = 0;
        for ($y = $height - 1; $y >= 0; $y--) {
            $rowIsWhite = true;
            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorat($image, $x, $y);
                $r = ($color >> 16) & 0xFF;
                $g = ($color >> 8) & 0xFF;
                $b = $color & 0xFF;
                if ($r < 248 || $g < 248 || $b < 248) {
                    $rowIsWhite = false;
                    break;
                }
            }
            if (! $rowIsWhite) {
                $lastContentRow = $y;
                break;
            }
        }

        // Add 20px bottom padding, cap at image height
        $cropHeight = min($lastContentRow + 20, $height);
        if ($cropHeight < 10) {
            imagedestroy($image);

            return base64_encode(file_get_contents($pngFile));
        }

        $cropped = imagecrop($image, ['x' => 0, 'y' => 0, 'width' => $width, 'height' => $cropHeight]);
        imagedestroy($image);

        if (! $cropped) {
            return base64_encode(file_get_contents($pngFile));
        }

        ob_start();
        imagepng($cropped);
        $pngData = ob_get_clean();
        imagedestroy($cropped);

        return base64_encode($pngData);
    }

    /**
     * Generate PDF from Blade view and return as Base64 string for WhatsApp API.
     */
    private function getInvoicePdfBase64(Invoice $invoice): ?string
    {
        try {
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
                if ($fileSize < 512000) {
                    $logoContent = Storage::disk('public')->get($logoPath);
                    $logoMime = mime_content_type(Storage::disk('public')->path($logoPath)) ?: 'image/png';
                    $logoDataUri = 'data:'.$logoMime.';base64,'.base64_encode($logoContent);
                }
            }

            // Generate PDF using DomPDF
            $pdf = Pdf::loadView('tenant.prints.play-invoice', [
                'invoice' => $invoice,
                'logoPath' => $logoDataUri,
                'footerMessage' => (string) SystemConfig::get('print_footer_message', ''),
                'shopName' => (string) SystemConfig::get('sys_shop_name', 'PS Web'),
                'isPdf' => true,
            ]);

            $pdfContent = $pdf->output();

            return base64_encode($pdfContent);
        } catch (\Exception $e) {
            Log::warning('Failed to generate invoice PDF: '.$e->getMessage());

            return null;
        }
    }
}
