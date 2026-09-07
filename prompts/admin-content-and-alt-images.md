# Prompt: Admin dashboard — home, about us, all DB content & image alt

Build the Aqdi admin **محتوى التطبيق** area (Arabic-first, RTL) against the Laravel JSON API.

**All copy and images for the public home page, about-us page, and legal pages live in the database.** The dashboard must **load, edit, and save them via API**. Do **not** hardcode Arabic/English text, image URLs, or extra page keys. Do **not** invent fields, metrics, or endpoints.

Copy this prompt to the frontend. One endpoint per action. Hide a section if the employee lacks its **view** permission. Super admin (مدير النظام) bypasses gates.

Related: `admin-website-images-seo.md` (image catalog only), `admin-marketing-content-and-reports.md` (marketing service pages — **not** this hub).

---

## Auth

- `Authorization: Bearer {employee_token}`
- `Accept: application/json`
- Optional: `Accept-Language: ar` or `en`

Envelope:

```json
{ "success": true, "code": 200, "message": "...", "data": { } }
```

Lists that paginate use `data.items` + `data.pagination`. Empty arrays / `null` / `""` are valid. Base path: `/api/admin`. File updates: `multipart/form-data`. JSON is fine when there is no file.

---

## What exists in the database (do not invent pages)

There is **no list-all-pages** endpoint. These are the only CMS page keys the API accepts.

### A. Structured landing pages — table `content_pages`

JSON blob per row (`page_key` unique + `content_json`). **Only two keys:**

| `pageKey` | Arabic screen | GET (creates empty row if missing) | POST (merge + save) |
|---|---|---|---|
| `home` | الصفحة الرئيسية | `GET /api/admin/content-pages/home` | `POST /api/admin/content-pages/home` |
| `about` | من نحن | `GET /api/admin/content-pages/about` | `POST /api/admin/content-pages/about` |

Any other `{pageKey}` → **422** (`page_key` invalid). Public read (no admin token): `GET /api/v2/content-pages/home` and `GET /api/v2/content-pages/about`.

`GET` uses `firstOrCreate`: if the row is missing, the API inserts the default empty schema and returns it. Always **GET first**, bind the form to `data.sections`, then POST.

Permission: `app_content.view` (GET), `app_content.edit` (POST).

### B. Legal HTML pages — table `pages`

Plain HTML/text, not JSON sections.

| DB `page` value | Screen | GET | POST |
|---|---|---|---|
| `term_and_condition` | الشروط والأحكام | `GET /api/admin/content/terms-and-conditions` | `POST /api/admin/content/terms-and-conditions` |
| `privacy` | سياسة الخصوصية | `GET /api/admin/content/privacy` | `POST /api/admin/content/privacy` |

Combined: `GET /api/admin/content/legal-pages` → `data.terms_and_conditions` + `data.privacy_policy`.

Body: `description_ar` (required), `description_en` (optional).

### C. Image alt catalog — table `website_images`

Separate from `content_json`. Keys used on home / about / chrome: `logo`, `favicon`, `login-hero`, `landing-banner`, `home-create-contract`, `home-choose-contract`, `ejar-icon`, `whatsapp`, `footer-whatsapp`, `footer-tiktok`, `footer-x`, `success-icon`, `about-buyer`, `about-seller`, `about-middle`.

### D. Other content tables (same dashboard hub)

FAQs, blogs, ads, instruction sections, customer messages, payment types — see later sections. Not extra `content_pages` keys.

---

## Image alt rules (do not invent columns)

| Surface | Alt / SEO fields that exist |
|---|---|
| Website images catalog | `alt_ar`, `alt_en`, `meta_title_ar`, `meta_title_en`, `meta_description_ar`, `meta_description_en` |
| Home / About CMS (`content_pages`) | Section/card `image_url` (and `license_file_url`). **No `alt_*` inside JSON.** Do not add alt inputs on home/about forms. Alt for chrome images is the website-images screen. |
| Blog cover | `image_alt` + page `meta_title` / `meta_description` |
| Instruction images | `title_ar` as caption only |
| Ads | `title` only |

Empty alt/meta is allowed. Do not block save.

---

## 1. Home page CMS (`pageKey=home`)

Screen: **الصفحة الرئيسية**. Load `GET /api/admin/content-pages/home`. Save `POST /api/admin/content-pages/home`.

Response `data`:

```json
{
  "page": "home",
  "updated_at": "2026-09-07T08:00:00+00:00",
  "sections": {
    "hero": {},
    "official_authorities": {},
    "features": {},
    "pricing": {},
    "contact": {},
    "app": {}
  }
}
```

Render **six editors**, one per section. Bind to the GET payload. Do not drop unknown extra keys the DB may already store (deep-merge on save).

### 1.1 `hero`

| Field | Type |
|---|---|
| `badge_text` | string |
| `main_title` | string |
| `description` | string |
| `image_url` | public URL (read-only in UI; replace via file) |

