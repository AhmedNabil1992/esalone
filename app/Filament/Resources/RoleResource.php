<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages\CreateRole;
use App\Filament\Resources\RoleResource\Pages\EditRole;
use App\Filament\Resources\RoleResource\Pages\ListRoles;
use App\Filament\Resources\RoleResource\Pages\ViewRole;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource as BaseRoleResource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class RoleResource extends BaseRoleResource
{
    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'view' => ViewRole::route('/{record}'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    public static function getShieldFormComponents(): Component
    {
        $resources = FilamentShield::getResources();

        // 1. Group entities by their Filament Navigation Group
        $groupedEntities = [];
        foreach ($resources as $entity) {
            $fqcn = $entity['resourceFqcn'];
            $navGroup = 'عام / أخرى';
            if (class_exists($fqcn) && method_exists($fqcn, 'getNavigationGroup')) {
                $navGroup = $fqcn::getNavigationGroup() ?? 'عام / أخرى';
            }
            $groupedEntities[$navGroup][] = $entity;
        }

        // Order groups logically matching system navigation
        $groupOrder = [
            'اللعب',
            'إدارة الأجهزة',
            'الشيفتات',
            'إدارة المخزون',
            'المشتريات',
            'الإدارة المالية',
            'العملاء',
            'التقارير',
            'تقارير إدارية',
            'الإحصائيات',
            'إعدادات المستخدمين',
            'إعدادات النظام',
        ];

        $sortedGroupedEntities = [];
        foreach ($groupOrder as $gName) {
            if (isset($groupedEntities[$gName])) {
                $sortedGroupedEntities[$gName] = $groupedEntities[$gName];
                unset($groupedEntities[$gName]);
            }
        }
        foreach ($groupedEntities as $gName => $entities) {
            $sortedGroupedEntities[$gName] = $entities;
        }

        $tabs = [];

        // Build a Tab for each Navigation Group
        foreach ($sortedGroupedEntities as $groupName => $entities) {
            $sections = [];
            foreach ($entities as $entity) {
                $sectionLabel = strval(
                    static::shield()->hasLocalizedPermissionLabels()
                        ? FilamentShield::getLocalizedResourceLabel($entity['resourceFqcn'])
                        : $entity['model']
                );

                $sections[] = Section::make($sectionLabel)
                    ->compact()
                    ->schema([
                        static::getCheckBoxListComponentForResource($entity),
                    ])
                    ->collapsible();
            }

            $tabs[] = Tab::make($groupName)
                ->label($groupName)
                ->schema([
                    Grid::make()
                        ->schema($sections)
                        ->columns(2),
                ]);
        }

        // Add Pages and Widgets tabs
        $pageTab = static::getTabFormComponentForPage();
        if ($pageTab) {
            $tabs[] = $pageTab;
        }

        $widgetTab = static::getTabFormComponentForWidget();
        if ($widgetTab) {
            $tabs[] = $widgetTab;
        }

        $customPermissionsTab = static::getTabFormComponentForCustomPermissions();
        if ($customPermissionsTab) {
            $tabs[] = $customPermissionsTab;
        }

        return Tabs::make('Permissions')
            ->contained()
            ->tabs($tabs)
            ->columnSpan('full');
    }
}
