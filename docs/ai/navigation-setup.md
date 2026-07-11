# Custom App Launcher & Top Sub-Navigation Setup Guide (Filament v3)

This guide explains how to implement the custom app launcher navigation system (where navigation groups act as "Apps" in a grid launcher, and active group items appear horizontally at the top).

---

## 1. Customizing the Views

To enable the app launcher menu and top horizontal tabs, you must override the default Filament topbar and sidebar layouts.

Copy or create the following files in `resources/views/vendor/filament-panels/livewire/`:

### A. Topbar Customization (`topbar.blade.php`)
This file triggers the grid launcher icon, displays the popup apps menu, and renders the active group's items as top navigation tabs.

**Key Changes:**
1. Check the active panel ID (e.g. `admin`, `customer`) to enable the layout:
   ```php
   $isAdminPanel = in_array(filament()->getCurrentPanel()->getId(), ['admin', 'customer']);
   ```
2. Render the app launcher button (`icon-menu`) and loop through `$navigation` to show the grid:
   ```blade
   @if ($isAdminPanel)
       <x-filament::dropdown placement="bottom-start" teleport width="sm">
           <x-slot name="trigger">
               <x-filament::icon-button icon="icon-menu" />
           </x-slot>

           <div class="grid grid-cols-2 gap-2 overflow-y-auto p-4 md:grid-cols-3" style="max-height: 80vh;">
               @foreach ($navigation as $group)
                   @php
                       $groupLabel = $group->getLabel();
                       $groupIcon = $group->getIcon();
                       $itemUrl = $group->getItems()->first()?->getUrl();
                   @endphp

                   @if (! $groupLabel || ! $itemUrl || ! $groupIcon)
                       @continue
                   @endif

                   <div @class(['fi-topbar-item', 'fi-active' => $group->isActive()])>
                       <a href="{{ $itemUrl }}" class="fi-topbar-item-btn flex flex-col items-center justify-center p-4">
                           <x-filament::icon :icon="$groupIcon" style="height: 64px; width: 64px" />
                           {{ $groupLabel }}
                       </a>
                   </div>
               @endforeach
           </div>
       </x-filament::dropdown>
   @endif
   ```
3. Inside `<ul class="fi-topbar-nav-groups">`, skip inactive groups so only the current active "App" is shown:
   ```php
   if ($isAdminPanel && ! $isGroupActive) {
       continue;
   }
   ```

### B. Sidebar Customization (`sidebar.blade.php`)
This ensures the sidebar navigation is synchronized with the new launcher layout (only rendering the active group's sub-navigation).

**Key Changes:**
1. Determine if the custom layout is active:
   ```php
   $isAdminPanel = in_array(filament()->getCurrentPanel()->getId(), ['admin', 'customer']);
   ```
2. Filter the navigation list inside the loop:
   ```php
   if ($isAdminPanel && ! $isGroupActive) {
       continue;
   }
   ```

---

## 2. Configuring the Panel Provider

Your panel provider (e.g., `CustomerPanelProvider.php`) must be configured to use top navigation and define the navigation groups along with their custom launcher icons.

**Example Panel Configuration:**
```php
public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->topNavigation() // Required to enable top horizontal bar
        ->navigationGroups([
            NavigationGroup::make()
                ->label(fn (): string => __('admin.navigation.dashboard'))
                ->icon('icon-dashboard'),
            NavigationGroup::make()
                ->label(fn (): string => __('admin.navigation.wifi'))
                ->icon('heroicon-o-wifi'),
            NavigationGroup::make()
                ->label(fn (): string => __('admin.navigation.accounting'))
                ->icon('icon-accounting'),
        ]);
}
```

---

## 3. Organizing Resources, Pages, and Clusters

For pages and resources to appear under their respective app launchers:

1. **Pages/Resources**: Override the `getNavigationGroup()` method to match the registered navigation group label:
   ```php
   public static function getNavigationGroup(): ?string
   {
       return __('admin.navigation.dashboard'); // Groups this page under "Dashboard"
   }
   ```
2. **Clusters**: If utilizing clusters, define `getNavigationGroup()` inside the Cluster class:
   ```php
   class WiFiCluster extends Cluster
   {
       public static function getNavigationGroup(): string
       {
           return __('admin.navigation.wifi'); // Groups all cluster components under "WiFi"
       }
   }
   ```

---

## 4. Positioning Clustered Sub-Navigation Tabs to the Top

By default, Filament places clustered resource tabs (e.g., List, Create, View) in a left sidebar. To move them to the top of the screen and eliminate the sidebar:

1. Import `SubNavigationPosition` in the Resource or Custom Page:
   ```php
   use Filament\Pages\Enums\SubNavigationPosition;
   ```
2. Add the `$subNavigationPosition` property to the class:
   ```php
   protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
   ```
