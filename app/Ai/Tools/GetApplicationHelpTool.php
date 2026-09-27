<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetApplicationHelpTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'شرح مسارات شاشات النظام الفعلية، خصوصاً المستخدمين والصلاحيات والمبيعات المباشرة وإضافة الأصناف.';
    }

    public function handle(Request $request): Stringable|string
    {
        return json_encode([
            'status' => 'ok',
            'paths' => [
                'user_direct_permissions' => [
                    'navigation_group' => 'إعدادات المستخدمين',
                    'screen' => 'المستخدمين',
                    'steps' => ['اختيار المستخدم', 'تعديل', 'التحكم في الوصول', 'الصلاحيات المباشرة'],
                    'permission' => 'ViewAny:Invoice',
                    'note' => 'هذه هي صلاحية عرض مورد الفواتير الذي تعتمد عليه شاشة المبيعات المباشرة، ويمكن منحها مباشرة أو من خلال دور.',
                ],
                'direct_sales' => [
                    'navigation_group' => 'اللعب',
                    'screen' => 'المبيعات المباشرة',
                ],
                'catalog_items' => [
                    'navigation_group' => 'المخزون',
                    'screen' => 'الأصناف',
                    'note' => 'إضافة وتعديل الصنف وأسعار البيع تتم من شاشة الأصناف.',
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'topic' => $schema->string()
                ->description('الموضوع المطلوب شرحه، مثل صلاحية المبيعات المباشرة أو إضافة صنف.'),
        ];
    }
}
