<?php

namespace App\Ai\Tools;

use App\Services\AIAssistantService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ProposePriceUpdate implements Tool
{
    /**
     * الحقول الثابتة لحفظ الطلب المعلق مؤقتاً لكي يقرأه الـ Service بعد انتهاء تشغيل الـ Agent.
     */
    public static ?array $pendingAction = null;

    public static string $previewText = '';

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'اقترح عملية تعديل للأسعار (سواء أسعار ألعاب البلايستيشن أو أسعار منتجات الكافيه) للحصول على تأكيد المدير قبل الحفظ.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $type = $request['type'];
        $target = $request['target'];
        $amount = (float) $request['amount'];
        $operation = $request['operation'];

        // إعداد البنية المتوقعة
        $actionData = [
            'action' => 'update_price',
            'type' => $type,
            'target' => $target,
            'amount' => $amount,
            'operation' => $operation,
        ];

        // استخدام الـ Service لبناء معاينة التغيير قبل وبعد
        $aiService = app(AIAssistantService::class);
        $pendingResponse = $aiService->buildPendingActionResponse($actionData);

        // حفظ البيانات في الحقول الثابتة
        self::$pendingAction = $actionData;
        self::$previewText = $pendingResponse['preview'];

        return $pendingResponse['preview'];
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()
                ->description('نوع التعديل: play لأسعار اللعب أو product لأسعار المنتجات.')
                ->required(),
            'target' => $schema->string()
                ->description('اسم الجهاز أو اسم فئة/صنف المنتجات أو all للكل.')
                ->required(),
            'amount' => $schema->number()
                ->description('القيمة المراد زيادتها أو إنقاصها أو تحديدها.')
                ->required(),
            'operation' => $schema->string()
                ->description('العملية: add للزيادة، subtract للخصم، set لتحديد السعر.')
                ->required(),
        ];
    }

    /**
     * إعادة تعيين الحقول بعد معالجة الطلب.
     */
    public static function reset(): void
    {
        self::$pendingAction = null;
        self::$previewText = '';
    }
}
