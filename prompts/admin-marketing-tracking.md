# Prompt: Admin marketing tracking UI

Build the Aqdi admin **marketing tracking** screens against the Laravel JSON API. Arabic-first, RTL. Do **not** invent metrics, colors, or labels — use the payload fields below.

Copy this prompt to the frontend (or another agent). One endpoint per screen section. Shared period filter on every page.

---

## Auth

- Header: `Authorization: Bearer {employee_token}`
- Header: `Accept: application/json`
- Optional locale: `Accept-Language: ar` or `en`
- Permission: **`analytics.view`** for all three endpoints
- Super admin (مدير النظام) has full access
- Hide the whole marketing-tracking area if the employee lacks `analytics.view`

Screen ids (permission matrix → `analytics`):

| Screen id | UI |
|---|---|
| `marketing-overview` | ROAS header + widgets |
| `marketing-keywords` | Keyword ranking table |
| `marketing-tracking` | Funnel + paid channels |
| `marketing-campaigns` | Same channels/campaigns data |

Envelope:

```json
{ "success": true, "code": 200, "message": "...", "data": { } }
```

`success: true` = API call OK. Empty arrays/`null` campaigns are valid (no spend yet).

---

## Shared: period filter

Every tracking GET accepts the same query as other admin reports:

| Query | Values |
|---|---|
| `period` | `today` \| `yesterday` \| `last_7_days` \| `last_30_days` \| `all` \| `custom` |
| `date_from` + `date_to` | `YYYY-MM-DD` — both required together (sets `period=custom`) |

Default in UI: `last_30_days`.

Every response includes:

```json
{
  "period": "last_30_days",
  "date_from": "2026-08-03",
  "date_to": "2026-09-01",
  "periods": [
    { "key": "today", "label_ar": "اليوم", "selected": false },
    { "key": "last_30_days", "label_ar": "آخر 30 يومًا", "selected": true }
  ]
}
```

Render `data.periods` as tabs. Changing a tab refetches **the current section’s endpoint** with `?period={key}`. Custom range: send `date_from` + `date_to`.

Money is always SAR. Use `data.currency_label_ar` (`ريال`) next to amounts. Do not hardcode `$`.

---

## Section 1 — Keyword ranking

**UI:** six summary cards + table «الكلمات المفتاحية – الترتيب والمنافسة والحالة»

```
GET /api/admin/marketing-tracking/keywords?period=last_30_days
```

### Cards (`data.summary`)

| Field | Card (AR) | Notes |
|---|---|---|
| `organic_revenue` | إيراد عضوي | append `currency_label_ar` |
| `organic_clicks` | نقرات عضوية | integer |
| `decreased` | انخفضت | red |
| `increased` | ارتفعت | green |
| `average_rank` | متوسط الترتيب | `null` → show `—` |
| `target_keywords` | كلمات مستهدفة | integer |

### Table (`data.items[]`)

RTL columns, right → left:

| Column (AR) | Field | Render |
|---|---|---|
| الكلمة / الصفحة | `keyword` + `page_path` | title + muted path |
| الترتيب الحالي | `current_rank` | pill; color from `rank_tone` |
| السابق | `previous_rank` | number or `—` if `null` |
| بحث/شهر | `search_volume` | integer |
| المنافسة | `competition_label_ar` | pill from `competition` |
| الحالة | `status_label_ar` | icon from `status` |
| الإيراد | `revenue` | `{revenue} {currency_label_ar}` |
| (سهم) | — | details affordance; row id = `keyword` |

Enums (do not translate yourself — use `*_label_ar` / `*_label_en`):

| Field | Values | Color |
|---|---|---|
| `rank_tone` | `good` (1–3) / `warn` (4–10) / `muted` | green / orange / grey |
| `competition` | `high` / `medium` / `low` | red / orange / green |
| `status` | `increased` / `decreased` / `stable` | green ▲ / red ▼ / grey — |

`current_rank` may be `null` if Google Search Console is not connected — show `—`, keep revenue rows.

Empty: `items: []` → empty table, cards stay `0` / `—`.

---

## Section 2 — Funnel + paid channels

**UI:** «القمع التسويقي الكامل» + table «أداء القنوات المدفوعة – ROI»

```
GET /api/admin/marketing-tracking/channels?period=last_30_days
```

### Funnel (`data.funnel[]`) — 4 bars, order as returned

| `key` | Label field | Bar width | Badge on bar |
|---|---|---|---|
| `impressions` | `label_ar` ظهور | `share_percent` | `change_percent` |
| `clicks` | نقرات | `share_percent` | `change_percent` |
| `leads` | عملاء محتملون | `share_percent` | `change_percent` |
| `conversions` | تحويلات | `share_percent` | `change_percent` |

- Bar fill width = `share_percent` (0–100 vs impressions).
- Optional secondary: `rate_from_previous` = conversion from the previous stage (clicks/impressions, …).
- `change_percent` vs last equal-length period (green if ≥ 0, red if &lt; 0).
- Format `value` with locale grouping (1215000 → `1,215,000`).

### Channels table (`data.channels[]`) — always 5 rows

Order is fixed: Google, Meta, TikTok, Snapchat, X (`twitter`).

| Column (AR) | Field | Render |
|---|---|---|
| القناة | `label_ar` | pill; `color` = `blue` \| `purple` \| `black` \| `yellow` \| `gray` |
| الصرف | `spend` | + ريال |
| الإيراد | `revenue` | + ريال |
| ROAS | `roas` | `x{roas}` or `—` if `null`; bg from `roas_tone` |
| تحويلات | `conversions` | integer |
| CAC | `cac` | ريال, or `—` if `null` |
| الربح | `profit` | prefix `+` when &gt; 0 |

