<?php

namespace App\Filament\Pages;

use App\Models\Tenant\User;
use App\Services\AIAssistantService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;

class AIAssistant extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.ai-assistant';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static string|\UnitEnum|null $navigationGroup = 'تقارير إدارية';

    protected static ?string $navigationLabel = 'المساعد الذكي (AI)';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'المساعد الذكي (AI)';

    public string $question = '';

    public array $messages = [];

    public int $requestsToday = 0;

    public int $requestsLimit = 10;

    public bool $hasAiSubscription = false;

    /**
     * Pending price-update action waiting for user confirmation.
     * When not null, shows the confirmation UI.
     *
     * @var array<string, mixed>|null
     */
    public ?array $pendingAction = null;

    /**
     * Human-readable preview of what will be changed (shown to user before confirming).
     */
    public string $pendingPreview = '';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user && ($user->hasRole('super_admin') || $user->hasRole('admin') || $user->can('page_AIAssistant'));
    }

    public function mount(): void
    {
        $tenant = tenant();
        $this->requestsLimit = $tenant->ai_requests_limit ?? 10;
        $this->hasAiSubscription = (bool) ($tenant->has_ai_subscription ?? false);

        // Reset daily counter if day changed
        $today = now()->toDateString();
        if ($tenant->ai_last_request_date !== $today) {
            $tenant->ai_requests_today = 0;
            $tenant->ai_last_request_date = $today;
            $tenant->save();
        }

        $this->requestsToday = $tenant->ai_requests_today ?? 0;

        // Load persistent chat logs from the SDK's tenant database tables
        $latestConversation = \DB::table('agent_conversations')
            ->where('user_id', auth()->id())
            ->orderBy('updated_at', 'desc')
            ->first();

        $chatMessages = [];
        if ($latestConversation) {
            $chatMessages = \DB::table('agent_conversation_messages')
                ->where('conversation_id', $latestConversation->id)
                ->orderBy('created_at', 'asc')
                ->get();
        }

        if (empty($chatMessages) || count($chatMessages) === 0) {
            $this->messages[] = [
                'sender' => 'ai',
                'text' => 'مرحباً بك يا مدير ! 👋 أنا مساعدك الذكي . يمكنك سؤالي عن أي شيء يخص إيرادات الوردية الحالية، مبيعات الكافيه، حالة الأجهزة النشطة، أو الأرباح والمصروفات المسجلة لليوم.',
            ];
        } else {
            foreach ($chatMessages as $msg) {
                if ($msg->role === 'user') {
                    $this->messages[] = [
                        'sender' => 'user',
                        'text' => $msg->content,
                    ];
                } elseif ($msg->role === 'assistant') {
                    $this->messages[] = [
                        'sender' => 'ai',
                        'text' => $msg->content,
                    ];
                }
            }
        }
    }

    public function sendMessage(): void
    {
        if (! $this->hasAiSubscription) {
            $this->messages[] = [
                'sender' => 'ai',
                'text' => 'عفواً، هذه الخدمة تتطلب الاشتراك في باقة المساعد الذكي (AI Assistant). برجاء التواصل مع الدعم الفني لتفعيل الاشتراك.',
            ];
            $this->dispatch('scroll-to-bottom');

            return;
        }

        $this->validate([
            'question' => 'required|string|min:2',
        ], [
            'question.required' => 'برجاء كتابة سؤال أولاً.',
            'question.min' => 'يجب أن يكون السؤال من حرفين على الأقل.',
        ]);

        $userQuestion = trim($this->question);
        $this->question = '';

        // Add user message to chat
        $this->messages[] = [
            'sender' => 'user',
            'text' => $userQuestion,
        ];

        // Call AI Service – may return a pending action instead of a final response
        $aiService = app(AIAssistantService::class);
        $result = $aiService->ask($userQuestion, $this->messages);

        // If the AI detected a price-update action, hold for confirmation
        if (is_array($result) && isset($result['__pending_action'])) {
            $this->pendingAction = $result['__pending_action'];
            $this->pendingPreview = $result['preview'];

            $this->messages[] = [
                'sender' => 'ai',
                'text' => $result['preview'],
                'type' => 'pending_confirmation',
            ];
        } else {
            // Normal text response
            $this->pendingAction = null;
            $this->pendingPreview = '';

            $this->messages[] = [
                'sender' => 'ai',
                'text' => $result,
            ];
        }

        // Update counts
        $tenant = tenant();
        $this->requestsToday = $tenant->ai_requests_today ?? 0;

        $this->dispatch('scroll-to-bottom');
    }

    /**
     * User confirmed the pending price-update action.
     */
    public function confirmPendingAction(): void
    {
        if (! $this->pendingAction) {
            return;
        }

        $aiService = app(AIAssistantService::class);
        $finalResponse = $aiService->executePriceUpdate($this->pendingAction);

        $this->messages[] = [
            'sender' => 'ai',
            'text' => $finalResponse,
        ];

        $this->pendingAction = null;
        $this->pendingPreview = '';

        // Update counts
        $tenant = tenant();
        $this->requestsToday = $tenant->ai_requests_today ?? 0;

        $this->dispatch('scroll-to-bottom');
    }

    /**
     * User cancelled the pending price-update action.
     */
    public function cancelPendingAction(): void
    {
        $this->messages[] = [
            'sender' => 'ai',
            'text' => '✅ تم إلغاء عملية التعديل بنجاح. لم يتم تغيير أي أسعار.',
        ];

        $this->pendingAction = null;
        $this->pendingPreview = '';

        $this->dispatch('scroll-to-bottom');
    }
}
