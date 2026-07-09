<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\ContentPage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ContentPageController extends Controller
{
    use Responser;

    private const SUPPORTED_PAGES = ['home', 'about'];

    public function show(string $pageKey)
    {
        try {
            $pageKey = $this->normalizePageKey($pageKey);

            $page = ContentPage::query()->firstOrCreate(
                ['page_key' => $pageKey],
                ['content_json' => $this->defaultContentFor($pageKey)]
            );

            return $this->apiResponse(
                $this->buildResponsePayload($pageKey, $page),
                $this->fetchMessageFor($pageKey)
            );
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function upsert(Request $request, string $pageKey)
    {
        try {
            $pageKey = $this->normalizePageKey($pageKey);

            $page = ContentPage::query()->firstOrCreate(
                ['page_key' => $pageKey],
                ['content_json' => $this->defaultContentFor($pageKey)]
            );

            $existingContent = is_array($page->content_json) ? $page->content_json : [];
            $baseContent = $this->mergeAssocRecursive($this->defaultContentFor($pageKey), $existingContent);

            $incomingSections = $this->extractSectionsPayload($request);
            $incomingSections = $this->mergeAssocRecursive(
                $incomingSections,
                $this->normalizeUploadedFiles($request->allFiles(), $pageKey)
            );

            $mergedSections = $this->mergeAssocRecursive(
                Arr::get($baseContent, 'sections', []),
                $incomingSections
            );

            $content = [
                'page' => $pageKey,
                'sections' => $mergedSections,
            ];

            $page->update([
                'content_json' => $content,
            ]);

            return $this->apiResponse(
                $this->buildResponsePayload($pageKey, $page->fresh()),
                $this->saveMessageFor($pageKey)
            );
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    private function buildResponsePayload(string $pageKey, ContentPage $page): array
    {
        $payload = $this->formatPageResponse($pageKey, $page->content_json);
        $payload['updated_at'] = optional($page->updated_at)->toAtomString() ?? '';

        return $payload;
    }

    private function normalizePageKey(string $pageKey): string
    {
        $pageKey = trim(strtolower($pageKey));

        if (!in_array($pageKey, self::SUPPORTED_PAGES, true)) {
            throw ValidationException::withMessages([
                'page_key' => ['The selected page key is invalid.'],
            ]);
        }

        return $pageKey;
    }

    private function formatPageResponse(string $pageKey, mixed $content): array
    {
        $content = is_array($content) ? $content : $this->defaultContentFor($pageKey);
        $content = $this->mergeAssocRecursive($this->defaultContentFor($pageKey), $content);
        $content = $this->normalizeNullValues($content);

        return [
            'page' => $pageKey,
            'sections' => Arr::get($content, 'sections', []),
        ];
    }

    private function extractSectionsPayload(Request $request): array
    {
        $payload = $request->except(['page']);

        foreach (array_keys($payload) as $key) {
            if (str_starts_with($key, 'deleted_')) {
                unset($payload[$key]);
            }
        }

        return $this->sanitizeNode($payload);
    }

    private function sanitizeNode(mixed $node): mixed
    {
        if (!is_array($node)) {
            return $node;
        }

        $sanitized = [];
        foreach ($node as $key => $value) {
            if ($value instanceof UploadedFile) {
                continue;
            }

            $sanitized[$key] = $this->sanitizeNode($value);
        }

        return $sanitized;
    }

    private function normalizeUploadedFiles(array $files, string $pageKey, array $path = []): array
    {
        $normalized = [];

        foreach ($files as $key => $value) {
            $currentPath = [...$path, (string) $key];

            if ($value instanceof UploadedFile) {
                $normalized[$this->uploadedFileTargetKey((string) $key)] = $this->storeUploadedFile(
                    $value,
                    $pageKey,
                    $currentPath
                );

                if ($this->uploadedFileTargetKey((string) $key) === 'license_file_url') {
                    $normalized['license_file_type'] = $this->detectLicenseType($value);
                }

                continue;
            }

            if (is_array($value)) {
                $normalized[$key] = $this->normalizeUploadedFiles($value, $pageKey, $currentPath);
            }
        }

        return $normalized;
    }

    private function uploadedFileTargetKey(string $key): string
    {
        return match ($key) {
            'image' => 'image_url',
            'license_file' => 'license_file_url',
            default => $key . '_url',
        };
    }

    private function storeUploadedFile(UploadedFile $file, string $pageKey, array $path): string
    {
        $segments = array_map(function (string $segment): string {
            return preg_replace('/[^A-Za-z0-9\-_]/', '-', $segment) ?: 'field';
        }, $path);

        $directory = 'uploads/content-pages/' . $pageKey . '/' . implode('/', array_slice($segments, 0, -1));
        $storedPath = $file->store($directory, 'public');

        return asset('storage/' . $storedPath);
    }

    private function detectLicenseType(UploadedFile $file): string
    {
        return strtolower($file->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
    }

    private function mergeAssocRecursive(mixed $existing, mixed $incoming): mixed
    {
        if (!is_array($incoming)) {
            return $incoming;
        }

        if (!$this->isAssoc($incoming)) {
            return $this->mergeList($existing, $incoming);
        }

        $result = is_array($existing) ? $existing : [];

        foreach ($incoming as $key => $value) {
            if (str_starts_with((string) $key, 'keep_')) {
                continue;
            }

            $result[$key] = array_key_exists($key, $result)
                ? $this->mergeAssocRecursive($result[$key], $value)
                : $value;
        }

        return $this->applyKeepFlags($result, is_array($existing) ? $existing : [], $incoming);
    }

    private function mergeList(mixed $existing, array $incoming): array
    {
        $existing = is_array($existing) ? array_values($existing) : [];

        if ($incoming === []) {
            return [];
        }

        $allIncomingItemsAreArrays = collect($incoming)->every(fn ($item) => is_array($item));
        $allIncomingItemsHaveIds = $allIncomingItemsAreArrays
            && collect($incoming)->every(fn (array $item) => array_key_exists('id', $item) && $item['id'] !== null && $item['id'] !== '');

        if (!$allIncomingItemsHaveIds) {
            return array_map(fn ($item) => $this->sanitizeNode($item), $incoming);
        }

        $existingById = collect($existing)
            ->filter(fn ($item) => is_array($item) && array_key_exists('id', $item))
            ->keyBy(fn (array $item) => (string) $item['id']);

        $result = [];
        foreach ($incoming as $item) {
            $existingItem = $existingById->get((string) $item['id'], []);
            $result[] = $this->mergeAssocRecursive($existingItem, $item);
        }

        return $result;
    }

    private function applyKeepFlags(array $result, array $existing, array $incoming): array
    {
        $fileFieldMap = [
            'keep_image' => 'image_url',
            'keep_license' => 'license_file_url',
            'keep_license_file' => 'license_file_url',
        ];

        foreach ($fileFieldMap as $keepKey => $targetKey) {
            if (!array_key_exists($keepKey, $incoming)) {
                continue;
            }

            $keepValue = filter_var($incoming[$keepKey], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            $keepValue = $keepValue ?? in_array($incoming[$keepKey], [1, '1'], true);

            if ($keepValue && !array_key_exists($targetKey, $incoming) && array_key_exists($targetKey, $existing)) {
                $result[$targetKey] = $existing[$targetKey];
            }

            if (!$keepValue && !array_key_exists($targetKey, $incoming)) {
                $result[$targetKey] = null;
            }
        }

        return $result;
    }

    private function isAssoc(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    private function normalizeNullValues(mixed $value): mixed
    {
        if ($value === null) {
            return '';
        }

        if (!is_array($value)) {
            return $value;
        }

        $normalized = [];
        foreach ($value as $key => $item) {
            $normalized[$key] = $this->normalizeNullValues($item);
        }

        return $normalized;
    }

    private function fetchMessageFor(string $pageKey): string
    {
        return match ($pageKey) {
            'home' => 'Home content fetched successfully',
            'about' => 'About content fetched successfully',
            default => trans('api.success'),
        };
    }

    private function saveMessageFor(string $pageKey): string
    {
        return match ($pageKey) {
            'home' => 'Home content saved successfully',
            'about' => 'About content saved successfully',
            default => trans('api.updated_successfully'),
        };
    }

    private function defaultContentFor(string $pageKey): array
    {
        return match ($pageKey) {
            'home' => [
                'page' => 'home',
                'sections' => [
                    'hero' => [
                        'badge_text' => '',
                        'main_title' => '',
                        'description' => '',
                        'image_url' => '',
                    ],
                    'official_authorities' => [
                        'badge_text' => '',
                        'main_title' => '',
                        'description' => '',
                        'cards' => [],
                    ],
                    'features' => [
                        'badge_text' => '',
                        'main_title' => '',
                        'description' => '',
                        'cards' => [],
                    ],
                    'pricing' => [
                        'badge_text' => '',
                        'main_title' => '',
                        'description' => '',
                        'cards' => [],
                    ],
                    'contact' => [
                        'badge_text' => '',
                        'main_title' => '',
                        'description' => '',
                        'contact_number' => '',
                        'image_url' => '',
                    ],
                    'app' => [
                        'badge_text' => '',
                        'main_title' => '',
                        'description' => '',
                        'image_url' => '',
                    ],
                ],
            ],
            'about' => [
                'page' => 'about',
                'sections' => [
                    'hero' => [
                        'badge_text' => '',
                        'main_title' => '',
                        'description' => '',
                    ],
                    'story' => [
                        'badge_text' => '',
                        'main_title' => '',
                        'description' => '',
                        'cards' => [],
                    ],
                    'vision_mission' => [
                        'section_title' => '',
                        'section_description' => '',
                        'mission' => [
                            'badge_text' => '',
                            'title' => '',
                            'description' => '',
                            'image_url' => '',
                        ],
                        'vision' => [
                            'badge_text' => '',
                            'title' => '',
                            'description' => '',
                            'image_url' => '',
                        ],
                    ],
                    'beneficiaries' => [
                        'badge_text' => '',
                        'main_title' => '',
                        'description' => '',
                        'cards' => [],
                    ],
                    'values' => [
                        'badge_text' => '',
                        'main_title' => '',
                        'description' => '',
                        'cards' => [],
                    ],
                ],
            ],
            default => [
                'page' => $pageKey,
                'sections' => [],
            ],
        };
    }
}