`roas_tone`: `good` (≥2, green) / `ok` (≥1, blue) / `bad` (&lt;1, red) / `muted` (no spend).

---

## Section 3 — Overview widgets

**UI:** three lists + best/weakest campaign cards

Same request as section 4 (one fetch for the overview page):

```
GET /api/admin/marketing-tracking?period=last_30_days
```

### أهم الكلمات في Google — `data.top_keywords[]`

| Field | UI |
|---|---|
| `keyword` | text |
| `rank` | optional number (`null` ok) |
| `status` | `increased` / `decreased` / `stable` |
| `status_label_ar` | ارتفعت / انخفضت / ثابتة |

### أكثر الصفحات والمقالات زيارة — `data.top_pages[]`

Empty until Search Console is connected.

| Field | UI |
|---|---|
| `visits` | number (right) |
| `title` | page title |
| `path` | muted |
| `type` | `page` / `service` / `article` |
| `type_label_ar` | صفحة / خدمة / مقال (grey tag) |

### أكثر الحملات تحقيقاً للطلبات — `data.top_campaigns[]`

| Field | UI |
|---|---|
| `orders` | «{orders} طلب» |
| `campaign` | name |
| `label_ar` + `color` | platform tag (قوقل / ميتا / …) |

### Highlight cards

- **أفضل حملة (ROAS)** ← `data.best_campaign` (green card)
- **أضعف حملة (ROAS)** ← `data.weakest_campaign` (red/pink card)

Either may be `null` → hide the card.

```json
{
  "kind": "best",
  "campaign": "قوقل - إعادة الاستهداف",
  "label_ar": "قوقل",
  "color": "blue",
  "roas": 3.7,
  "result_key": "profit",
  "result_label_ar": "ربح",
  "result_amount": 16500
}
```

- Title ROAS: `x{roas}` (`null` → `—`)
- Footer: `{result_label_ar} {result_amount} ريال` (`result_key` is `profit` or `loss`)
- «التفاصيل» can link to campaigns filtered by `source` + `campaign`

---

## Section 4 — ROAS dashboard

**UI:** dark header + 6 KPI cards + grouped bar chart «الصرف مقابل الإيراد – حسب القناة»

Uses the **same** `GET /api/admin/marketing-tracking` as section 3. On one overview route, call it once.

### Header (`data.summary`)

| Field | UI |
|---|---|
| `roas` | big `x2.28` (`null` → `—`) |
| `roas_caption_ar` | subtitle under ROAS (already Arabic sentence) |
| `spend` | إجمالي الصرف |
| `revenue` | إيراد مسند |
| `profit` | ربح صافي (prefix `+` if &gt; 0) |

Do **not** rebuild the caption. Use `roas_caption_ar` / `roas_caption_en`.

### KPI cards (`data.kpis`)

| Field | Card (AR) |
|---|---|
| `cac` | تكلفة العميل CAC (`null` → `—`) |
| `conversion_rate` | معدل التحويل زائر-عميل (already a percent number, append `%`) |
| `paying_customers` | عملاء دفعوا |
| `marketing_orders` | طلبات من التسويق |
| `app_visits.value` + `app_visits.change_percent` | زيارات التطبيق |
| `website_visits.value` + `website_visits.change_percent` | زيارات الموقع |

Visit change: green ▲ if `change_percent` ≥ 0, red ▼ if &lt; 0. Show as `15%`.

`website_visits.source` is `search_console` or `users` (debug only; do not show in UI).

### Chart (`data.chart[]`)

Grouped bars per channel (same 5 sources):

- Dark bar = `revenue` (الإيراد)
- Light bar = `spend` (الصرف)
- X label = `label_ar`
- Keep API order (Google → Meta → TikTok → Snapchat → X)

---

## Suggested routes

| Path | Sections | Endpoint |
|---|---|---|
| `/marketing` or `/marketing-overview` | 3 + 4 | `GET /marketing-tracking` |
| `/marketing/keywords` | 1 | `GET /marketing-tracking/keywords` |
| `/marketing/channels` | 2 | `GET /marketing-tracking/channels` |

Shared period store (query or context) so switching tabs keeps `period`.

---

## Styling tokens (from API, not guessed)

```
rank_tone:     good | warn | muted
competition:   high | medium | low
status:        increased | decreased | stable
roas_tone:     good | ok | bad | muted
color:         blue | purple | black | yellow | gray
result_key:    profit | loss
page type:     page | service | article
channel source: google | meta | tiktok | snapchat | twitter
```

---

## Related APIs (optional, same admin)

Not required to paint the three screens, but they fill the numbers:

| Use | Method | Path |
|---|---|---|
| Connect Google (ranks + pages) | POST | `/api/admin/seo-google/connect` |
| Console status | GET | `/api/admin/seo-google/status` |
| Import ad spend CSV/JSON | POST | `/api/admin/reports/marketing/spend` |
| Sync ad accounts | POST | `/api/admin/reports/marketing/sync` |
| UTM link template | GET | `/api/admin/reports/marketing/utm-template` |

If `top_pages` is empty and keyword `current_rank` is null, show a quiet hint: ربط Search Console من صفحة السيو.

---

## Do / don’t

- **Do** use `label_ar` when `Accept-Language: ar`.
- **Do** treat `null` rank / roas / cac as `—`.
- **Do** refetch on period change.
- **Don’t** compute ROAS, CAC, or funnel % on the client.
- **Don’t** drop channel rows with zero spend — always show all five.
- **Don’t** poll; these GETs are snapshots.

Postman: `postman/AQDI-Admin-Marketing-Tracking.postman_collection.json`  
Regenerate: `php tools/generate_marketing_tracking_postman.php`
