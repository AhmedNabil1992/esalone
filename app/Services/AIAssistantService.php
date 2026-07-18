<?php

namespace App\Services;

use App\Ai\Agents\PlayStationAssistant;
use App\Ai\Tools\ExecuteReadOnlySql;
use App\Ai\Tools\ProposePriceUpdate;
use App\Models\AiChatLog;
use App\Models\AiTrainingRule;
use App\Models\Tenant\CatalogItem;
use App\Models\Tenant\Devices;
use App\Models\Tenant\DevicesType;
use App\Models\Tenant\InventoryGroup;
use App\Models\Tenant\PlayPrice;
use App\Models\Tenant\PlayType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function Laravel\Ai\agent;

class AIAssistantService
{
    /**
     * إرسال السؤال وسجل المحادثة إلى المساعد الذكي.
     * يرجع إما string (رد نصي عادي) أو array بمفتاح __pending_action عند اكتشاف طلب تعديل أسعار.
     *
     * @return string|array<string, mixed>
     */
    public function ask(string $question, array $history = []): string|array
    {
        // منع انتهاء مهلة تنفيذ PHP للمكالمات الخارجية
        if (function_exists('set_time_limit')) {
            @set_time_limit(90);
        }

        $tenant = tenant();
        if (! $tenant) {
            return 'خطأ: لم يتم التعرف على الفرع النشط.';
        }

        if (! $tenant->has_ai_subscription) {
            return 'عفواً، هذه الخدمة تتطلب الاشتراك في باقة المساعد الذكي (AI Assistant). برجاء التواصل مع الدعم الفني لتفعيل الاشتراك.';
        }

        // 1. التحقق من مفتاح الـ API
        $apiKey = config('services.gemini.key');
        if (! $apiKey || (! str_starts_with($apiKey, 'AIzaSy') && ! str_starts_with($apiKey, 'AQ'))) {
            return 'برجاء ضبط مفتاح GEMINI_API_KEY الصحيح في ملف البيئة (.env) لتفعيل المساعد الذكي.';
        }

        // 2. التحقق من الحد اليومي
        $today = now()->toDateString();
        $requestsLimit = $tenant->ai_requests_limit ?? 50;

        if ($tenant->ai_last_request_date !== $today) {
            $tenant->ai_requests_today = 0;
            $tenant->ai_last_request_date = $today;
            $tenant->save();
        }

        if ($tenant->ai_requests_today >= $requestsLimit) {
            return "عفواً، لقد استنفدت الحد اليومي المسموح به لأسئلة المساعد الذكي ({$requestsLimit} سؤالاً). برجاء المحاولة غداً أو التواصل مع الدعم لترقية الباقة.";
        }

        $executedSql = '';
        $dbResults = null;
        $bypassSqlGen = false;

        $normalized = trim(mb_strtolower($question));

        // 3. بناء سجل المحادثة (آخر 6 رسائل لتوفير التوكنز)
        $historyText = '';
        if (! empty($history)) {
            $recentHistory = array_slice($history, -6);
            foreach ($recentHistory as $msg) {
                $role = $msg['sender'] === 'user' ? 'User (Manager)' : 'AI Assistant';
                if ($msg['sender'] === 'user' && trim($msg['text']) === $question) {
                    continue;
                }
                $historyText .= "{$role}: {$msg['text']}\n";
            }
        }

        // 4. التوجيه المحلي الديناميكي عبر سجلات التعلم الذاتي (إذا كانت مفعلة)
        if (config('services.gemini.enable_self_learning_lookup', false)) {
            try {
                $cachedLogs = AiChatLog::where('tenant_id', $tenant->id)
                    ->whereNotNull('executed_sql')
                    ->where('was_routed', false)
                    ->latest()
                    ->take(200)
                    ->get();

                foreach ($cachedLogs as $log) {
                    similar_text($normalized, trim(mb_strtolower($log->question)), $percentage);
                    if ($percentage >= 90) { // تشابه 90% أو أكثر
                        $executedSql = $log->executed_sql;

                        // التحقق إذا كان الاستعلام المخزن عبارة عن JSON لتعديل الأسعار
                        if (str_starts_with($executedSql, '{')) {
                            $actionData = json_decode($executedSql, true);
                            if (is_array($actionData) && ($actionData['action'] ?? '') === 'update_price') {
                                Log::info('AIAssistant self-learning route matched action', ['question' => $log->question]);
                                $pendingResponse = $this->buildPendingActionResponse($actionData);

                                // تسجيل استعلام التعلم الذاتي
                                try {
                                    AiChatLog::create([
                                        'tenant_id' => $tenant->id,
                                        'user_id' => auth()->id(),
                                        'user_name' => auth()->user()->name ?? 'Manager',
                                        'question' => $question,
                                        'response' => $pendingResponse['preview'],
                                        'executed_sql' => $executedSql,
                                        'was_routed' => true,
                                    ]);
                                    $tenant->ai_requests_today += 1;
                                    $tenant->save();
                                } catch (\Throwable $logEx) {
                                    Log::warning('Failed to log learned price action hit', ['error' => $logEx->getMessage()]);
                                }

                                return $pendingResponse;
                            }
                        }

                        $bypassSqlGen = true;
                        Log::info('AIAssistant self-learning route matched query', ['question' => $log->question, 'percentage' => $percentage]);
                        break;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Self-learning lookup failed', ['error' => $e->getMessage()]);
            }
        }

        // 5. التوجيه المحلي الديناميكي عبر جدول قواعد التدريب المركزي
        if (! $bypassSqlGen) {
            try {
                $rules = AiTrainingRule::where('is_active', true)->get();
                foreach ($rules as $rule) {
                    if (preg_match('/'.$rule->pattern.'/ui', $normalized)) {
                        $executedSql = $rule->sql_query;
                        $bypassSqlGen = true;
                        Log::info('AIAssistant dynamic route matched', ['pattern' => $rule->pattern]);
                        break;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Dynamic routing failed', ['error' => $e->getMessage()]);
            }
        }

        if ($bypassSqlGen) {
            try {
                $dbResults = DB::select($executedSql);
            } catch (\Throwable $e) {
                Log::warning('Routed query failed, falling back to AI SQL generation', ['error' => $e->getMessage()]);
                $bypassSqlGen = false;
            }
        }

        // 5. الاستعانة بالمساعد الذكي والـ Tools العامة للـ SDK عند عدم مطابقة أي قاعدة محلية
        if (! $bypassSqlGen) {
            try {
                // إعادة تعيين قيم الـ Tools الثابتة لمنع تداخل الاستعلامات السابقة
                ExecuteReadOnlySql::reset();
                ProposePriceUpdate::reset();

                $assistant = new PlayStationAssistant($question);
                $user = auth()->user();

                // تنفيذ المساعد الذكي مع حفظ المحادثة
                if ($user) {
                    $response = $assistant->forUser($user)->prompt($question);
                } else {
                    $response = $assistant->prompt($question);
                }

                // التقاط ما إذا كان قد تم اقتراح تعديل أسعار من خلال أداة ProposePriceUpdate
                if (ProposePriceUpdate::$pendingAction !== null) {
                    $action = ProposePriceUpdate::$pendingAction;
                    $preview = ProposePriceUpdate::$previewText;

                    ProposePriceUpdate::reset();
                    ExecuteReadOnlySql::reset();

                    // تسجيل العملية المعلقة
                    try {
                        AiChatLog::create([
                            'tenant_id' => $tenant->id,
                            'user_id' => auth()->id(),
                            'user_name' => auth()->user()->name ?? 'Manager',
                            'question' => $question,
                            'response' => $preview,
                            'executed_sql' => json_encode($action),
                            'was_routed' => false,
                        ]);
                        $tenant->ai_requests_today += 1;
                        $tenant->save();
                    } catch (\Throwable $logEx) {
                        Log::warning('Failed to log pending price action in refactored service', ['error' => $logEx->getMessage()]);
                    }

                    return [
                        '__pending_action' => $action,
                        'preview' => $preview,
                    ];
                }

                $finalAnswer = $response->text;
                $executedSql = ExecuteReadOnlySql::$lastQuery;

                ProposePriceUpdate::reset();
                ExecuteReadOnlySql::reset();

                // حفظ الاستعلام بنجاح في سجل المحادثة
                try {
                    AiChatLog::create([
                        'tenant_id' => $tenant->id,
                        'user_id' => auth()->id(),
                        'user_name' => auth()->user()->name ?? 'Manager',
                        'question' => $question,
                        'response' => $finalAnswer,
                        'executed_sql' => $executedSql ?: null,
                        'was_routed' => false,
                    ]);
                    $tenant->ai_requests_today += 1;
                    $tenant->save();
                } catch (\Throwable $logEx) {
                    Log::warning('Failed to log AI query', ['error' => $logEx->getMessage()]);
                }

                return $finalAnswer;
            } catch (\Throwable $e) {
                Log::error('Error executing PlayStationAssistant agent', ['error' => $e->getMessage()]);

                return 'عفواً، واجه المساعد الذكي مشكلة أثناء معالجة طلبك. يرجى المحاولة مرة أخرى لاحقاً.';
            }
        }

        // 6. صياغة الإجابة العربية للقواعد الموجهة محلياً (Bypassed SQL rules)
        return $this->generateFinalAnswer($question, $executedSql, $dbResults, $historyText, $tenant, $bypassSqlGen);
    }

    /**
     * تنفيذ تعديل الأسعار بعد تأكيد المدير.
     */
    public function executePriceUpdate(array $actionData): string
    {
        $finalAnswer = $this->handlePriceUpdateAction($actionData);

        $tenant = tenant();
        try {
            AiChatLog::create([
                'tenant_id' => $tenant->id,
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name ?? 'Manager',
                'question' => 'تأكيد تعديل أسعار: '.($actionData['target'] ?? ''),
                'response' => $finalAnswer,
                'executed_sql' => json_encode($actionData),
                'was_routed' => true,
            ]);
        } catch (\Throwable $logEx) {
            Log::warning('Failed to log price action', ['error' => $logEx->getMessage()]);
        }

        return $finalAnswer;
    }

    /**
     * جلب البيانات المتأثرة أولاً وإرجاعها للمستخدم طلباً للتأكيد.
     *
     * @return array<string, mixed>
     */
    public function buildPendingActionResponse(array $actionData): array
    {
        $type = $actionData['type'] ?? '';
        $target = $actionData['target'] ?? '';
        $amount = (float) ($actionData['amount'] ?? 0);
        $operation = $actionData['operation'] ?? 'add';

        $operationLabel = match ($operation) {
            'add' => "زيادة {$amount} ج.م",
            'subtract' => "تخفيض {$amount} ج.م",
            'set' => "تحديد السعر بـ {$amount} ج.م",
            default => "تعديل {$amount} ج.م",
        };

        $preview = "⚠️ **تأكيد التعديل المطلوب**\n\n";

        if ($type === 'play') {
            $preview .= "العملية: **{$operationLabel}** على أسعار اللعب";
            if (! empty($target) && strtolower($target) !== 'all') {
                $preview .= " الخاصة بـ **{$target}**";
            } else {
                $preview .= ' لـ **جميع الأجهزة**';
            }
            $preview .= "\n\n**الأسعار الحالية قبل التعديل:**\n";

            $previewData = $this->getPlayPricesPreview($target, $amount, $operation);
            $preview .= $previewData['preview_text'];

        } elseif ($type === 'product') {
            $preview .= "العملية: **{$operationLabel}** على أسعار المبيعات";
            if (! empty($target) && strtolower($target) !== 'all') {
                $preview .= " لمجموعة **{$target}**";
            } else {
                $preview .= ' لـ **جميع المنتجات**';
            }
            $preview .= "\n\n**الأسعار الحالية قبل التعديل:**\n";

            $previewData = $this->getProductPricesPreview($target, $amount, $operation);
            $preview .= $previewData['preview_text'];
        }

        $preview .= "\n\n🔔 **هل تريد تأكيد هذا التعديل؟**";

        return [
            '__pending_action' => $actionData,
            'preview' => $preview,
        ];
    }

    /**
     * جلب preview لأسعار اللعب قبل التعديل.
     *
     * @return array<string, mixed>
     */
    protected function getPlayPricesPreview(string $target, float $amount, string $operation): array
    {
        $query = PlayPrice::query();

        if (! empty($target) && strtolower($target) !== 'all') {
            $device = Devices::where('name', 'like', '%'.$target.'%')->first();
            if ($device) {
                $query->where('device_id', $device->id);
            } else {
                $deviceType = DevicesType::where('name', 'like', '%'.$target.'%')->first();
                if ($deviceType) {
                    $deviceIds = Devices::where('devices_type_id', $deviceType->id)->pluck('id');
                    $query->whereIn('device_id', $deviceIds);
                } else {
                    $playType = PlayType::where('name', 'like', '%'.$target.'%')->first();
                    if ($playType) {
                        $query->where('play_type_id', $playType->id);
                    } else {
                        $query->whereRaw('1=0');
                    }
                }
            }
        }

        $prices = $query->with(['playType', 'device.type'])->get();
        $text = '';

        foreach ($prices as $p) {
            $devName = $p->device->name ?? 'عام';
            $devTypeName = $p->device->type->name ?? '';
            $typeName = $p->playType->name ?? 'عادي';
            $labelName = $devTypeName ? "{$devName} ({$devTypeName})" : $devName;
            $oldPrice = (float) $p->price;
            $newPrice = match ($operation) {
                'add' => $oldPrice + $amount,
                'subtract' => max(0, $oldPrice - $amount),
                default => $amount,
            };
            $text .= "* **{$labelName} ({$typeName}):** {$oldPrice} ج.م → **{$newPrice} ج.م**\n";
        }

        return ['preview_text' => $text ?: '* لا توجد أسعار مطابقة.'];
    }

    /**
     * جلب preview لأسعار المنتجات قبل التعديل.
     *
     * @return array<string, mixed>
     */
    protected function getProductPricesPreview(string $target, float $amount, string $operation): array
    {
        $query = CatalogItem::query()->where('is_product', true);

        if (! empty($target) && strtolower($target) !== 'all') {
            $group = InventoryGroup::where('name', 'like', '%'.$target.'%')->first();
            if ($group) {
                $query->where('inventory_group_id', $group->id);
            } else {
                $query->where('name', 'like', '%'.$target.'%');
            }
        }

        $items = $query->get();
        $text = '';

        foreach ($items as $item) {
            $oldPrice = (float) $item->sale_price;
            $newPrice = match ($operation) {
                'add' => $oldPrice + $amount,
                'subtract' => max(0, $oldPrice - $amount),
                default => $amount,
            };
            $text .= "* **{$item->name}:** {$oldPrice} ج.م → **{$newPrice} ج.م**\n";
        }

        return ['preview_text' => $text ?: '* لا توجد منتجات مطابقة.'];
    }

    /**
     * بناء الـ prompt الخاص بتوليد SQL مع مخطط الجداول وعلاقاتها.
     */
    protected function buildSqlPrompt(string $question, string $schemaContext, string $historyText): string
    {
        return "
You are an expert SQL generator and admin action parser for a PlayStation & Cafe management system running on MySQL.

Your PRIMARY job is to write a single correct READ-ONLY SELECT SQL query using the schema and relationship hints below.
When tables share data via foreign keys, you MUST use proper JOINs to get human-readable names instead of IDs.

DATABASE SCHEMA & TABLE RELATIONSHIPS:
===
{$schemaContext}
===

CRITICAL JOIN RULES (follow these to get correct results):
1. **Device Names:** Always JOIN devices ON <table>.device_id = devices.id to get device name instead of device_id.
2. **Device Type Names:** JOIN devices_types ON devices.devices_type_id = devices_types.id to get the type name.
3. **Customer Names:** JOIN customers ON <table>.customer_id = customers.id for customer names.
4. **Expense Names:** JOIN expenses ON expense_histories.expenses_id = expenses.id for expense category names.
5. **Catalog Item Names:** JOIN catalog_items ON <table>.catalog_item_id = catalog_items.id for item names.
6. **Play Type Names:** JOIN play_types ON play_prices.play_type_id = play_types.id for play type names.
7. **play_history** already stores device_name as a VARCHAR column — no JOIN needed for device name here.
8. **shifts** has user IDs for opened_by_user_id and closed_by_user_id — JOIN users ON these columns if user names are needed.

CONVERSATION HISTORY (for context and learning from user corrections):
===
{$historyText}
===

CRITICAL RULES:
1. ONLY output the raw SQL query. Do NOT wrap in markdown, backticks, or explanations.
2. The query MUST be a read-only SELECT statement.
3. If the user corrects how a calculation is done, adjust accordingly.
4. If the question cannot be answered with SQL, output 'NONE'.
5. **ACTION-TAKING (PRICE UPDATES):** If the user explicitly asks to change, increase, decrease, or set pricing/rates:
   Return ONLY this JSON (no other text):
   {\"action\": \"update_price\", \"type\": \"play|product\", \"target\": \"target name\", \"amount\": number, \"operation\": \"add|subtract|set\"}
   (Use 'play' for PlayStation rates, 'product' for café items. Operation: 'add' = increase, 'subtract' = decrease, 'set' = fixed price).

User Question: \"{$question}\"
SQL Query:";
    }

    /**
     * توليد الإجابة العربية النهائية بعد تنفيذ الاستعلام.
     */
    protected function generateFinalAnswer(
        string $question,
        string $executedSql,
        ?array $dbResults,
        string $historyText,
        mixed $tenant,
        bool $bypassSqlGen
    ): string {
        try {
            $resultsJson = json_encode($dbResults, JSON_UNESCAPED_UNICODE);
            $answerPrompt = "
أنت مساعد ذكي ونظام خبير متكامل لإدارة صالة ألعاب PlayStation وكافيه.
أجب عن سؤال المستخدم باللغة العربية بأسلوب ودود ومهني وواضح ومختصر وبشكل نقاط مرتبة.

سجل المحادثة السابقة:
===
{$historyText}
===

سؤال المستخدم الجديد: \"{$question}\"
الاستعلام المنفذ على قاعدة البيانات: \"{$executedSql}\"
نتائج الاستعلام الفعلية (JSON):
===
{$resultsJson}
===

تعليمات الصياغة:
1. اعتمد تماماً على نتائج الاستعلام لتغطية الإجابة بالأرقام الحقيقية.
2. إذا ذكر المستخدم افتراضاً خاطئاً يخالف الأرقام الفعلية، صحّحه بلطف.
3. إذا قام المستخدم بتصحيحك في الرسائل السابقة، اعترف بذلك وقدم الحساب المعدل.
4. إذا كانت النتائج فارغة، وضح بلطف أنه لا توجد بيانات مطابقة ولا تخترع أرقاماً.
5. إذا سألك المستخدم عن حذف أو إضافة تعديلات (غير الأسعار)، أخبره أنك مساعد تحليلي فقط.

الإجابة المنسقة:";

            // استخدام الـ Laravel AI SDK لصياغة الإجابة بطريقة آمنة
            $response = agent(
                instructions: 'أنت مساعد ذكي ونظام خبير لكافيه بلايستيشن وتقوم بصياغة الإجابات العربية بناءً على بيانات JSON الفعلية.'
            )->prompt($answerPrompt);

            $finalAnswer = $response->text;

            if (empty($finalAnswer)) {
                return 'لم أتمكن من العثور على إجابة مناسبة للسؤال.';
            }

            // حفظ السجل المركزي وزيادة العداد
            try {
                AiChatLog::create([
                    'tenant_id' => $tenant->id,
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->name ?? 'Manager',
                    'question' => $question,
                    'response' => $finalAnswer,
                    'executed_sql' => $executedSql ?: null,
                    'was_routed' => $bypassSqlGen,
                ]);
                $tenant->ai_requests_today += 1;
                $tenant->save();
            } catch (\Throwable $logEx) {
                Log::warning('Failed to log AI query', ['error' => $logEx->getMessage()]);
            }

            return $finalAnswer;
        } catch (\Throwable $e) {
            Log::error('Error in final answer step', ['error' => $e->getMessage()]);

            return 'حدث خطأ غير متوقع أثناء معالجة السؤال.';
        }
    }

    /**
     * تنظيف مخرجات SQL من الـ AI.
     */
    protected function cleanSqlOutput(string $raw): string
    {
        $sql = trim($raw);
        if (preg_match('/```sql(.*?)```/is', $sql, $matches)) {
            $sql = trim($matches[1]);
        } elseif (preg_match('/```(.*?)```/is', $sql, $matches)) {
            $sql = trim($matches[1]);
        }

        return rtrim($sql, ';');
    }

    /**
     * التحقق من أمان SQL وإرجاعه أو null إذا كان غير آمن.
     */
    protected function sanitizeSql(string $sql): ?string
    {
        $lowerSql = strtolower($sql);
        if (! str_starts_with($lowerSql, 'select')) {
            return null;
        }

        $forbidden = ['insert', 'update', 'delete', 'drop', 'alter', 'create', 'truncate', 'replace', 'rename', 'grant', 'revoke', 'lock'];
        foreach ($forbidden as $word) {
            if (preg_match('/\b'.preg_quote($word, '/').'\b/i', $lowerSql)) {
                Log::warning('Blocked unsafe SQL keyword: '.$word, ['sql' => $sql]);

                return null;
            }
        }

        return $sql;
    }

    public function getSchemaForQuestion(string $question): string
    {
        return $this->getFilteredSchemaContext($question);
    }

    /**
     * تصفية مخطط قاعدة البيانات وإرسال الجداول ذات الصلة فقط لتوفير التوكنز.
     */
    private function getFilteredSchemaContext(string $question): string
    {
        $allTables = [
            'shifts',
            'invoices',
            'invoice_payments',
            'devices',
            'devices_types',
            'customers',
            'play_history',
            'play_sessions',
            'play_session_details',
            'play_prices',
            'play_types',
            'expense_histories',
            'expenses_types',
            'purchases',
            'purchase_items',
            'catalog_items',
            'inventory_groups',
            'reservations',
            'waiting_lists',
            'warehouses',
            'warehouse_stocks',
            'order_session_details',
        ];

        return $this->getSpecificTablesSchema($allTables);
    }

    /**
     * جلب مخطط الجداول المحددة مع توضيح العلاقات.
     */
    private function getSpecificTablesSchema(array $tables): string
    {
        $allSchema = [
            'shifts' => "Table: shifts (سجلات الورديات)
- id (bigint, PK)
- shift_no (int): رقم الوردية
- shift_date (date): تاريخ الوردية
- shift_from (timestamp): وقت بداية الوردية
- shift_to (timestamp): وقت نهاية الوردية
- opened_by_user_id (bigint, FK → users.id)
- closed_by_user_id (bigint, FK → users.id)
- start_amount (decimal): رصيد البداية بالدرج
- playstation_amount (decimal): إيراد البلايستيشن (0 في الوردية المفتوحة)
- sales_amount (decimal): إيراد الكافيه (0 في الوردية المفتوحة)
- expected_amount (decimal): الرصيد الدفتري المتوقع عند الإغلاق
- actual_amount (decimal): الرصيد الفعلي عند الإغلاق
- expenses_amount (decimal): إجمالي المصروفات
- purchase_amount (decimal): إجمالي المشتريات
- discount_amount (decimal): إجمالي الخصومات
- status (varchar): 'open' مفتوح، 'close' مغلق",

            'invoices' => "Table: invoices (الفواتير المصدرة)
- id (bigint, PK)
- invoice_no (varchar): رقم الفاتورة
- invoice_type (varchar): 'device_sale' للعب، 'direct_sale' للكافيه المباشر
- play_amount (decimal): قيمة اللعب
- order_amount (decimal): قيمة الطلبات
- tax_amount (decimal): قيمة الضريبة
- service_amount (decimal): قيمة الخدمة
- discount_amount (decimal): قيمة الخصم
- final_amount (decimal): القيمة الصافية النهائية (الإيراد الحقيقي)
- remain_amount (decimal): المبلغ المتبقي (مديونية)
- status (varchar): 'posted' معتمدة، 'draft' مسودة، 'returned' مرتجعة
- shift_id (bigint, FK → shifts.id)
- customer_id (bigint, FK → customers.id)
- issued_at (timestamp): تاريخ الإصدار",

            'invoice_payments' => "Table: invoice_payments (طرق الدفع للفواتير)
- id (bigint, PK)
- invoice_id (bigint, FK → invoices.id)
- payment_method (varchar): 'cash' كاش، 'wallet' محفظة، 'customer_balance' خصم من رصيد العميل
- amount (decimal): المبلغ المدفوع",

            'devices' => "Table: devices (أجهزة البلايستيشن)
- id (bigint, PK)
- name (varchar): اسم الجهاز (مثال: جهاز 1، جهاز 2)
- status (varchar): 'active' نشط، 'inactive' معطل، 'busy' مشغول
- devices_type_id (bigint, FK → devices_types.id)
RELATIONSHIP: devices JOIN devices_types ON devices.devices_type_id = devices_types.id → to get device type name",

            'devices_types' => 'Table: devices_types (أنواع الأجهزة)
- id (bigint, PK)
- name (varchar): اسم النوع (مثال: PS5، PS4، بلياردو، VIP)
RELATIONSHIP: Used by devices.devices_type_id',

            'customers' => 'Table: customers (سجلات العملاء)
- id (bigint, PK)
- name (varchar): اسم العميل
- phone (varchar): هاتف العميل
- balance (decimal): رصيد المحفظة (سالب = مديون)
- points (int): نقاط الولاء',

            'play_history' => 'Table: play_history (سجل اللعب التاريخي المكتمل - يحتوي على اسم الجهاز مباشرة)
- id (bigint, PK)
- device_id (bigint, FK → devices.id)
- device_name (varchar): اسم الجهاز محفوظ مباشرة هنا (لا حاجة لـ JOIN للحصول على الاسم)
- play_type (varchar): نوع اللعب (عادي، مالتي، إلخ)
- start_time (datetime): وقت بدء اللعب
- end_time (datetime): وقت نهاية اللعب
- play_time (int): مدة اللعب بالدقائق
- cost (decimal): إيراد اللعب
- shift_no (int): رقم الوردية
- trx_date (date): تاريخ العملية
NOTE: To find most active device, use GROUP BY device_name and SUM(play_time) or COUNT(*)',

            'play_sessions' => "Table: play_sessions (جلسات اللعب النشطة حالياً)
- id (bigint, PK)
- device_id (bigint, FK → devices.id)
- customer_id (bigint, FK → customers.id)
- prepaid_amount (decimal): مبلغ مدفوع مقدماً
- status (varchar): 'active' نشطة، 'completed' منتهية
- created_at (timestamp): تاريخ ووقت بدء أو فتح الجلسة
RELATIONSHIP: JOIN devices ON play_sessions.device_id = devices.id → device name",

            'play_session_details' => "Table: play_session_details (تفاصيل فترات اللعب)
- id (bigint, PK)
- play_session_id (bigint, FK → play_sessions.id)
- device_id (bigint, FK → devices.id)
- play_type (varchar): نوع اللعب
- play_price (decimal): سعر الساعة
- start_time (datetime): وقت بدء الفترة
- end_time (datetime): وقت نهاية الفترة
- cost (decimal): تكلفة الفترة
- status (varchar): 'active' نشطة، 'completed' منتهية",

            'play_prices' => 'Table: play_prices (تسعيرة اللعب لكل جهاز ونوع)
- id (bigint, PK)
- device_id (bigint, FK → devices.id)
- play_type_id (bigint, FK → play_types.id)
- price (decimal): السعر بالساعة
- start_from (time): وقت بدء تطبيق السعر
RELATIONSHIP: play_prices JOIN play_types ON play_prices.play_type_id = play_types.id
RELATIONSHIP: play_prices JOIN devices ON play_prices.device_id = devices.id',

            'play_types' => 'Table: play_types (أنواع اللعب)
- id (bigint, PK)
- name (varchar): اسم نوع اللعب (مثال: فردي، مالتي، إلخ)
- device_type_id (bigint, FK → devices_types.id)',

            'expense_histories' => 'Table: expense_histories (سجل المصروفات)
- id (bigint, PK)
- expenses_id (bigint, FK → expenses_types.id)
- amount (decimal): قيمة المصروف
- notes (varchar): تفاصيل المصروف
- created_at (timestamp)
RELATIONSHIP: JOIN expenses_types ON expense_histories.expenses_id = expenses_types.id → to get expense name',

            'expenses_types' => 'Table: expenses_types (أنواع بنود المصروفات)
- id (bigint, PK)
- name (varchar): اسم البند (إيجار، كهرباء، صيانة، إلخ)',

            'purchases' => "Table: purchases (مشتريات البضائع)
- id (bigint, PK)
- total_amount (decimal): إجمالي المشتريات
- status (varchar): 'posted' معتمدة، 'draft' مسودة
- purchased_at (timestamp): تاريخ الشراء",

            'purchase_items' => 'Table: purchase_items (أصناف الفواتير الشرائية)
- id (bigint, PK)
- purchase_id (bigint, FK → purchases.id)
- catalog_item_id (bigint, FK → catalog_items.id)
- quantity (decimal): الكمية
- unit_cost (decimal): سعر التكلفة
- total_cost (decimal): التكلفة الكلية
RELATIONSHIP: JOIN catalog_items ON purchase_items.catalog_item_id = catalog_items.id → item name',

            'catalog_items' => 'Table: catalog_items (أصناف المبيعات والكافيه)
- id (bigint, PK)
- name (varchar): اسم الصنف
- sale_price (decimal): سعر البيع للجمهور
- purchase_price (decimal): سعر التكلفة
- is_product (tinyint): 1 = منتج للبيع
- is_active (tinyint): 1 = نشط
- inventory_group_id (bigint, FK → inventory_groups.id)
RELATIONSHIP: JOIN inventory_groups ON catalog_items.inventory_group_id = inventory_groups.id → group/category name',

            'inventory_groups' => 'Table: inventory_groups (مجموعات/تصنيفات أصناف المبيعات)
- id (bigint, PK)
- name (varchar): اسم المجموعة (مشروبات ساخنة، مشروبات باردة، وجبات، إلخ)
- printer_name (varchar): اسم طابعة المطبخ/البار المرتبطة بالمجموعة',

            'reservations' => "Table: reservations (حجوزات الأجهزة)
- id (bigint, PK)
- device_id (bigint, FK → devices.id)
- customer_id (bigint, FK → customers.id)
- start_time (datetime): وقت بدء الحجز
- end_time (datetime): وقت نهاية الحجز
- status (enum): 'pending' معلق، 'confirmed' مؤكد، 'completed' مكتمل، 'cancelled' ملغي
RELATIONSHIP: JOIN devices ON reservations.device_id = devices.id → device name
RELATIONSHIP: JOIN customers ON reservations.customer_id = customers.id → customer name",

            'waiting_lists' => "Table: waiting_lists (قائمة الانتظار)
- id (bigint, PK)
- customer_name (varchar): اسم العميل
- customer_phone (varchar): هاتف العميل
- devices_type_id (bigint, FK → devices_types.id)
- status (enum): 'waiting' ينتظر، 'served' خُدِم، 'cancelled' ملغي",

            'warehouses' => 'Table: warehouses (المخازن)
- id (bigint, PK)
- name (varchar): اسم المخزن
- is_active (tinyint)',

            'warehouse_stocks' => 'Table: warehouse_stocks (كميات المخزون)
- id (bigint, PK)
- warehouse_id (bigint, FK → warehouses.id)
- catalog_item_id (bigint, FK → catalog_items.id)
- quantity (decimal): الكمية المتوفرة
RELATIONSHIP: JOIN catalog_items ON warehouse_stocks.catalog_item_id = catalog_items.id → item name',

            'order_session_details' => "Table: order_session_details (طلبات الكافيه للجلسات المفتوحة)
- id (bigint, PK)
- play_session_id (bigint, FK → play_sessions.id)
- device_id (bigint, FK → devices.id)
- catalog_item_id (bigint, FK → catalog_items.id)
- price (decimal): سعر البيع
- quantity (int): الكمية
- total (decimal): المجموع
- status (varchar): 'pending' معلق، 'served' خُدِم، 'cancelled' ملغي
RELATIONSHIP: JOIN catalog_items ON order_session_details.catalog_item_id = catalog_items.id",
        ];

        $context = '';
        foreach ($tables as $t) {
            if (isset($allSchema[$t])) {
                $context .= $allSchema[$t]."\n\n";
            }
        }

        $context .= "
CRITICAL BUSINESS LOGIC HINTS:
1. **Shift Revenue:** For CLOSED shifts: playstation_amount + sales_amount (NOT start_amount).
2. **Current Shift Revenue:** SELECT SUM(final_amount) FROM invoices WHERE status='posted' AND shift_id=(SELECT id FROM shifts WHERE status='open' ORDER BY id DESC LIMIT 1)
3. **Most Active Device:** SELECT d.name AS device_name, SUM(ph.play_time) AS total_minutes, COUNT(*) AS sessions FROM play_history ph LEFT JOIN devices d ON ph.device_id = d.id GROUP BY d.id, d.name ORDER BY total_minutes DESC LIMIT 1
4. **Net Profit (Monthly):** Revenue - expenses_amount - purchases_amount for the month.
5. **Customer Balance:** balance column in customers — negative means they owe money.
6. **Play Prices:** price column in play_prices (not play_history.cost which is actual session earnings).
7. **مدة تشغيل الأجهزة النشطة (المشغولة):** لمعرفة مدة تشغيل الأجهزة المفتوحة حالياً، احسب فرق الوقت بالدقائق أو الساعات بين الوقت الحالي ووقت بدء الجلسة (created_at). مثال: SELECT devices.name, TIMEDIFF(NOW(), play_sessions.created_at) AS duration FROM play_sessions JOIN devices ON play_sessions.device_id = devices.id WHERE play_sessions.status='active'
8. **حساب تكلفة الجلسة النشطة حالياً (حساب الوقت المفتوح الحساب الحالي):** لحساب تكلفة جلسة اللعب لجهاز نشط حالياً إذا انتهت الآن، اجمع تكلفة الفترات المكتملة (`psd.end_time IS NOT NULL`) بالإضافة إلى تكلفة الفترة النشطة الحالية (`psd.end_time IS NULL`) محسوبة بفرق الثواني بين وقت بدء الفترة الحالية والوقت الحالي مضروباً في سعر الساعة مقسوماً على 3600. مثال:
   `SELECT (IFNULL(SUM(CASE WHEN psd.end_time IS NOT NULL THEN psd.cost ELSE 0 END), 0) + SUM(CASE WHEN psd.end_time IS NULL THEN (TIMESTAMPDIFF(SECOND, psd.start_time, NOW()) / 3600.0) * psd.play_price ELSE 0 END)) AS total_cost FROM play_session_details psd JOIN play_sessions ps ON psd.play_session_id = ps.id JOIN devices d ON ps.device_id = d.id WHERE d.name LIKE '%Room 1%' AND ps.status='active'`
9. **البحث عن اسم الجهاز ومطابقته:** لا تفترض أبداً تحويل أسماء الأجهزة (مثل: Room 1) إلى أسماء افتراضية أخرى (مثل: جهاز 1). ابحث دائماً عن الاسم الذي يكتبه المستخدم مباشرة باستخدام عامل التصفية LIKE (مثل: `d.name LIKE '%Room 1%'`).
10. **حساب إجمالي الفاتورة المفتوحة بالطلبات (كافيه + لعب):** لحساب الحساب الكلي لجهاز مفتوح بالطلبات، اجمع تكلفة فترات اللعب (كما هو موضح في Hint 8) بالإضافة إلى إجمالي قيمة الطلبات المفتوحة للجلسة من جدول `order_session_details` حيث `status != 'cancelled'`. مثال:
   `SELECT (SELECT IFNULL(SUM(CASE WHEN psd.end_time IS NOT NULL THEN psd.cost ELSE 0 END), 0) + SUM(CASE WHEN psd.end_time IS NULL THEN (TIMESTAMPDIFF(SECOND, psd.start_time, NOW()) / 3600.0) * psd.play_price ELSE 0 END) FROM play_session_details psd WHERE psd.play_session_id = ps.id) AS play_cost, (SELECT IFNULL(SUM(total), 0) FROM order_session_details osd WHERE osd.play_session_id = ps.id AND osd.status != 'cancelled') AS orders_cost FROM play_sessions ps JOIN devices d ON ps.device_id = d.id WHERE d.name LIKE '%Room 1%' AND ps.status='active'`
11. **العملاء وجلسات اللعب المفتوحة (Walk-in vs Registered):** حقل `customer_id` في جدول `play_sessions` قد يكون فارغاً (NULL) لبعض الجلسات (في حال كان الزبون يسير عادياً Walk-in). لذلك عند محاولة جلب تفاصيل أو حساب جلسات لعب نشطة، استخدم دائماً `LEFT JOIN customers` بدلاً من `INNER JOIN customers` حتى لا يتم استبعاد الجلسات المفتوحة التي لا ترتبط بعميل مسجل.
12. **أسلوب الرد وصياغة الإجابات للمدير:** يجب أن تكون الإجابات الموجهة للمدير دائماً باللغة العربية البسيطة والمفهومة للبيزنس. تجنب تماماً ذكر الأسماء التقنية للجداول أو الحقول البرمجية (مثل: `play_history` أو `device_id` أو `SELECT` أو `Null` أو `JOIN`) في نص الإجابة النهائي للمستخدم. ركز فقط على الأسماء التجارية المألوفة للمدير (مثل: سجل اللعب، اسم الجهاز، إجمالي الفاتورة، أو الكافيتريا).
";

        return $context;
    }

    /**
     * تنفيذ تعديل الأسعار فعلياً بعد تأكيد المدير.
     */
    protected function handlePriceUpdateAction(array $actionData): string
    {
        $type = $actionData['type'] ?? '';
        $operation = $actionData['operation'] ?? 'add';
        $amount = (float) ($actionData['amount'] ?? 0);
        $target = $actionData['target'] ?? '';

        if ($amount <= 0 && $operation !== 'set') {
            return 'عفواً، يجب تحديد قيمة صحيحة أكبر من الصفر للتعديل.';
        }

        if ($type === 'play') {
            $query = PlayPrice::query();
            $targetName = 'جميع الأجهزة';

            if (! empty($target) && strtolower($target) !== 'all') {
                $device = Devices::where('name', 'like', '%'.$target.'%')->first();
                if ($device) {
                    $query->where('device_id', $device->id);
                    $targetName = $device->name;
                } else {
                    $deviceType = DevicesType::where('name', 'like', '%'.$target.'%')->first();
                    if ($deviceType) {
                        $deviceIds = Devices::where('devices_type_id', $deviceType->id)->pluck('id');
                        $query->whereIn('device_id', $deviceIds);
                        $targetName = 'أجهزة '.$deviceType->name;
                    } else {
                        $playType = PlayType::where('name', 'like', '%'.$target.'%')->first();
                        if ($playType) {
                            $query->where('play_type_id', $playType->id);
                            $targetName = 'نمط لعب '.$playType->name;
                        } else {
                            $query->whereRaw('1=0');
                            $targetName = 'غير معروف: '.$target;
                        }
                    }
                }
            }

            $prices = $query->with(['playType', 'device.type'])->get();
            if ($prices->isEmpty()) {
                return "عفواً، لم يتم العثور على أي تسعيرة لعب مطابقة لـ \"{$target}\".";
            }

            foreach ($prices as $priceObj) {
                $oldPrice = (float) $priceObj->price;
                $newPrice = match ($operation) {
                    'add' => $oldPrice + $amount,
                    'subtract' => max(0, $oldPrice - $amount),
                    default => $amount,
                };
                $priceObj->update(['price' => $newPrice]);
            }

            $pricesAfter = $query->with(['playType', 'device.type'])->get();
            $response = "✅ **تم تعديل أسعار اللعب بنجاح!** ({$targetName})\n\nإليك الأسعار الجديدة:\n";
            foreach ($pricesAfter as $p) {
                $devName = $p->device->name ?? 'عام';
                $devTypeName = $p->device->type->name ?? '';
                $typeName = $p->playType->name ?? 'عادي';
                $labelName = $devTypeName ? "{$devName} ({$devTypeName})" : $devName;
                $response .= "* **{$labelName} ({$typeName}):** ".number_format((float) $p->price, 2)." ج.م\n";
            }

            return $response;

        } elseif ($type === 'product') {
            $query = CatalogItem::query()->where('is_product', true);
            $targetName = 'جميع منتجات الكافيه';

            if (! empty($target) && strtolower($target) !== 'all') {
                $group = InventoryGroup::where('name', 'like', '%'.$target.'%')->first();
                if ($group) {
                    $query->where('inventory_group_id', $group->id);
                    $targetName = 'مجموعة '.$group->name;
                } else {
                    $query->where('name', 'like', '%'.$target.'%');
                    $targetName = 'منتج '.$target;
                }
            }

            $items = $query->get();
            if ($items->isEmpty()) {
                return "عفواً، لم يتم العثور على أي منتج مطابق لـ \"{$target}\".";
            }

            foreach ($items as $item) {
                $oldPrice = (float) $item->sale_price;
                $newPrice = match ($operation) {
                    'add' => $oldPrice + $amount,
                    'subtract' => max(0, $oldPrice - $amount),
                    default => $amount,
                };
                $item->update(['sale_price' => $newPrice]);
            }

            $itemsAfter = $query->get();
            $response = "✅ **تم تعديل أسعار المبيعات بنجاح!** ({$targetName})\n\nإليك الأسعار الجديدة:\n";
            foreach ($itemsAfter as $item) {
                $response .= "* **{$item->name}:** ".number_format((float) $item->sale_price, 2)." ج.م\n";
            }

            return $response;
        }

        return 'عفواً، نوع التعديل غير مدعوم.';
    }
}
