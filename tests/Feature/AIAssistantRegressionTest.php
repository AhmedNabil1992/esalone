<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Ai\Agents\PlayStationAssistant;
use App\Ai\Tools\ExecuteReadOnlySql;
use App\Ai\Tools\GetApplicationHelpTool;
use App\Ai\Tools\GetClosedDevicesByUserTool;
use App\Ai\Tools\GetItemOrderLocationsTool;
use App\Ai\Tools\GetPaymentMethodsSummaryTool;
use App\Support\ArabicSearch;
use Laravel\Ai\Gateway\TextGenerationOptions;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class AIAssistantRegressionTest extends TestCase
{
    public function test_arabic_search_matches_common_spelling_variants(): void
    {
        $this->assertTrue(ArabicSearch::matches('قهوه فرنساوي', 'القهوة الفرنساوي'));
        $this->assertTrue(ArabicSearch::matches('أجهزة البلايستيشن', 'اجهزة البلايستيشن'));
        $this->assertFalse(ArabicSearch::matches('شاي فتلة', 'قهوة فرنساوي'));
    }

    public function test_agent_exposes_only_relevant_tools_for_production_regressions(): void
    {
        $itemTools = $this->toolClasses('محتاج الترابيزات اللي نزل عليها قهوة فرنساوي');
        $this->assertContains(GetItemOrderLocationsTool::class, $itemTools);
        $this->assertCount(1, $itemTools);
        $this->assertNotContains(ExecuteReadOnlySql::class, $itemTools);

        $closedTools = $this->toolClasses('الترابيزات اللي اتقفلت في الوردية رقم 6 اسم المستخدم عبدالرحمن');
        $this->assertContains(GetClosedDevicesByUserTool::class, $closedTools);
        $this->assertCount(1, $closedTools);
        $this->assertNotContains(ExecuteReadOnlySql::class, $closedTools);

        $paymentTools = $this->toolClasses('محتاج الفلوس اللي على الفيزا في الشيفت الحالي');
        $this->assertContains(GetPaymentMethodsSummaryTool::class, $paymentTools);
        $this->assertCount(1, $paymentTools);
        $this->assertNotContains(ExecuteReadOnlySql::class, $paymentTools);

        $permissionTools = $this->toolClasses('محتاج أعرف مكان صلاحية المبيعات المباشرة');
        $this->assertContains(GetApplicationHelpTool::class, $permissionTools);
    }

    public function test_agent_has_strict_generation_limits_and_disables_generated_titles(): void
    {
        $options = TextGenerationOptions::forAgent(new PlayStationAssistant('مبيعات اليوم'));

        $this->assertSame(3, $options->maxSteps);
        $this->assertSame(500, $options->maxTokens);
        $this->assertSame(0.1, $options->temperature);
        $this->assertFalse(config('ai.conversations.generate_title'));
        $this->assertFalse(config('ai.assistant.allow_read_only_sql'));
    }

    public function test_application_help_uses_real_navigation_labels(): void
    {
        $result = json_decode((string) (new GetApplicationHelpTool)->handle(new Request([
            'topic' => 'صلاحية المبيعات المباشرة',
        ])), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('إعدادات المستخدمين', $result['paths']['user_direct_permissions']['navigation_group']);
        $this->assertSame('المستخدمين', $result['paths']['user_direct_permissions']['screen']);
        $this->assertContains('الصلاحيات المباشرة', $result['paths']['user_direct_permissions']['steps']);
    }

    /**
     * @return array<int, class-string>
     */
    private function toolClasses(string $question): array
    {
        return collect((new PlayStationAssistant($question))->tools())
            ->map(fn (object $tool): string => $tool::class)
            ->values()
            ->all();
    }
}
