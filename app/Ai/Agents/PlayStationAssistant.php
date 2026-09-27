<?php

namespace App\Ai\Agents;

use App\Ai\Tools\ExecuteReadOnlySql;
use App\Ai\Tools\GetApplicationHelpTool;
use App\Ai\Tools\GetBestSellingProductsTool;
use App\Ai\Tools\GetCashBoxesBalancesTool;
use App\Ai\Tools\GetCatalogInventoryTool;
use App\Ai\Tools\GetClosedDevicesByUserTool;
use App\Ai\Tools\GetCustomersBalancesTool;
use App\Ai\Tools\GetDevicesStatusTool;
use App\Ai\Tools\GetExpensesReportTool;
use App\Ai\Tools\GetItemOrderLocationsTool;
use App\Ai\Tools\GetLowStockAlertsTool;
use App\Ai\Tools\GetMostActiveDevicesTool;
use App\Ai\Tools\GetPaymentMethodsSummaryTool;
use App\Ai\Tools\GetReservationsTool;
use App\Ai\Tools\GetRevenueReportTool;
use App\Ai\Tools\GetReversedPurchasesTool;
use App\Ai\Tools\GetShiftSummaryTool;
use App\Ai\Tools\GetStaffPerformanceTool;
use App\Ai\Tools\GetWaitingListTool;
use App\Ai\Tools\ProposePriceUpdate;
use App\Support\ArabicSearch;
use App\Support\Tenant\AiTenantContext;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;

