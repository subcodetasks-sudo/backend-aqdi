<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\MergesMessageAlertRequestAliases;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V2\Api\MessageAlertCreateFormResource;
use App\Http\Resources\Admin\V2\Api\MessageAlertResource;
use App\Http\Traits\Responser;
use App\Models\MessageAlert;
use App\Models\MessageAlertSection;
use App\Models\MessageAlertSectionItem;
use App\Support\MessageAlertType;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MessageAlertController extends Controller
{
    use MergesMessageAlertRequestAliases;
    use Responser;

    /**
     * Form data for "إضافة رسالة جديدة" (sections + items for dropdowns).
     * GET /api/admin/message-alerts/client/create
     * GET /api/admin/message-alerts/employee/create
     */
    public function create(Request $request, ?string $audience = null)
    {
        try {
            $type = $this->resolveAudience($request, $audience);

            $sections = MessageAlertSection::query()
                ->where('type', $type)
                ->with(['items' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            return $this->apiResponse(
                MessageAlertCreateFormResource::forAudience($type, $sections),
                trans('api.success')
            );
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function index(Request $request, ?string $audience = null)
    {
        try {
            $type = $this->resolveAudience($request, $audience);

            $query = MessageAlert::query()
                ->whereHas('sectionItem.section', fn ($q) => $q->where('type', $type))
                ->with([
                    'sectionItem:id,message_alert_section_id,name_ar,name_en',
                    'sectionItem.section:id,name_ar,name_en,type',
                ])
                ->latest();

            if ($request->filled('message_alert_section_id')) {
                $sid = (int) $request->input('message_alert_section_id');
                $query->whereHas('sectionItem', fn ($q) => $q->where('message_alert_section_id', $sid));
            }

            if ($request->filled('message_alert_section_item_id')) {
                $query->where(
                    'message_alert_section_item_id',
                    (int) $request->input('message_alert_section_item_id')
                );
            }

            if ($request->filled('search')) {
                $search = $request->string('search');
                $query->where('message', 'like', "%{$search}%");
            }

            $alerts = $query->paginate(min(max((int) $request->get('per_page', 20), 1), 100));

            return $this->apiResponse([
                'type' => $type,
                'items' => MessageAlertResource::collection($alerts),
                'pagination' => $this->paginate($alerts),
            ], trans('api.success'));
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function store(Request $request, ?string $audience = null)
    {
        try {
            $type = $this->resolveAudience($request, $audience);
            $this->mergeMessageAlertAliases($request);
            $this->mergeMessageAlertSectionIdFromItem($request);
            $data = $request->validate($this->rules());
            $this->assertItemBelongsToSection(
                (int) $data['message_alert_section_item_id'],
                (int) $data['message_alert_section_id']
            );
            $this->assertItemMatchesSectionType((int) $data['message_alert_section_item_id'], $type);

            $alert = MessageAlert::query()->create(
                collect($data)->only(['message_alert_section_item_id', 'message'])->all()
            );
            $alert->load([
                'sectionItem:id,message_alert_section_id,name_ar,name_en',
                'sectionItem.section:id,name_ar,name_en,type',
            ]);

            return $this->apiResponse(
                new MessageAlertResource($alert),
                trans('api.created_successfully'),
                201
            );
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function show(Request $request, int|string $idOrAudience, ?int $id = null)
    {
        try {
            [$audience, $alertId] = $this->resolveRouteAudienceAndId($idOrAudience, $id);
            $type = $this->resolveAudience($request, $audience);
            $alert = $this->findAlertForAudience($alertId, $type);

            return $this->apiResponse(new MessageAlertResource($alert), trans('api.success'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function update(Request $request, int|string $idOrAudience, ?int $id = null)
    {
        try {
            [$audience, $alertId] = $this->resolveRouteAudienceAndId($idOrAudience, $id);
            $type = $this->resolveAudience($request, $audience);
            $alert = MessageAlert::query()->findOrFail($alertId);
            $alert->loadMissing('sectionItem.section');

            if (($alert->sectionItem?->section?->type ?? null) !== $type) {
                return $this->errorMessage(trans('api.not_found'), 404);
            }

            $this->mergeMessageAlertAliases($request);
            $this->mergeMessageAlertSectionIdFromItem($request);
            $data = $request->validate($this->rules(true));

            $itemId = (int) ($data['message_alert_section_item_id'] ?? $alert->message_alert_section_item_id);
            if (array_key_exists('message_alert_section_id', $data)) {
                $sectionId = (int) $data['message_alert_section_id'];
            } elseif (array_key_exists('message_alert_section_item_id', $data)) {
                $linkedItem = MessageAlertSectionItem::query()->find($itemId);
                $sectionId = $linkedItem ? (int) $linkedItem->message_alert_section_id : 0;
            } else {
                $sectionId = (int) ($alert->sectionItem?->message_alert_section_id ?? 0);
            }

            if (array_key_exists('message_alert_section_item_id', $data) || array_key_exists('message_alert_section_id', $data)) {
                $this->assertItemBelongsToSection($itemId, $sectionId);
            }
            $this->assertItemMatchesSectionType($itemId, $type);

            $alert->update(collect($data)->only(['message_alert_section_item_id', 'message'])->all());
            $alert->load([
                'sectionItem:id,message_alert_section_id,name_ar,name_en',
                'sectionItem.section:id,name_ar,name_en,type',
            ]);

            return $this->apiResponse(new MessageAlertResource($alert), trans('api.updated_successfully'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function destroy(Request $request, int|string $idOrAudience, ?int $id = null)
    {
        try {
            [$audience, $alertId] = $this->resolveRouteAudienceAndId($idOrAudience, $id);
            $type = $this->resolveAudience($request, $audience);
            $alert = $this->findAlertForAudience($alertId, $type);
            $alert->delete();

            return $this->apiResponse([], trans('api.deleted_successfully'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    private function findAlertForAudience(int $id, string $type): MessageAlert
    {
        return MessageAlert::query()
            ->whereHas('sectionItem.section', fn ($q) => $q->where('type', $type))
            ->with([
                'sectionItem:id,message_alert_section_id,name_ar,name_en',
                'sectionItem.section:id,name_ar,name_en,type',
            ])
            ->findOrFail($id);
    }

    private function resolveAudience(Request $request, ?string $routeAudience): string
    {
        if ($routeAudience !== null && $routeAudience !== '') {
            return MessageAlertType::normalize($routeAudience);
        }

        return MessageAlertType::normalize($request->input('type'));
    }

    /**
     * Legacy: /message-alerts/{id} — first arg is id.
     * Audience: /message-alerts/{audience}/{id} — first arg is audience, second is id.
     *
     * @return array{0: ?string, 1: int}
     */
    private function resolveRouteAudienceAndId(int|string $idOrAudience, ?int $id): array
    {
        if ($id !== null) {
            return [(string) $idOrAudience, $id];
        }

        return [null, (int) $idOrAudience];
    }

    private function rules(bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes|required' : 'required';

        return [
            'message_alert_section_id' => "{$required}|exists:message_alert_sections,id",
            'message_alert_section_item_id' => "{$required}|exists:message_alert_section_items,id",
            'message' => "{$required}|string|max:10000",
        ];
    }

    private function assertItemMatchesSectionType(int $messageAlertSectionItemId, string $type): void
    {
        $ok = MessageAlertSectionItem::query()->whereKey($messageAlertSectionItemId)
            ->whereHas('section', fn ($q) => $q->where('type', $type))
            ->exists();

        if (! $ok) {
            throw ValidationException::withMessages([
                'message_alert_section_item_id' => [__('The selected section item does not exist for this audience type.')],
            ]);
        }
    }

    private function assertItemBelongsToSection(int $messageAlertSectionItemId, int $messageAlertSectionId): void
    {
        $ok = MessageAlertSectionItem::query()
            ->whereKey($messageAlertSectionItemId)
            ->where('message_alert_section_id', $messageAlertSectionId)
            ->exists();

        if (! $ok) {
            throw ValidationException::withMessages([
                'message_alert_section_item_id' => [__('The selected item does not belong to this section.')],
                'message_alert_section_id' => [__('The selected item does not belong to this section.')],
            ]);
        }
    }

    private function mergeMessageAlertSectionIdFromItem(Request $request): void
    {
        if ($request->filled('message_alert_section_id')) {
            return;
        }

        $itemId = $request->input('message_alert_section_item_id');
        if ($itemId === null || $itemId === '') {
            return;
        }

        $sectionId = MessageAlertSectionItem::query()
            ->whereKey((int) $itemId)
            ->value('message_alert_section_id');

        if ($sectionId !== null) {
            $request->merge(['message_alert_section_id' => (int) $sectionId]);
        }
    }
}
