# Prompt: Admin dashboard — all content & image alt/SEO

Build the Aqdi admin **محتوى التطبيق** area (Arabic-first, RTL) against the Laravel JSON API. Cover **every content screen** plus **image alt / meta SEO**. Do **not** invent fields, metrics, or endpoints.

Copy this prompt to the frontend. One endpoint per action. Hide a section if the employee lacks its **view** permission. Super admin (مدير النظام) bypasses gates.

Related existing prompts: `admin-website-images-seo.md` (images only), `admin-marketing-content-and-reports.md` (marketing service pages / articles / reports — **not** this hub).

---

## Auth

- `Authorization: Bearer {employee_token}`
- `Accept: application/json`
- Optional: `Accept-Language: ar` or `en`

Envelope:

```json
{ "success": true, "code": 200, "message": "...", "data": { } }
```

Lists that paginate use `data.items` + `data.pagination` (`current_page`, `last_page`, `from`, `to`, `total`, …). Empty arrays / `null` are valid.

Base path: `/api/admin`.

Updates that accept files: `multipart/form-data`. JSON is fine when there is no file.

---

## Hub vs screens

| Screen id | Permission section | What it is |
|---|---|---|
| *(hub)* | `app_content.view` | Cards linking to payment methods, legal pages, customer messages |
| `website-images` | `website_images` | Catalog of site images: **alt + meta title + meta description** (AR/EN) |
| `instruction_sections` | `instruction_sections` | In-app instructional images by stable `key` |
| *(blogs)* | `blogs` | Blog posts: cover **`image_alt`** + page `meta_title` / `meta_description` |
| `terms` / `privacy` | `app_content` | Legal HTML (AR required, EN optional) |
| *(home / about pages)* | `app_content` | Structured JSON pages (`home`, `about`) |
| *(faqs)* | `faqs` | Q&A bilingual |
| *(ads)* | `ads` | In-app ads (image + title; **no alt column**) |
| `customer-app-messages` | `app_content` | Client in-app messages (alias of message alerts, type client) |
| `payment-types` | `app_content` **screen map**; API gate is **`payment_types`** | Payment method names (Catalog) |

Do not mix this hub with marketing **إدارة المحتوى** (`/api/admin/marketing/service-pages`).

---

## Image alt rules (do not invent columns)

| Surface | Alt / SEO fields that exist |
|---|---|
| Website images | `alt_ar`, `alt_en`, `meta_title_ar`, `meta_title_en`, `meta_description_ar`, `meta_description_en` |
| Blog cover | `image_alt` (cover `<alt>`), plus `meta_title`, `meta_description` (page SEO) |
| Instruction images | `title_ar` only (caption). **No `alt_ar`.** Use `title_ar` as the accessible label in the admin preview. |
| Ads | `title` only. **No alt field.** Use `title` as the preview label. |
| Content pages (`home` / `about`) | Section `image_url` (and file uploads). **No per-image alt in the JSON schema.** Do not add alt inputs unless the API adds them. |
| Settings cover / banner | `/api/admin/settings` (`cover`, `image-banner`) — file replace only, not this content hub |

Empty alt/meta is allowed. Do not block save.

---

## 0. Hub — app content overview

```
GET /api/admin/app-content/overview
```

Permission: `app_content.view`.

`data.sections[]`: `key`, `label_ar`, `label_en`, optional `count` / `has_content` / `sections_count`, and `routes` (method + path strings for the SPA to navigate).

Keys: `payment_types`, `terms_and_conditions`, `privacy_policy`, `customer_messages`.

Use this as the landing cards. Do not fetch those four resources until the user opens a card.

---

## 1. Website images (alt + meta SEO) — primary alt screen

Screen id: `website-images`. Permission: `website_images.view` / `create` / `edit` / `delete`.

| Method | Path | Permission |
|---|---|---|
| GET | `/api/admin/website-images` | view |
| GET | `/api/admin/website-images/{id}` | view |
| POST | `/api/admin/website-images` | create |
| PUT or POST | `/api/admin/website-images/{id}` | edit |
| DELETE or POST `…/{id}/delete` | `/api/admin/website-images/{id}` | delete |
| POST | `/api/admin/website-images/sync-defaults` | create |

List query (optional): `search`, `is_active`.

