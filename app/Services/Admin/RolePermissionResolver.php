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

                $permission = $this->findOrCreatePermission($sectionKey, $action);

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
        return $this->allModulesForForm();
    }

    /**
     * Full permission grid: every section × every action (for add/edit role UI).
     *
     * @return array<int, array<string, mixed>>
     */
    public function allModulesForForm(): array
    {
        $sections = config('permissions.sections', []);
        $actions = config('permissions.actions', []);
        $existing = Permission::query()
            ->where('is_active', true)
            ->get()
            ->groupBy('section');

        $modules = [];

        foreach ($sections as $sectionKey => $sectionLabels) {
            $sectionPermissions = $existing->get($sectionKey, collect());
            $actionRows = [];

            foreach ($actions as $actionKey => $actionLabels) {
                $permission = $sectionPermissions->firstWhere('action', $actionKey);

                $actionRows[] = [
                    'action' => $actionKey,
                    'action_label_ar' => $actionLabels['ar'] ?? $actionKey,
                    'action_label_en' => $actionLabels['en'] ?? $actionKey,
                    'permission_id' => $permission?->id,
                    'permission_name' => $permission?->name ?? "{$sectionKey}.{$actionKey}",
                ];
            }

            $modules[] = [
                'section_key' => $sectionKey,
                'section_label_ar' => $sectionLabels['ar'] ?? $sectionKey,
                'section_label_en' => $sectionLabels['en'] ?? $sectionKey,
                'actions' => $actionRows,
            ];
        }

        return $modules;
    }

    /**
     * Empty permission_matrix keyed by every section (for POST body shape).
     *
     * @return array<string, array<int, string>>
     */
    public function permissionMatrixTemplate(): array
    {
        $template = [];

        foreach (array_keys(config('permissions.sections', [])) as $sectionKey) {
            $template[$sectionKey] = [];
        }

        return $template;
    }

    public function findOrCreatePermission(string $sectionKey, string $action): ?Permission
    {
        $sectionKey = $this->normalizeSectionKey($sectionKey);
        $action = strtolower(trim($action));

        if ($action === '') {
            return null;
        }

        $name = "{$sectionKey}.{$action}";
        $sectionLabels = config("permissions.sections.{$sectionKey}", []);
        $actionLabels = config("permissions.actions.{$action}", []);

        return Permission::query()->firstOrCreate(
            ['name' => $name],
            [
                'section' => $sectionKey,
                'section_en' => $sectionLabels['en'] ?? $sectionKey,
                'action' => $action,
                'action_label_ar' => $actionLabels['ar'] ?? $action,
                'action_label_en' => $actionLabels['en'] ?? $action,
                'is_active' => true,
            ]
        );
    }

    /**
     * Ensure every section × action exists in DB (run once via seeder or create form).
     *
     * @return int Number of permissions created
     */
    public function syncAllPermissionsFromConfig(): int
    {
        $created = 0;

        foreach (array_keys(config('permissions.sections', [])) as $sectionKey) {
            foreach (array_keys(config('permissions.actions', [])) as $actionKey) {
                $permission = Permission::query()
                    ->where('name', "{$sectionKey}.{$actionKey}")
                    ->first();

                if (! $permission) {
                    $this->findOrCreatePermission($sectionKey, $actionKey);
                    $created++;
                }
            }
        }

        return $created;
    }
}
