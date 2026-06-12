<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V2\Api\PageContentResource;
use App\Http\Traits\Responser;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SettingController extends Controller
{
    use Responser;

    /**
     * All app settings: general config, social links, legal pages, images.
     *
     * GET /api/admin/settings
     */
    public function index()
    {
        try {
            $setting = $this->resolveSettingRow();
            $terms = $this->resolveLegalPage('term_and_condition');
            $privacy = $this->resolveLegalPage('privacy');

            return $this->apiResponse(
                $this->formatSettingsPayload($setting, $terms, $privacy),
                trans('api.success')
            );
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * Update settings, social links, and optional banner/cover images.
     *
     * POST /api/admin/settings
     */
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'whatsapp' => ['nullable', 'string', 'max:255'],
                'instagram' => ['nullable', 'string', 'max:255'],
                'twitter' => ['nullable', 'string', 'max:255'],
                'snapchat' => ['nullable', 'string', 'max:255'],
                'facebook' => ['nullable', 'string', 'max:255'],
                'tiktok' => ['nullable', 'string', 'max:255'],
                'linkedIn' => ['nullable', 'string', 'max:255'],
                'whatsapp_contact' => ['nullable', 'string', 'max:255'],
                'whatsapp_contract' => ['nullable', 'string', 'max:255'],
                'housing_tax' => ['nullable', 'numeric', 'min:0'],
                'commercial_tax' => ['nullable', 'numeric', 'min:0'],
                'application_fees' => ['nullable', 'numeric', 'min:0'],
                'open_payment' => ['nullable', 'boolean'],
                'version' => ['nullable', 'string', 'max:50'],
                'time_to_documentation_contract' => ['nullable', 'integer', 'min:0'],
                'text_message_user' => ['nullable', 'string'],
                'text_message_admin' => ['nullable', 'string'],
                'is_open' => ['nullable', 'boolean'],
                'working_hours' => ['nullable', 'string', 'max:500'],
                'image_banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
                'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            ]);

            $setting = $this->resolveSettingRow();

            if ($request->hasFile('image_banner')) {
                if ($setting->banner) {
                    deleteFile($setting->banner);
                }
                $validated['banner'] = fileUploader($request->file('image_banner'), 'settings');
            }

            if ($request->hasFile('cover')) {
                if ($setting->cover) {
                    deleteFile($setting->cover);
                }
                $validated['cover'] = fileUploader($request->file('cover'), 'settings');
            }

            unset($validated['image_banner']);

            $setting->update($validated);

            $terms = $this->resolveLegalPage('term_and_condition');
            $privacy = $this->resolveLegalPage('privacy');

            return $this->apiResponse(
                $this->formatSettingsPayload($setting->fresh(), $terms, $privacy),
                trans('api.updated_successfully')
            );
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    private function resolveSettingRow(): Setting
    {
        return Setting::query()->first() ?? Setting::query()->create([]);
    }

    private function resolveLegalPage(string $pageKey): Page
    {
        return Page::query()->firstOrCreate(
            ['page' => $pageKey],
            ['description_ar' => '', 'description_en' => null]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function formatSettingsPayload(Setting $setting, Page $terms, Page $privacy): array
    {
        return [
            'settings' => [
                'id' => $setting->id,
                'housing_tax' => $setting->housing_tax,
                'commercial_tax' => $setting->commercial_tax,
                'application_fees' => $setting->application_fees,
                'open_payment' => (bool) $setting->open_payment,
                'version' => $setting->version,
                'time_to_documentation_contract' => $setting->time_to_documentation_contract,
                'text_message_user' => $setting->text_message_user,
                'text_message_admin' => $setting->text_message_admin,
                'is_open' => isset($setting->is_open) ? (bool) $setting->is_open : null,
                'working_hours' => $setting->working_hours,
            ],
            'social' => [
                'whatsapp' => $setting->whatsapp ?? '',
                'instagram' => $setting->instagram ?? '',
                'twitter' => $setting->twitter ?? '',
                'snapchat' => $setting->snapchat ?? '',
                'facebook' => $setting->facebook ?? '',
                'tiktok' => $setting->tiktok ?? '',
                'linkedIn' => $setting->linkedIn ?? '',
                'whatsapp_contact' => $setting->whatsapp_contact ?? '',
                'whatsapp_contract' => $setting->whatsapp_contract ?? '',
            ],
            'terms' => new PageContentResource($terms),
            'privacy' => new PageContentResource($privacy),
            'image_banner' => $this->formatImageField($setting->banner),
            'cover' => $this->formatImageField($setting->cover),
        ];
    }

    /**
     * @return array{path: ?string, url: ?string}
     */
    private function formatImageField(?string $path): array
    {
        return [
            'path' => $path,
            'url' => $path ? url("storage/{$path}") : null,
        ];
    }
}