### List `data`

```json
{
  "summary": { "total": 15, "active": 15, "with_alt": 12, "with_meta": 8 },
  "items": [
    {
      "id": 1,
      "key": "logo",
      "label_ar": "شعار الموقع",
      "label_en": "Website logo",
      "url": "https://…/logo.svg",
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
```

### Create / edit body

`key` (kebab-case, unique, required on create), `label_ar`, `label_en`, `static_path`, `alt_ar`, `alt_en`, `meta_title_ar`, `meta_title_en`, `meta_description_ar`, `meta_description_en`, `is_active`, `sort_order`, optional file `image` (jpeg/png/gif/svg/webp, max 4MB). Replacing `image` keeps the same `key`.

**Do not rename `key` casually** — clients resolve by key (`logo`, `favicon`, `login-hero`, `landing-banner`, `home-create-contract`, `home-choose-contract`, `ejar-icon`, `whatsapp`, `footer-whatsapp`, `footer-tiktok`, `footer-x`, `success-icon`, `about-buyer`, `about-seller`, `about-middle`, …).

`POST /sync-defaults` inserts missing catalog rows for known assets. It does **not** overwrite existing alt/meta.

**UI:** table = thumbnail + label + key + alt + meta title. Drawer = bilingual alt / meta title / meta description + optional image replace. Show summary chips for missing alt / missing meta.

---

## 2. Instruction images (صور تعليمية)

Permission: `instruction_sections.view` / `edit` / `delete`. Sections are predefined; there is **no create section** route.

| Method | Path |
|---|---|
| GET | `/api/admin/instruction-sections` |
| GET | `/api/admin/instruction-sections/{id}` |
| POST | `/api/admin/instruction-sections/{id}` (update title/description/active/sort) |
| POST | `/api/admin/instruction-sections/{id}/toggle` body `{ "is_active": true }` |
| POST | `/api/admin/instruction-sections/{id}/delete` |
| POST | `/api/admin/instruction-sections/{id}/images` multipart `image` + optional `title_ar`, `sort_order` |
| POST | `/api/admin/instruction-sections/{id}/images/{imageId}/delete` |

Upload: png/jpg/jpeg/webp, max 5MB. Image payload: `id`, `section_id`, `title_ar`, `image_url`, `mime_type`, `file_extension`, `sort_order`.

Stable section keys (mobile `GET /api/instruction-images/{key}`): `new-client`, `start`, `create-residential-contract`, `create-commercial-contract`, `my-real-estate`, `units`, `deed`, `address`, `owner`, `tenant`, `real-estate`, `instrument`, `agent`, `authorization`, `endowment`, `financial-data`, `payment-completion`, `requests`, `documentation`, `contract-review`.

Treat `title_ar` as the admin alt/caption. Do not send `alt_ar`.

---

## 3. Blogs (cover alt + page meta)

Permission: `blogs.view` / `create` / `edit` / `delete`.

| Method | Path |
|---|---|
| GET | `/api/admin/blogs` |
| POST | `/api/admin/blogs` |
| GET | `/api/admin/blogs/{id}` |
| PUT | `/api/admin/blogs/{id}` |
| DELETE | `/api/admin/blogs/{id}` |
| POST | `/api/admin/blogs/{id}/toggle-active` |
| GET | `/api/admin/blogs/statistics` |

Body (create): `title` (required), `description` (required), `status` = `published` \| `draft` \| `scheduled` \| `archived` (`schedule` is accepted and stored as `scheduled`), `image` (optional file), **`image_alt`**, **`meta_title`**, **`meta_description`**, `publish_at` (required if scheduled, must be future), `scheduled_at` (alias of `publish_at`), `is_active`, `category`, `category_label_ar`, `author`.

Always show **`image_alt`** next to the cover upload. Page SEO fields are separate from cover alt.

---

## 4. Legal pages (terms + privacy)

Permission: `app_content.view` / `edit`. Screen ids: `terms`, `privacy`.

| Method | Path |
|---|---|
| GET | `/api/admin/content/legal-pages` |
| GET | `/api/admin/content/terms-and-conditions` |
| POST | `/api/admin/content/terms-and-conditions` |
| GET | `/api/admin/content/privacy` |
| POST | `/api/admin/content/privacy` |

