# Prompt: Admin — Website images SEO

Arabic-first admin screen to edit **alt**, **meta title**, and **meta description** for public website images.

## Auth

- `Authorization: Bearer {employee_token}`
- Permission section: **`website_images`** (`view` / `create` / `edit` / `delete`)
- Screen id: `website-images`

## Endpoints

| Method | Path | Permission |
|---|---|---|
| GET | `/api/admin/website-images` | `website_images.view` |
| GET | `/api/admin/website-images/{id}` | `website_images.view` |
| POST | `/api/admin/website-images` | `website_images.create` |
| PUT/POST | `/api/admin/website-images/{id}` | `website_images.edit` |
| DELETE | `/api/admin/website-images/{id}` | `website_images.delete` |
| POST | `/api/admin/website-images/sync-defaults` | `website_images.create` |

Optional list query: `search`, `is_active`.

### List shape

```json
{
  "data": {
    "summary": { "total": 15, "active": 15, "with_alt": 12, "with_meta": 8 },
    "items": [
      {
        "id": 1,
        "key": "logo",
        "label_ar": "شعار الموقع",
        "label_en": "Website logo",
        "url": "https://…/website/asset/images/logo.svg",
        "static_path": "website/asset/images/logo.svg",
        "path": null,
        "alt_ar": "شعار أقدي",
        "alt_en": "Aqdi logo",
        "meta_title_ar": "أقدي",
        "meta_title_en": null,
        "meta_description_ar": "…",
        "meta_description_en": null,
        "is_active": true,
        "sort_order": 10
      }
    ]
  }
}
```

### Edit body (multipart or JSON)

`alt_ar`, `alt_en`, `meta_title_ar`, `meta_title_en`, `meta_description_ar`, `meta_description_en`, `label_ar`, `label_en`, `is_active`, `sort_order`, optional `image` file (replaces the file while keeping the same key), optional `static_path`.

`key` is stable (kebab-case). Do not rename casually — Blade helpers resolve by key.

### Sync defaults

`POST /sync-defaults` creates missing catalog rows for known site assets (logo, login hero, landing banner, footer icons, …) without overwriting existing alt/meta text.

## Blog covers

Blog CRUD (`/api/admin/blogs`) also accepts `image_alt` alongside existing `meta_title` / `meta_description` (page SEO). Public blog pages use `image_alt` for the cover `alt` attribute.

## UI notes

- Table: preview thumbnail + label + key + alt + meta title
- Edit drawer/form: bilingual alt / meta title / meta description + optional image replace
- Empty alt/meta is valid; website falls back to defaults in Blade helpers
