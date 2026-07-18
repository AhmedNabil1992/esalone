<?php

namespace App\Ai\Agents;

use App\Ai\Tools\ExecuteReadOnlySql;
use App\Ai\Tools\GetCatalogInventoryTool;
use App\Ai\Tools\GetDevicesStatusTool;
use App\Ai\Tools\GetExpensesReportTool;
use App\Ai\Tools\GetMostActiveDevicesTool;
use App\Ai\Tools\GetRevenueReportTool;
use App\Ai\Tools\ProposePriceUpdate;
use App\Services\AIAssistantService;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::Gemini)]
#[Model('gemini-3.1-flash-lite')]
class PlayStationAssistant implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    protected string $schemaContext = '';

    /**
     * Constructor to dynamically accept the user's question and load filtered database schemas.
     */
    public function __construct(string $question = '')
    {
        if ($question !== '') {
            $this->schemaContext = app(AIAssistantService::class)->getSchemaForQuestion($question);
        }
    }

    /**
     * Get the system instructions that the agent should follow.
     */
    public function instructions(): string
    {
        return "You are an expert system and a helpful assistant for a PlayStation cafe & cafe management system.
Your job is to answer the user's questions about the cafe using the database or action tools.

DATABASE SCHEMA AND TABLES:
===
{$this->schemaContext}
===

Follow these instructions carefully:
1. **DEVICES STATUS & ACTIVE SESSIONS:** If asked about devices, active sessions, play cost running now, cafe order totals running now, or status:
   You MUST call 'GetDevicesStatusTool'.
2. **REVENUE & FINANCIAL REPORTS:** If asked about revenue reports, cafe sales, play revenues, net profit, shift revenues, or financial summaries:
   You MUST call 'GetRevenueReportTool'.
3. **MOST ACTIVE DEVICES:** If asked about most active devices, most played devices, or device session counts:
   You MUST call 'GetMostActiveDevicesTool'.
4. **CATALOG & STOCK INVENTORY:** If asked about catalog items, products, beverage prices, or stock levels:
   You MUST call 'GetCatalogInventoryTool'.
5. **EXPENSES REPORT:** If asked about expenses, outgoing cash, category breakdowns, or expense logs:
   You MUST call 'GetExpensesReportTool'.
6. **ACTION-TAKING (PRICE UPDATES):** If the user explicitly asks to change, increase, decrease, or set pricing/rates (such as hourly play price, beverage price, or item price):
   You MUST call 'proposePriceUpdate' tool with the exact operation parameters. Do NOT try to write SQL to modify the database yourself.
7. **COMPLEX CUSTOM REPORTING (FALLBACK):** If the user asks a complex custom reporting question that is NOT covered by the specific tools above, you may call 'executeReadOnlySql' (if available).
8. Do NOT guess or fabricate any data. If a tool returns empty results or fails, explain this nicely to the user.
9. Always respond to the user in a friendly, professional Arabic style. Never mention database table names, SQL syntax, or internal technical terms in your final response.";
    }

    /**
     * Get the tools available to the agent.
     *
     * @return array<Tool>
     */
    public function tools(): iterable
    {
        $tools = [
            new ProposePriceUpdate,
            new GetDevicesStatusTool,
            new GetRevenueReportTool,
            new GetMostActiveDevicesTool,
            new GetCatalogInventoryTool,
            new GetExpensesReportTool,
        ];

        // Only register ExecuteReadOnlySql for super_admins
        if (auth()->check() && auth()->user()->hasRole('super_admin')) {
            $tools[] = new ExecuteReadOnlySql;
        }

        return $tools;
    }
}
