<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PageContentController extends Controller
{
    use Responser;

    public function termsAndConditions()
    {
        try {
            $page = Page::query()->firstOrCreate(
                ['page' => 'term_and_condition'],
                ['description_ar' => '', 'description_en' => null]
            );

            return $this->apiResponse([
                'id' => $page->id,
                'page' => $page->page,
                'description_ar' => $page->description_ar,
                'description_en' => $page->description_en,
                'description' => $page->description_trans,
            ], trans('api.success'));
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function updateTermsAndConditions(Request $request)
    {
        return $this->updatePage($request, 'term_and_condition');
    }

    public function privacy()
    {
        try {
            $page = Page::query()->firstOrCreate(
                ['page' => 'privacy'],
                ['description_ar' => '', 'description_en' => null]
            );

            return $this->apiResponse([
                'id' => $page->id,
                'page' => $page->page,
                'description_ar' => $page->description_ar,
                'description_en' => $page->description_en,
                'description' => $page->description_trans,
            ], trans('api.success'));
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function updatePrivacy(Request $request)
    {
        return $this->updatePage($request, 'privacy');
    }

    private function updatePage(Request $request, string $pageKey)
    {
        try {
            $validated = $request->validate([
                'description_ar' => ['required', 'string'],
                'description_en' => ['nullable', 'string'],
            ]);

            $page = Page::query()->firstOrCreate(
                ['page' => $pageKey],
                ['description_ar' => '', 'description_en' => null]
            );
            $page->update($validated);

            return $this->apiResponse([
                'id' => $page->id,
                'page' => $page->page,
                'description_ar' => $page->description_ar,
                'description_en' => $page->description_en,
                'description' => $page->description_trans,
            ], trans('api.updated_successfully'));
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }
}
