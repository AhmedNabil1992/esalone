<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ExecuteReadOnlySql implements Tool
{
    public static ?string $lastQuery = null;

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'نفّذ استعلام MySQL (SELECT فقط) للحصول على معلومات من قاعدة بيانات الكافيه. استخدم أسماء الجداول والعلاقات الصحيحة من المخطط المعطى.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        // التحقق من الصلاحيات الإضافية لأمان الـ SaaS
        if (! auth()->check() || ! auth()->user()->hasRole('super_admin')) {
            return 'خطأ: غير مسموح لك بتشغيل استعلامات SQL المباشرة. يرجى استخدام الأدوات المخصصة.';
        }

        $sql = trim($request['sql']);
        self::$lastQuery = $sql;

        // تنظيف استعلام SQL من العلامات المائلة أو التنسيقات
        if (preg_match('/```sql(.*?)```/is', $sql, $matches)) {
            $sql = trim($matches[1]);
        } elseif (preg_match('/```(.*?)```/is', $sql, $matches)) {
            $sql = trim($matches[1]);
        }
        $sql = rtrim($sql, ';');

        // التحقق من الأمان (مسموح بـ SELECT فقط)
        $lowerSql = strtolower($sql);
        if (! str_starts_with($lowerSql, 'select')) {
            return 'خطأ: مسموح فقط باستعلامات SELECT للقراءة.';
        }

        // الكلمات المحظورة لمنع التعديل أو التخريب
        $forbidden = ['insert', 'update', 'delete', 'drop', 'alter', 'create', 'truncate', 'replace', 'rename', 'grant', 'revoke', 'lock'];
        foreach ($forbidden as $word) {
            if (preg_match('/\b'.preg_quote($word, '/').'\b/i', $lowerSql)) {
                Log::warning('Blocked unsafe SQL keyword in ExecuteReadOnlySql tool: '.$word, ['sql' => $sql]);

                return 'خطأ: غير مسموح باستخدام عمليات تعديل البيانات.';
            }
        }

        // الجداول المحظورة لمنع تسريب البيانات الحساسة (مثل كلمات المرور والصلاحيات والجلسات)
        $forbiddenTables = ['users', 'password_reset_tokens', 'roles', 'permissions', 'failed_jobs', 'sessions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions'];
        foreach ($forbiddenTables as $table) {
            if (preg_match('/\b'.preg_quote($table, '/').'\b/i', $lowerSql)) {
                Log::warning('Blocked unsafe SQL table access in ExecuteReadOnlySql tool: '.$table, ['sql' => $sql]);

                return 'خطأ: غير مسموح بالوصول إلى بيانات المستخدمين أو الصلاحيات لأسباب أمنية.';
            }
        }

        // استبدال NOW() و CURRENT_TIMESTAMP بالوقت المحلي للتطبيق لحل مشاكل التوقيت الزمني (Timezone)
        $localNow = "'".now()->toDateTimeString()."'";
        $sql = preg_replace('/\bNOW\(\)/i', $localNow, $sql);
        $sql = preg_replace('/\bCURRENT_TIMESTAMP\(\)/i', $localNow, $sql);
        $sql = preg_replace('/\bCURRENT_TIMESTAMP\b/i', $localNow, $sql);

        try {
            $results = DB::select($sql);

            return json_encode($results, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            Log::warning('SQL execution failed in tool', ['sql' => $sql, 'error' => $e->getMessage()]);

            return 'خطأ في تنفيذ الاستعلام: '.$e->getMessage();
        }
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'sql' => $schema->string()
                ->description('استعلام SELECT المكتوب بلغة SQL لتنفيذه.')
                ->required(),
        ];
    }

    /**
     * إعادة تعيين الحقول بعد معالجة الطلب.
     */
    public static function reset(): void
    {
        self::$lastQuery = null;
    }
}
