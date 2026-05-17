<?php

namespace App\Services\Admin;

use App\Models\Permission;
use Illuminate\Support\Collection;

class RolePermissionResolver
{
    /**
     * @param  array<int>|null  $permissionIds
     * @param  array<string, array<int, string>>|null  $permissionMatrix  section_key => [actions]
     * @return array<int>
     */
    public function resolve(
        ?array $permissionIds,
        ?array $permissionMatrix,
        bool $activateAll = false
    ): array {
        if ($activateAll) {
            return Permission::query()
                ->where('is_active', true)
                ->pluck('id')
                ->all();
        }

        $ids = collect($permissionIds ?? []);

        if (! empty($permissionMatrix)) {
            $ids = $ids->merge($this->idsFromMatrix($permissionMatrix));
        }

        return $ids->unique()->filter()->values()->all();
    }

    /**
     * @param  array<string, array<int, string>>  $matrix
     * @return Collection<int, int>
     */
    protected function idsFromMatrix(array $matrix): Collection
    {
        $ids = collect();

        foreach ($matrix as $section => $actions) {
            $sectionKey = $this->normalizeSectionKey((string) $section);

            foreach ((array) $actions as $action) {
                $action = strtolower(trim((string) $action));
                if ($action === '') {
                    continue;
                }

                $permission = Permission::query()
                    ->where('section', $sectionKey)
                    ->where('action', $action)
                    ->first();

                if ($permission) {
                    $ids->push($permission->id);
                }
            }
        }

        return $ids;
    }

    public function normalizeSectionKey(string $section): string
    {
        $section = trim(strtolower($section));
        $section = preg_replace('/\s+/', '_', $section);
        $section = preg_replace('/[^a-z0-9_]/', '', $section);

        return $section !== '' ? $section : 'section';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function groupedPermissionsForForm(): array
    {
        $permissions = Permission::query()
            ->where('is_active', true)
            ->orderBy('section')
            ->orderBy('action')
            ->get()
            ->groupBy('section');

        return $permissions->map(function ($items, $section) {
            return [
                'section_key' => $section,
                'section_label' => config("permissions.sections.{$section}.ar", $items->first()?->section_trans ?? $section),
                'permissions' => $items->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'action' => $p->action,
                    'action_label' => $p->action_label_trans ?? config("permissions.actions.{$p->action}.ar", $p->action),
                ])->values()->all(),
            ];
        })->values()->all();
    }
}
