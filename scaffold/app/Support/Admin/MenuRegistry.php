<?php

namespace App\Support\Admin;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

/**
 * Menu d'administration exposé en prop partagée Inertia (`admin.menu`).
 *
 * Lit config/admin/menu/*.php (une entrée par module : label, route, icon, group, order, permission)
 * et config/admin/menu-groups.php. Filtre par permission (Gate), ignore les routes absentes,
 * groupe et trie. Les entrées stockent le nom de route : compatible avec config:cache.
 */
class MenuRegistry
{
    /**
     * @return list<array{key: string, label: string, items: list<array{label: string, url: string, icon: string}>}>
     */
    public function forUser(?Authenticatable $user): array
    {
        $groups = (array) config('admin.menu-groups', []);
        $items = [];

        foreach ((array) config('admin.menu', []) as $entry) {
            if (! is_array($entry) || ! isset($entry['route'], $entry['label']) || ! Route::has($entry['route'])) {
                continue;
            }

            if (! $this->allowed($user, $entry['permission'] ?? null)) {
                continue;
            }

            $items[$entry['group'] ?? 'main'][] = [
                'label' => (string) $entry['label'],
                'url' => route($entry['route'], absolute: false),
                'icon' => (string) ($entry['icon'] ?? 'folder'),
                'order' => (int) ($entry['order'] ?? 100),
            ];
        }

        $menu = [];

        foreach ($items as $key => $groupItems) {
            usort($groupItems, fn (array $a, array $b) => [$a['order'], $a['label']] <=> [$b['order'], $b['label']]);

            $menu[] = [
                'key' => (string) $key,
                'label' => (string) ($groups[$key]['label'] ?? ucfirst((string) $key)),
                'order' => (int) ($groups[$key]['order'] ?? 100),
                'items' => array_map(fn (array $item) => array_diff_key($item, ['order' => true]), $groupItems),
            ];
        }

        usort($menu, fn (array $a, array $b) => [$a['order'], $a['label']] <=> [$b['order'], $b['label']]);

        return array_map(fn (array $group) => array_diff_key($group, ['order' => true]), $menu);
    }

    private function allowed(?Authenticatable $user, mixed $permission): bool
    {
        if ($permission === null || $permission === '') {
            return true;
        }

        return $user !== null && Gate::forUser($user)->allows((string) $permission);
    }
}