Multipart: `hero[badge_text]`, `hero[main_title]`, `hero[description]`, file `hero[image]` (stored as `image_url`), `hero[keep_image]` = `1` to keep current image, `0` to clear if no new file.

### 1.2 `official_authorities`

Header: `badge_text`, `main_title`, `description`. List `cards[]`:

| Field | Notes |
|---|---|
| `id` | **Send a stable id** (string or number). Merge is by `id`. New card: new unique id. Delete: omit the card from the posted list (do **not** rely on `deleted_*` keys — the API ignores keys starting with `deleted_`). |
| `title`, `description` | strings |
| `image_url` | logo; file `official_authorities[cards][0][image]` |
| `keep_image` | `1` / `0` |
| `license_file_url` | license image or PDF |
| `license_file_type` | `pdf` or `image` (set automatically on upload) |
| `keep_license` or `keep_license_file` | keep current license file |

Example: `official_authorities[cards][0][id]`, `[title]`, `[description]`, file `[image]`, file `[license_file]`, `[keep_image]=1`, `[keep_license]=1`.

### 1.3 `features`

Header: `badge_text`, `main_title`, `description`. Cards: `id`, `title`, `description`, `image_url`, `keep_image`, optional `is_default`.

### 1.4 `pricing`

Header: `badge_text`, `main_title`, `description`. Cards: `id`, `title`, `subtitle`, `price`, `duration_label`, `image_url`, `keep_image`, `is_default`, nested `features[]` with `id` + `text`.

Example: `pricing[cards][0][features][0][id]`, `pricing[cards][0][features][0][text]`.

To remove a pricing bullet, post the card with the remaining `features` array only.

### 1.5 `contact`

`badge_text`, `main_title`, `description`, `contact_number`, `image_url` / `keep_image` / file `contact[image]`.

### 1.6 `app`

`badge_text`, `main_title`, `description`, `image_url` / `keep_image` / file `app[image]`.

### Save rules (home and about)

- POST **multipart** when any file changed; otherwise JSON with the same nested object shape (`hero`, `official_authorities`, … at the **root**, not wrapped in `sections`).
- Optional `page=home` is ignored for routing (the URL key wins).
- Server **deep-merges** into existing `content_json`. Send only changed sections if you want; sending the full `sections` object from GET is safer.
- File field name `image` is stored as `image_url`. `license_file` → `license_file_url`.
- `keep_image=1` without a new file keeps the old URL. `keep_image=0` without a new file clears it.
- GET returns absolute `https://…/storage/…` URLs. Do not rewrite them on save unless replacing the file.
- **No alt fields** on these images.

JSON save example (no files):

```json
{
  "hero": {
    "badge_text": "عقدك الموثق من شبكة ايجار خلال دقائق",
    "main_title": "عقد إيجار إلكتروني موثق",
    "description": "…",
    "keep_image": true
  }
}
```

---

## 2. About us CMS (`pageKey=about`)

Screen: **من نحن**. Load `GET /api/admin/content-pages/about`. Save `POST /api/admin/content-pages/about`. Same merge/file/`keep_*` rules as home.

Response `data.page` = `"about"`. Sections:

### 2.1 `hero`

`badge_text`, `main_title`, `description`. **No image** in the default schema.

### 2.2 `story` (stats / أرقام)

Header: `badge_text`, `main_title`, `description`. Cards: `id`, `value` (e.g. `8.3M+`), `label` (e.g. `عدد العقود السكنية الموثقة`). No image on story cards.

### 2.3 `vision_mission`

| Path | Fields |
|---|---|
| `vision_mission.section_title` | string |
| `vision_mission.section_description` | string |
| `vision_mission.mission` | `badge_text`, `title`, `description`, `image_url`, `keep_image`, file `vision_mission[mission][image]` |
| `vision_mission.vision` | same as mission; file `vision_mission[vision][image]` |

### 2.4 `beneficiaries`

Header: `badge_text`, `main_title`, `description`. Cards: `id`, `title`, `description`, `image_url`, `keep_image`, file `beneficiaries[cards][n][image]`.

### 2.5 `values`

Same card shape as beneficiaries: `id`, `title`, `description`, `image_url`.

Suggested UI: two tabs **الرئيسية** | **من نحن**, or two sidebar items. Prefetch both GETs when the employee opens “صفحات الموقع”. Show `updated_at` on each tab.

---

## 3. Legal pages (DB table `pages`)

Permission: `app_content.view` / `edit`. Screen ids: `terms`, `privacy`.

| Method | Path |
|---|---|
| GET | `/api/admin/content/legal-pages` |
| GET | `/api/admin/content/terms-and-conditions` |
| POST | `/api/admin/content/terms-and-conditions` |
| GET | `/api/admin/content/privacy` |
| POST | `/api/admin/content/privacy` |

Resource: `id`, `page` (`term_and_condition` or `privacy`), `description_ar`, `description_en`, `description` (locale pick), `updated_at`.

