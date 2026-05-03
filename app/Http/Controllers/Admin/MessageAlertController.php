<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\MessageAlert;
use App\Models\MessageAlertSectionItem;
use App\Support\MessageAlertType;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MessageAlertController extends Controller
{
    use Responser;

    public function index(Request $request)
    {
        try {
            $type = MessageAlertType::normalize($request->input('type'));

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

            $alerts = $query->paginate((int) $request->get('per_page', 20));

            return $this->apiResponse([
                'items' => array_map(
                    fn (MessageAlert $m) => $this->formatAlert($m),
                    $alerts->items()
                ),
                'pagination' => $this->paginate($alerts),
            ], trans('api.success'));
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $type = MessageAlertType::normalize($request->input('type'));
            $data = $request->validate($this->rules());
            $this->assertItemMatchesSectionType((int) $data['message_alert_section_item_id'], $type);
            $alert = MessageAlert::query()->create($data);
            $alert->load([
                'sectionItem:id,message_alert_section_id,name_ar,name_en',
                'sectionItem.section:id,name_ar,name_en,type',
            ]);

            return $this->apiResponse($this->formatAlert($alert), trans('api.created_successfully'), 201);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            $alert = MessageAlert::query()->with([
                'sectionItem:id,message_alert_section_id,name_ar,name_en',
                'sectionItem.section:id,name_ar,name_en,type',
            ])->findOrFail($id);

            if ($request->filled('type')) {
                $type = MessageAlertType::normalize($request->input('type'));
                if (($alert->sectionItem?->section?->type ?? null) !== $type) {
                    return $this->errorMessage(trans('api.not_found'), 404);
                }
            }

            return $this->apiResponse($this->formatAlert($alert), trans('api.success'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $type = MessageAlertType::normalize($request->input('type'));
            $alert = MessageAlert::query()->findOrFail($id);
            $data = $request->validate($this->rules(true));
            if (array_key_exists('message_alert_section_item_id', $data)) {
                $this->assertItemMatchesSectionType((int) $data['message_alert_section_item_id'], $type);
            } else {
                $this->assertItemMatchesSectionType((int) $alert->message_alert_section_item_id, $type);
            }
            $alert->update($data);
            $alert->load([
                'sectionItem:id,message_alert_section_id,name_ar,name_en',
                'sectionItem.section:id,name_ar,name_en,type',
            ]);

            return $this->apiResponse($this->formatAlert($alert), trans('api.updated_successfully'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function destroy(Request $request, int $id)
    {
        try {
            $type = MessageAlertType::normalize($request->input('type'));
            $alert = MessageAlert::query()->with('sectionItem.section')->findOrFail($id);
            $this->assertItemMatchesSectionType((int) $alert->message_alert_section_item_id, $type);
            $alert->delete();

            return $this->apiResponse([], trans('api.deleted_successfully'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    private function rules(bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes|required' : 'required';

        return [
            'message_alert_section_item_id' => "{$required}|exists:message_alert_section_items,id",
            'message' => "{$required}|string|max:10000",
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatAlert(MessageAlert $alert): array
    {
        $item = $alert->sectionItem;
        $section = $item?->section;

        return [
            'id' => $alert->id,
            'type' => $section?->type ?? MessageAlertType::CLIENT,
            'message' => $alert->message,
            'message_alert_section_id' => $item?->message_alert_section_id,
            'message_alert_section_item_id' => $alert->message_alert_section_item_id,
            'section' => $section ? [
                'id' => $section->id,
                'name_ar' => $section->name_ar,
                'name_en' => $section->name_en,
                'type' => $section->type,
            ] : null,
            'section_item' => $item ? [
                'id' => $item->id,
                'name_ar' => $item->name_ar,
                'name_en' => $item->name_en,
            ] : null,
            'created_at' => $alert->created_at,
            'updated_at' => $alert->updated_at,
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
}
