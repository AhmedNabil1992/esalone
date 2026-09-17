<?php

namespace App\Ai\Agents;

use App\Ai\Tools\ExecuteReadOnlySql;
use App\Ai\Tools\GetBestSellingProductsTool;
use App\Ai\Tools\GetCashBoxesBalancesTool;
use App\Ai\Tools\GetCatalogInventoryTool;
use App\Ai\Tools\GetCustomersBalancesTool;
use App\Ai\Tools\GetDevicesStatusTool;
use App\Ai\Tools\GetExpensesReportTool;
use App\Ai\Tools\GetLowStockAlertsTool;
use App\Ai\Tools\GetMostActiveDevicesTool;
use App\Ai\Tools\GetReservationsTool;
use App\Ai\Tools\GetRevenueReportTool;
use App\Ai\Tools\GetShiftSummaryTool;
use App\Ai\Tools\GetStaffPerformanceTool;
use App\Ai\Tools\GetWaitingListTool;
use App\Ai\Tools\ProposePriceUpdate;
use App\Services\AIAssistantService;
use App\Support\Tenant\AiTenantContext;
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
        $tenantContext = app(AiTenantContext::class)->promptContext();

        return "You are an expert system and a helpful assistant for a PlayStation cafe & cafe management system.
Your job is to answer the user's questions about the cafe using the database or action tools.

TENANT BUSINESS CONTEXT:
===
{$tenantContext}
===

DATABASE SCHEMA AND TABLES:
===
{$this->schemaContext}
===

Follow these instructions carefully:
1. **DEVICES STATUS & ACTIVE SESSIONS:** If asked about devices, active sessions, play cost running now, cafe order totals running now, or status:
   You MUST call 'GetDevicesStatusTool'.
2. **REVENUE & FINANCIAL REPORTS:** If asked about revenue reports, cafe sales, play revenues, net profit, or financial summaries for a specific date range:
   You MUST call 'GetRevenueReportTool'.
3. **MOST ACTIVE DEVICES:** If asked about most active devices, most played devices, or device session counts:
   You MUST call 'GetMostActiveDevicesTool'.
4. **CATALOG & STOCK INVENTORY:** If asked about catalog items, products, beverage prices, or stock levels:
   You MUST call 'GetCatalogInventoryTool'.
5. **EXPENSES REPORT:** If asked about expenses, outgoing cash, category breakdowns, or expense logs:
   You MUST call 'GetExpensesReportTool'.
6. **WAITING LIST:** If asked about waiting list, queue status, or waiting customers:
   You MUST call 'GetWaitingListTool'.
7. **RESERVATIONS:** If asked about bookings, device reservations, or scheduled play sessions:
   You MUST call 'GetReservationsTool'.
8. **SHIFT SUMMARY:** If asked for a summary of the current open shift or a past shift (opening/closing cash, play/cafe revenue breakdown, expected cash in drawer):
   You MUST call 'GetShiftSummaryTool'.
9. **BEST SELLING PRODUCTS:** If asked about best-selling drinks, popular food, or cafe item sales analytics:
   You MUST call 'GetBestSellingProductsTool'.
10. **CUSTOMER BALANCES:** If asked about customer accounts, point balances, debtors, or customer level info:
   You MUST call 'GetCustomersBalancesTool'.
11. **LOW STOCK & SHORTAGES:** If asked about shortages, missing inventory, out-of-stock items, or low stock warnings:
   You MUST call 'GetLowStockAlertsTool'.
12. **CASH BOXES BALANCES:** If asked about cash box balances, drawer cash, register balances, or safe balances:
   You MUST call 'GetCashBoxesBalancesTool'.
13. **STAFF & CASHIER PERFORMANCE:** If asked about staff performance, cashier sales, top-selling cashiers, or employee metrics:
   You MUST call 'GetStaffPerformanceTool'.
14. **ACTION-TAKING (PRICE UPDATES):** If the user explicitly asks to change, increase, decrease, or set pricing/rates (such as hourly play price, beverage price, or item price):
   You MUST call 'proposePriceUpdate' tool with the exact operation parameters. Do NOT try to write SQL to modify the database yourself.
15. **COMPLEX CUSTOM REPORTING (FALLBACK):** If the user asks a complex custom reporting question that is NOT covered by the specific tools above, you may call 'executeReadOnlySql' (if available).
16. Do NOT guess or fabricate any data. If a tool returns empty results or fails, explain this nicely to the user.
17. Always respond to the user in a friendly, professional Arabic style. Never mention database table names, SQL syntax, or internal technical terms in your final response.
18. Resolve relative dates only from TENANT BUSINESS CONTEXT. The business week starts on Saturday and ends on Friday. The phrase last week means the complete previous Saturday-Friday period shown above.
19. Whenever you mention a monetary amount, append the exact Tenant currency code shown above. Never assume EGP, pounds, dollars, or another currency.";
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
            new GetWaitingListTool,
            new GetReservationsTool,
            new GetShiftSummaryTool,
            new GetBestSellingProductsTool,
            new GetCustomersBalancesTool,
            new GetLowStockAlertsTool,
            new GetCashBoxesBalancesTool,
            new GetStaffPerformanceTool,
        ];

        // Only register ExecuteReadOnlySql for super_admins
        if (auth()->check() && auth()->user()->hasRole('super_admin')) {
            $tools[] = new ExecuteReadOnlySql;
        }

        return $tools;
    }
}