Update body: `description_ar` (required string), `description_en` (optional). Resource: `id`, `page`, `description_ar`, `description_en`, `description` (locale pick), `updated_at`.

Prefer `legal-pages` for a combined editor (terms + privacy in one response).

---

## 5. Home / About structured pages

Permission: `app_content.view` / `edit`. `pageKey`: **`home`** or **`about`** only.

| Method | Path |
|---|---|
| GET | `/api/admin/content-pages/{pageKey}` |
| POST | `/api/admin/content-pages/{pageKey}` |

GET/POST merge JSON `sections` (and optional file uploads). Public read: `GET /api/v2/content-pages/{pageKey}`.

**Home sections:** `hero` (badge, title, description, `image_url`), `official_authorities`, `features`, `pricing` (cards arrays), `contact` (`contact_number`, `image_url`), `app` (`image_url`).

**About sections:** `hero`, `story` (cards), `vision_mission` (`mission` / `vision` with `image_url`), `beneficiaries`, `values`.

Send only changed section keys; server deep-merges. File fields replace URLs. **No alt keys in this schema.**

---

## 6. FAQs

Permission: `faqs.view` / `create` / `edit` / `delete`.

| Method | Path |
|---|---|
| GET | `/api/admin/faqs` (`search` optional) |
| POST | `/api/admin/faqs` |
| GET | `/api/admin/faqs/{id}` |
| POST | `/api/admin/faqs/{id}` |
| POST | `/api/admin/faqs/{id}/delete` |

Body: `title_ar` (required), `title_en`, `answer_ar` (required, max 1000), `answer_en`.

---

## 7. In-app ads

Permission: `ads.view` / `create` / `edit` / `delete`.

| Method | Path |
|---|---|
| GET | `/api/admin/ads` (`search`, `is_active`) |
| POST | `/api/admin/ads` |
| GET | `/api/admin/ads/{id}` |
| POST | `/api/admin/ads/{id}` |
| POST | `/api/admin/ads/{id}/delete` |

Create: `title` (required), `image` (required jpg/png/webp, max 4MB), `is_active` (default true). Update: same fields; `image` optional.

No alt field — preview with `title`.

---

## 8. Customer application messages

Permission: `app_content.*`. Same handlers as message alerts with type **client**.

| Method | Path |
|---|---|
| GET | `/api/admin/customer-messages/overview` |
| GET | `/api/admin/customer-messages` |
| GET | `/api/admin/customer-messages/all` |
| GET | `/api/admin/customer-messages/create` |
| POST | `/api/admin/customer-messages` |
| GET | `/api/admin/customer-messages/{id}` |
| POST | `/api/admin/customer-messages/{id}` |
| POST | `/api/admin/customer-messages/{id}/delete` |

Full message-alert tree (employee / property / client) remains under `/api/admin/message-alerts` and `/message-alert-sections` (`permission:message_alerts.*`). This hub only needs the **client** alias above.

---

## 9. Payment methods (from overview card)

API lives in Catalog, not Content. Permission middleware: **`payment_types.view`** (and create/edit/delete). Dashboard screen id `payment-types` maps to `app_content` in `config/permissions.php` screens — still call these URLs:

| Method | Path |
|---|---|
| GET | `/api/admin/payment-types` |
| POST | `/api/admin/payment-types` |
| GET | `/api/admin/payment-types/{id}` |
| POST | `/api/admin/payment-types/{id}` |
| POST | `/api/admin/payment-types/{id}/delete` |

Body: `name_ar` (required), `name_en` (optional). No image/alt.

---

## Suggested IA

1. **Overview** — four cards from `/app-content/overview`
2. **صور الموقع (SEO)** — website-images (this is the alt/meta editor)
3. **صور تعليمية** — instruction-sections
4. **المدونة** — blogs (`image_alt` required in the form UI even though API allows empty)
5. **صفحات الهبوط** — content-pages `home` / `about`
6. **قانوني** — legal-pages
7. **الأسئلة الشائعة** — faqs
8. **إعلانات التطبيق** — ads
9. **رسائل العميل** — customer-messages

Show a red/empty badge on website-images rows where both `alt_ar` and `alt_en` are empty, and on blogs where `image` exists but `image_alt` is empty.