Rich-text editors for AR (required) and EN (optional). Prefer one screen with two tabs fed by `legal-pages`.

---

## 4. Website images — alt + meta (home / about chrome)

Screen id: `website-images`. Permission: `website_images.view` / `create` / `edit` / `delete`.

This is the **alt/SEO** editor for images that appear around home/about (logo, landing banner, about-buyer/seller/middle, home-create-contract, …). It is **not** the home/about CMS JSON.

| Method | Path |
|---|---|
| GET | `/api/admin/website-images` |
| GET | `/api/admin/website-images/{id}` |
| POST | `/api/admin/website-images` |
| PUT or POST | `/api/admin/website-images/{id}` |
| DELETE or POST `…/{id}/delete` | |
| POST | `/api/admin/website-images/sync-defaults` |

List query: `search`, `is_active`. List `data.summary`: `total`, `active`, `with_alt`, `with_meta`.

Item fields: `id`, `key` (kebab, stable — do not rename casually), `label_ar`, `label_en`, `url`, `static_path`, `path`, **`alt_ar`**, **`alt_en`**, **`meta_title_ar`**, **`meta_title_en`**, **`meta_description_ar`**, **`meta_description_en`**, `is_active`, `sort_order`. Optional file `image` (jpeg/png/gif/svg/webp, max 4MB).

`POST /sync-defaults` inserts missing catalog keys. It does **not** overwrite existing alt/meta.

UI: thumbnail + key + alt + meta. Badge rows where both alts are empty.

---

## 5. Hub overview (not home/about)

```
GET /api/admin/app-content/overview
```

Permission: `app_content.view`. Cards only: `payment_types`, `terms_and_conditions`, `privacy_policy`, `customer_messages`. Home/about are **not** in this payload — add them in the SPA nav yourself (`/content-pages/home`, `/content-pages/about`).

---

## 6. Instruction images

Permission: `instruction_sections.*`. No create-section route.

`GET/POST /api/admin/instruction-sections/{id}`, toggle, delete, `POST …/{id}/images` (`image` required png/jpg/jpeg/webp max 5MB, optional `title_ar`, `sort_order`), delete image.

Keys: `new-client`, `start`, `create-residential-contract`, `create-commercial-contract`, `my-real-estate`, `units`, `deed`, `address`, `owner`, `tenant`, `real-estate`, `instrument`, `agent`, `authorization`, `endowment`, `financial-data`, `payment-completion`, `requests`, `documentation`, `contract-review`.

Image payload: `id`, `section_id`, `title_ar`, `image_url`, `mime_type`, `file_extension`, `sort_order`. Use `title_ar` as caption.

---

## 7. Blogs

Permission: `blogs.*`. `GET/POST /api/admin/blogs`, `GET/PUT/DELETE /blogs/{id}`, `POST /{id}/toggle-active`, `GET /blogs/statistics`.

Body: `title`, `description`, `status` = `published` \| `draft` \| `scheduled` \| `archived` (`schedule` → `scheduled`), file `image`, **`image_alt`**, **`meta_title`**, **`meta_description`**, `publish_at` / `scheduled_at` (future if scheduled), `is_active`, `category`, `category_label_ar`, `author`.

---

## 8. FAQs

Permission: `faqs.*`. `GET/POST /api/admin/faqs`, `GET/POST /faqs/{id}`, `POST /faqs/{id}/delete`. Optional `search`. Body: `title_ar` required, `title_en`, `answer_ar` required max 1000, `answer_en`.

---

## 9. Ads

Permission: `ads.*`. `GET/POST /api/admin/ads`, show/update/delete. Create: `title`, `image` (jpg/png/webp max 4MB), `is_active`. No alt column — preview with `title`.

---

## 10. Customer application messages

Permission: `app_content.*`. Client-only aliases:

`GET /api/admin/customer-messages/overview|all|create|/|{id}`, `POST /`, `POST /{id}`, `POST /{id}/delete`.

Full tree stays under `/message-alerts` (`permission:message_alerts.*`).

---

## 11. Payment methods

Catalog API, gate `payment_types.*`. Screen id `payment-types`.

`GET/POST /api/admin/payment-types`, `GET/POST /{id}`, `POST /{id}/delete`. Body: `name_ar` required, `name_en` optional.

---

## Suggested IA

1. **صفحات الموقع (من قاعدة البيانات)**
   - الرئيسية → `content-pages/home`
   - من نحن → `content-pages/about`
2. **قانوني** → `content/legal-pages` (`pages` table)
3. **صور الموقع (alt / SEO)** → `website-images`
4. Overview cards → payment types / customer messages
5. صور تعليمية / المدونة / الأسئلة / الإعلانات

Never add a third `content-pages/{key}` unless the backend adds it to `home|about`. Never seed home/about copy in the SPA; the source of truth is `content_json` in `content_pages`.
