# Prompt: Admin marketing — Content & Reports tabs

Build the Aqdi admin **إدارة المحتوى** and **التقارير** tabs against the Laravel JSON API. Arabic-first, RTL. Do **not** invent metrics or labels — use the payload fields below.

Copy this prompt to the frontend. One endpoint per screen section. Shared period filter on Reports (and on article attribution). Service pages are **not** period-scoped.

Frontend files that already match these shapes: `ContentTab.jsx`, `ReportsTab.jsx`, `mock-data.js`. Wire with zero UI changes.

---

## Auth

- Header: `Authorization: Bearer {employee_token}`
- Header: `Accept: application/json`
- Optional locale: `Accept-Language: ar` or `en`

Envelope:

```json
{ "success": true, "code": 200, "message": "...", "data": { } }
```

Empty arrays / `null` are valid.

---

## Shared: period filter (Reports + article attribution)

| Query | Values |
|---|---|
| `period` | `today` \| `yesterday` \| `last_7_days` \| `last_30_days` \| `all` \| `custom` |
| `date_from` + `date_to` | `YYYY-MM-DD` — both required together (sets `period=custom`) |

Every Reports response includes `period`, `date_from`, `date_to`, `periods[]` (`key`, `label_ar`, `selected`), `currency` (`SAR`), `currency_label_ar` (`ريال`) — identical to `/admin/marketing-tracking`.

Optional Reports query/body: `channel` = `google` \| `meta` \| `tiktok` \| `snapchat` \| `twitter` \| `all`.

Money is always SAR. Do not hardcode `$`. Do not compute ROAS / CAC / change % on the client.

---

## Permissions

| Screen | Permission |
|---|---|
| Service pages (list + CRUD) | `analytics.view` / `analytics.create` / `analytics.edit` / `analytics.delete` |
| Articles (list) | `blogs.view` |
| Articles CRUD | existing `/admin/blogs` (`blogs.create` / `blogs.edit` / `blogs.delete`) |
| Marketing reports (GETs + export) | `analytics.view` |

Super admin (مدير النظام) bypasses. Hide a section entirely if the employee lacks its view permission.

Screen ids: `marketing-content-pages` → `analytics`, `marketing-content-articles` → `blogs`, `marketing-reports` → `analytics`.

---

## 1. إدارة المحتوى — Content

### 1.1 Service pages

```
GET    /api/admin/marketing/service-pages
POST   /api/admin/marketing/service-pages
PUT    /api/admin/marketing/service-pages/{id}
DELETE /api/admin/marketing/service-pages/{id}
```

Not period-scoped. `status`: `published` (منشور) / `draft` (مسودة) / `archived` (مؤرشف). `updated_at` is a date only (`YYYY-MM-DD`); `null` → UI shows `–`.

List payload:

```json
{
  "data": {
    "summary": { "total": 3, "published": 2, "drafts": 1 },
    "items": [
      {
        "id": 12,
        "title": "توثيق عقد إيجار سكني",
        "path": "/residential",
        "target_keyword": "عقد إيجار سكني",
        "status": "published",
        "status_label_ar": "منشور",
        "updated_at": "2026-05-12",
        "url": "https://aqdi.sa/residential"
      }
    ]
  }
}
```

Create/update body: `{ title, path, target_keyword, status, body? }`. PUT fields are all optional. Duplicate `path` → `422` with Arabic `message`. Return the created/updated row in `data`.

`body` is optional (rich-text can stay in the CMS). List + status + counts are the priority.

### 1.2 Articles

```
GET /api/admin/marketing/articles?period=last_30_days&category=&status=
```

Marketing view of the same rows as `/admin/blogs`. CRUD stays on `/admin/blogs` (`POST` / `PUT /admin/blogs/{id}` / `DELETE`). `scheduled_at` on blog write is an alias of `publish_at`. Blog `status` also accepts `archived`. Optional blog fields: `category`, `category_label_ar`, `author`.

Article query: `category` (slug) or omit; `status` = `published` \| `scheduled` \| `draft` \| `archived` or omit.

`views` is lifetime `views_count` (incremented on public blog read). `leads` / `attributed_revenue` are period-scoped from first-touch UTM (`utm_content` or `utm_campaign` matching the blog slug). Always numbers, never `null` (use `0`).

`editorial_queue`: draft + scheduled, soonest `scheduled_at` first; unscheduled drafts have `scheduled_at: null` (UI shows «غير مجدول»).

---

## 2. التقارير — Reports

```
GET  /api/admin/marketing/reports
GET  /api/admin/marketing/reports/channels
POST /api/admin/marketing/reports/export
```

### 2.1 Overview — `GET /marketing/reports`

`highlights[]`: `best_page`, `best_keyword`, `best_campaign`, `best_source`. `badge` is `null`, a rank string (`"1"`), or `{ label_ar, color }` where `color` ∈ `blue` \| `purple` \| `black` \| `yellow` \| `gray`.

`comparison.items[]`: `orders`, `ad_spend`, `attributed_revenue` with `value`, `previous_value`, signed integer `change_percent` (or `null`), `is_money` when SAR. Green ▲ when `change_percent >= 0`, red ▼ when `< 0`.

`stats[]`: `new_customers`, `returning_customers`, `marketing_cost`, `total_revenue`, `revenue_per_riyal` (`suffix`: `"x"`). Send numbers, not formatted strings. `is_money: true` ⇒ append `currency_label_ar`.

### 2.2 Channel table — `GET /marketing/reports/channels`

Always the 5 paid channels in fixed order (Google, Meta, TikTok, Snapchat, X) plus `total`. Fields: `spend`, `revenue`, `roas`, `roas_tone` (`good` \| `ok` \| `bad` \| `muted`), `leads`, `conversions`, `cac`, `profit`. `roas` / `cac` may be `null` (UI shows —). `profit` is signed; UI prefixes `+` when `> 0`.

`range_label_ar` example: `تقرير القنوات: 03-08-2026 ← 01-09-2026 · 5 صفوف`.

### 2.3 Export — `POST /marketing/reports/export`

```json
{ "format": "pdf", "period": "last_30_days", "date_from": null, "date_to": null, "channel": "all" }
```

`format`: `pdf` \| `xlsx` \| `csv` → file download (`Content-Type` + `Content-Disposition`). `email` → send to the current employee, `{ "success": true, "message": "أُرسل التقرير إلى بريدك" }`.

---

## Suggested routes recap

| UI | Endpoints |
|---|---|
| `/home/marketing-and-content?tab=content` (services) | `GET/POST/PUT/DELETE /admin/marketing/service-pages` |
| `/home/marketing-and-content?tab=content` (articles) | `GET /admin/marketing/articles` + `/admin/blogs` CRUD |
| `/home/marketing-and-content?tab=reports` | `GET /admin/marketing/reports`, `GET /admin/marketing/reports/channels`, `POST /admin/marketing/reports/export` |