#[Provider(Lab::Gemini)]
#[Model('gemini-3.1-flash-lite')]
#[MaxSteps(3)]
#[MaxTokens(500)]
#[Temperature(0.1)]
class PlayStationAssistant implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(protected string $question = '') {}

    public function instructions(): string
    {
        $tenantContext = app(AiTenantContext::class)->compactPromptContext();

        return <<<PROMPT
You are the Arabic operations assistant for a PlayStation cafe.

Context:
{$tenantContext}

Rules:
- Answer in concise, clear Arabic for a business manager; normally 2-5 lines.
- For any claim about live business data, call the most specific available tool first.
- Never invent a screen, permission, record, column, relationship, amount, or status.
- Distinguish these cases exactly: record not found, record exists but has no activity in scope, and tool/query failure.
- An empty activity result never proves that the user, device, or catalog item does not exist.
- Preserve the user's scope. Do not assume "currently active" unless the user says so; when scope is omitted, report current and historical results separately if the tool provides both.
- Treat Arabic spelling variants as possible matches and mention the matched stored name.
- Never expose SQL, table names, field names, stack traces, or other internal implementation details.
- Use only the currency shown in Context and do not add follow-up sales language unless clarification is genuinely required.
PROMPT;
    }

    /**
     * Keep only two compact text turns. Stored tool payloads can be very large and
     * are intentionally excluded from the next prompt.
     *
     * @return array<int, Message>
     */
    public function messages(): iterable
    {
        $conversationId = $this->currentConversation();
        if (! $conversationId) {
            return [];
        }

        return DB::table(config('ai.conversations.tables.messages', 'agent_conversation_messages'))
            ->where('conversation_id', $conversationId)
            ->whereIn('role', ['user', 'assistant'])
            ->orderByDesc('id')
            ->limit(4)
            ->get(['role', 'content'])
            ->reverse()
            ->map(fn (object $message): Message => new Message($message->role, $message->content))
            ->values()
            ->all();
    }

    /**
     * @return array<Tool>
     */
    public function tools(): iterable
    {
        $normalized = ArabicSearch::normalize($this->routingQuestion());
        $tools = [];

        if ($this->containsAny($normalized, ['صلاح', 'تصريح', 'ازاي اضيف', 'كيف اضيف', 'طريقه اضافه', 'مكان الصلاحيه'])) {
            return [new GetApplicationHelpTool];
        }

        if ($this->containsAny($normalized, ['زود', 'زيد', 'قلل', 'خفض', 'تعديل سعر', 'غير سعر', 'حدد سعر'])) {
            return [new ProposePriceUpdate];
        }

        if ($this->containsAny($normalized, ['اتقفل', 'اتقفلت', 'اغلق', 'قفلها', 'قفل'])) {
            return [new GetClosedDevicesByUserTool];
        }

        if ($this->containsAny($normalized, ['فيزا', 'بطاق', 'كارت', 'طريقه دفع', 'مدفوعات'])) {
            return [new GetPaymentMethodsSummaryTool];
        }

        if ($this->containsAny($normalized, ['فاتوره معكوسه', 'فواتير معكوسه', 'المشتريات المعكوسه'])) {
            return [new GetReversedPurchasesTool];
        }

        if ($this->containsAny($normalized, ['ترابيز', 'طاول', 'نزل عليها', 'طلبات جهاز', 'مشاريب', 'طلب صنف'])) {
            return [new GetItemOrderLocationsTool];
        }

        if ($this->containsAny($normalized, ['صنف', 'اصناف', 'منتج', 'مشروب', 'قهو', 'شاي', 'مخزون', 'سعر'])) {
            $this->addTool($tools, new GetCatalogInventoryTool);
        }

        if ($this->containsAny($normalized, ['نواقص', 'ناقص', 'نفد', 'قارب علي النفاد', 'حد الطلب'])) {
            $this->addTool($tools, new GetLowStockAlertsTool);
        }

        if ($this->containsAny($normalized, ['ايراد', 'اراد المكان', 'دخل', 'مبيعات', 'مبلغ', 'تقارير اللعب', 'ربح', 'ارباح', 'مالي'])) {
            $this->addTool($tools, new GetRevenueReportTool);
        }

        if ($this->containsAny($normalized, ['مصروف', 'مصروفات', 'سلف', 'مرتب', 'مرتبات'])) {
            $this->addTool($tools, new GetExpensesReportTool);
        }

        if ($this->containsAny($normalized, ['ورديه', 'شيفت', 'درج', 'ملخص الورد'])) {
            $this->addTool($tools, new GetShiftSummaryTool);
        }

        if ($this->containsAny($normalized, ['جهاز', 'اجهزه', 'حاله المكان', 'جلسه', 'مشغول', 'متاح'])) {
            $this->addTool($tools, new GetDevicesStatusTool);
        }

        if ($this->containsAny($normalized, ['اكثر جهاز', 'انشط جهاز', 'تشغيل الاجهزه'])) {
            $this->addTool($tools, new GetMostActiveDevicesTool);
        }

        if ($this->containsAny($normalized, ['الاكثر مبيعا', 'اكثر صنف', 'اشهر منتج'])) {
            $this->addTool($tools, new GetBestSellingProductsTool);
        }

        if ($this->containsAny($normalized, ['عميل', 'عملاء', 'مديوني', 'نقاط', 'ولاء'])) {
            $this->addTool($tools, new GetCustomersBalancesTool);
        }

        if ($this->containsAny($normalized, ['موظف', 'مستخدم', 'كاشير', 'اداء', 'انتاجي'])) {
            $this->addTool($tools, new GetStaffPerformanceTool);
        }

        if ($this->containsAny($normalized, ['حجز', 'حجوزات', 'موعد'])) {
            $this->addTool($tools, new GetReservationsTool);
        }

        if ($this->containsAny($normalized, ['انتظار', 'منتظر', 'طابور'])) {
            $this->addTool($tools, new GetWaitingListTool);
        }

        if ($this->containsAny($normalized, ['خزينه', 'خزنه', 'صندوق', 'رصيد الدرج'])) {
            $this->addTool($tools, new GetCashBoxesBalancesTool);
        }

        if ($this->containsAny($normalized, ['ازاي', 'كيف', 'طريقه', 'مكان', 'صلاح', 'تفعيل', 'اضيف'])) {
            $this->addTool($tools, new GetApplicationHelpTool);
        }

        if ($tools === [] && config('ai.assistant.allow_read_only_sql', false)) {
            $this->addTool($tools, new ExecuteReadOnlySql);
        }

        return array_values($tools);
    }

    /**
     * @param  array<int, string>  $terms
     */
    private function containsAny(string $question, array $terms): bool
    {
        foreach ($terms as $term) {
            if (str_contains($question, ArabicSearch::normalize($term))) {
                return true;
            }
        }

        return false;
    }

    private function routingQuestion(): string
    {
        $normalized = ArabicSearch::normalize($this->question);
        if ($this->containsAny($normalized, ['شكرا', 'متشكر', 'تمام كده', 'لا تمام', 'تسلم'])) {
            return $this->question;
        }

        $words = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $wordCount = count($words);
        $needsContext = $wordCount <= 3
            || $this->containsAny($normalized, ['عليها', 'عليه', 'عندها', 'دول', 'دي', 'ده', 'نفس']);

        if (! $needsContext || ! $this->currentConversation()) {
            return $this->question;
        }

        $previousQuestion = DB::table(config('ai.conversations.tables.messages', 'agent_conversation_messages'))
            ->where('conversation_id', $this->currentConversation())
            ->where('role', 'user')
            ->orderByDesc('id')
            ->value('content');

        return $previousQuestion ? $previousQuestion."\n".$this->question : $this->question;
    }

    /**
     * @param  array<class-string<Tool>, Tool>  $tools
     */
    private function addTool(array &$tools, Tool $tool): void
    {
        $tools[$tool::class] = $tool;
    }
}
