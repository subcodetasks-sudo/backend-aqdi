# Prompt: Firebase + API v2 contract tracking (mobile / web)

Copy everything below the line into a frontend/mobile agent (Cursor, Claude, etc.) to implement contract **tracking**. Backend already ships the REST fields and FCM data payload; do not invent extra keys.

---

You are implementing **live contract tracking** for Aqdi (أقدي). The Laravel backend already exposes a unified status payload on API v2 contract endpoints **and** the same fields inside Firebase Cloud Messaging `data`. Your job is to wire the tracking UI + FCM so they stay in sync.

Do **not** invent field names. Use the keys below exactly. Prefer API v2 (`/api/v2`) over v1.

## Goal

1. Register the device FCM token so the contract owner receives status pushes.
2. Show a tracking screen (list + detail + timeline) from API v2.
3. On FCM `contract_status_changed` / `contract_received`, refresh that contract and update the UI without a full app restart.
4. Status chip, color, Arabic label, and client explanation on the tracking screen must match GET `/api/v2/contracts/{id}`.

## Auth and headers

- Base URL: `{APP_URL}/api/v2`
- Auth: `Authorization: Bearer {sanctum_token}` (user token, not employee).
- `Accept: application/json`
- Locale: `Accept-Language: ar` (or `en`) and/or `X-Locale`. Tracking copy is mostly Arabic as stored in admin statuses.

Envelope for success:

```json
{
  "message": "...",
  "code": 200,
  "success": true,
  "data": {}
}
```

List endpoints nest pagination **inside** `data`:

```json
{
  "data": {
    "data": [ /* ContractResource[] */ ],
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "from": 1,
      "to": 10,
      "per_page": 10,
      "total": 10
    }
  }
}
```

Detail `GET /contracts/{id}` puts the contract object directly in top-level `data` (not `data.data`).

## 1) Save FCM token (required for tracking pushes)

After Firebase SDK init, send the token:

- Login: `POST /api/v2/auth/login` with `{ "mobile", "password", "fcm_token": "<token>" }` (`fcm_token` is optional but should be sent).
- Signup: `POST /api/v2/auth/signup` also accepts `fcm_token`.
- Anytime later: `POST /api/v2/fcm` with `{ "fcm_token": "<token>" }` (auth required).

Re-send on token refresh (`onTokenRefresh` / `onNewToken`). Without a stored token, status pushes are silently skipped.

## 2) Contract tracking REST API

All require `auth:sanctum`. Page size is 10.

| Method | Path | Use |
|---|---|---|
| GET | `/api/v2/contracts` | Owner’s submitted contracts (reached admin order step, not deleted) |
| GET | `/api/v2/contracts/{id}` | Tracking detail + full timeline |
| GET | `/api/v2/contracts/status/{statusId}` | Filter by `contract_statuses.id` |
| GET | `/api/v2/contracts/draft` | Drafts that reached admin |
| GET | `/api/v2/contracts/draft/status/{statusId}` | Filter by `draft_contract_statuses.id` |
| DELETE | `/api/v2/contracts/{id}` | Soft-delete (owner only; completed cannot be deleted) |

Use **detail** for the tracking screen. List cards can use the same status fields (timeline is included on list resources too).

## 3) Unified status fields (API + FCM)

Same mapper: `ContractFrontendStatus`.

If `is_draft` is true and `draft_contract_status_id` is set, current status comes from **draft** statuses. Otherwise from **contract** statuses.

| Field | Type | Meaning |
|---|---|---|
| `status` | string | Machine key for UI/routing (`new`, `under_review`, `paid`, `ejar_authenticated`, …). Not a numeric id. |
| `status_label` | string | Arabic dashboard name as stored (show this on chips). |
| `status_type` | string | `draft` \| `contract` \| `system` (timeline rows can be `system`, e.g. payment). |
| `status_id` | int \| null | Row id in `contract_statuses` or `draft_contract_statuses`. |
| `status_color` | string \| null | Hex, e.g. `#16A34A`. Use for chip / timeline dot. |
| `status_description` | string \| null | **Tracking copy**: client explanation, else internal description. Prefer this on the tracking card. |
| `status_client_explanation` | string \| null | شرح الحالة للعميل only (may be null). |
| `is_draft` | bool | Draft vs completed contract. |
| `journey_status` | string | Same as `status`. |
| `journey_status_label` | string | Same as `status_label`. |
| `journey` | array | Alias of `status_timeline`. |
| `status_timeline` | array | Only statuses that **happened** (history), oldest → newest. |

Known `status` keys (from Arabic names). Unknown names become `status_{md5prefix}` or `{status_type}_{id}`:

| Arabic label | `status` key |
|---|---|
| جديد | `new` |
| قيد المراجعة | `under_review` |
| مكتمل | `completed` |
| ملغى | `cancelled` |
| معلق | `on_hold` |
| مستلم | `received` |
| مستلم من الموظف | `received_by_employee` |
| تم الدفع | `paid` |
| إرسال مسودة العقد لكم عبر واتساب (and spelling variant) | `whatsapp_draft` |
| توثيق العقد في إيجار (and spelling variant) | `ejar_authenticated` |
| (draft with no row) | `draft` |

Do **not** hardcode a fixed 5-step wizard. The timeline is dynamic.

Legacy fields still present (do not use for tracking UI):

- `contract_status_id`, `contract_status_name`, `contract_status_color`, `contract_status_icon`
- `draft_contract_status_id`, `draft_contract_status_name`, `draft_contract_status_color`

Also on the resource: `time_to_documentation_contract` (`Y-m-d H:i:s` or null) — optional countdown on the tracking screen.

### Timeline item shape (`status_timeline[]` / `journey[]`)

```json
{
  "id": 12,
  "status": "paid",
  "status_label": "تم الدفع",
  "status_color": "#16A34A",
  "status_description": "تم استلام المقابل المالي",
  "client_explanation": "تم استلام المقابل المالي",
  "status_client_explanation": "تم استلام المقابل المالي",
  "status_type": "system",
  "status_id": null,
  "state": "completed",
  "source": "payment",
  "meta": null,
  "created_at": "2026-09-08T07:00:00+00:00"
}
```

- `state`: `"completed"` for past steps, `"current"` for the last row only. There is no `"upcoming"` row.
- `source`: `system` \| `admin` \| `payment` \| `receive` (and similar).
- Render as a vertical stepper: completed checkmarks, current highlighted, using `status_color` and `status_label`. Body text = `status_description` (or `status_client_explanation` if you want client-only copy).

Example detail `data` (tracking-relevant subset):

```json
{
  "id": 123,
  "uuid": "…",
  "contract_type": "residential_units",
  "is_completed": true,
  "is_draft": false,
  "status": "under_review",
  "status_label": "قيد المراجعة",
  "status_type": "contract",
  "status_id": 2,
  "status_color": "#F59E0B",
  "status_description": "جاري مراجعة الطلب",
  "status_client_explanation": "جاري مراجعة الطلب",
  "journey_status": "under_review",
  "journey_status_label": "قيد المراجعة",
  "status_timeline": [],
  "journey": [],
  "time_to_documentation_contract": "2026-09-15 12:00:00"
}
```

## 4) Firebase Cloud Messaging (tracking)

Backend: Firebase HTTP v1. Android `priority: high`. iOS sound `default`. All `data` values are **strings**.

### Notification types for the **contract owner** (user FCM token)

#### `type = "contract_status_changed"`

Sent when admin changes contract or draft status.

- `notification.title`: `تحديث حالة طلبك`
- `notification.body`: `طلبك رقم {id padded to 6}: {status_label}`

`data` (all strings):

| Key | Example |
|---|---|
| `type` | `contract_status_changed` |
| `contract_id` | `123` |
| `contract_uuid` | uuid or `""` |
| `status` | `under_review` |
| `status_label` | `قيد المراجعة` |
| `status_type` | `contract` or `draft` |
| `status_id` | `"2"` or `""` |
| `status_color` | `#F59E0B` |
| `status_description` | tracking copy |
| `status_client_explanation` | client copy or `""` |
| `is_draft` | `"1"` or `"0"` |
| `journey_status` | same as `status` |
| `journey_status_label` | same as `status_label` |
| `timeline_count` | `"3"` |
| `timeline_current_label` | last timeline label |

On receive:

1. Parse `data.type`.
2. If `contract_status_changed`, navigate or highlight contract `contract_id`.
3. **Re-fetch** `GET /api/v2/contracts/{contract_id}` (FCM has no full `status_timeline` array). Update local cache from REST.
4. You may optimistic-update the chip from `status` / `status_label` / `status_color` before the fetch returns.

#### `type = "contract_received"`

Contract marked received by an employee. Sent to **all employees** and to the **owner**.

- Title: `تم استلام عقد`
- Body: `تم استلام العقد رقم {id} من الموظف {name}`

`data` = same status fields as above, plus:

| Key | Example |
|---|---|
| `type` | `contract_received` |
| `employee_id` | `"4"` |
| `employee_name` | employee name |
| `contract_status_id` | numeric id as string |

Owner app: treat like a status change — refresh that contract.

### Employee-only types (admin / employee app, not customer tracking)

Sent to topic `employees` (config `FIREBASE_EMPLOYEES_TOPIC`) **and** each employee `fcm_token`.

| `type` | When | Title (AR) |
|---|---|---|
| `new_contract_draft` | User saved a draft | مسودة عقد جديدة |
| `new_contract_paid` | Paid / completed | عقد جديد مدفوع |
| `new_contract` | New unpaid contract | عقد جديد |

Employee `data` strings: `contract_id`, `contract_uuid`, `contract_type`, `is_draft` (`1`/`0`), `is_paid` (`1`/`0`), `paid_amount`, `user_id`, `user_name`.

Employee token: `POST /api/admin/employees/login` with `fcm_token`, or `POST /api/admin/employees/fcm`.

Foreground: handle `data` even if the system tray notification is not shown. Background/killed: tap should open tracking for `contract_id`.

## 5) Tracking UI spec

- **List**: `GET /contracts`. Chip = `status_label` + `status_color`. Subtitle = `status_description`. Badge if `is_draft`.
- **Detail**: vertical timeline from `status_timeline` (or `journey`). Current step = `state === "current"`. Header chip uses top-level `status_*` fields (same as last timeline item).
- **Empty timeline**: still show current `status_label` (backend seeds history on read when empty).
- **Pull to refresh** detail.
- **Deep link / FCM tap**: `/contracts/{contract_id}` tracking screen.
- **Do not** map `status` through a local enum only — fall back to `status_label` for unknown keys.

## 6) Implementation checklist

- [ ] FCM token sent on login and `POST /fcm`
- [ ] Token refresh re-posted
- [ ] Tracking list + detail use `status`, `status_label`, `status_color`, `status_description`
- [ ] Timeline rendered from `status_timeline` (`state` completed vs current)
- [ ] Handler for `contract_status_changed` refreshes `GET /contracts/{id}`
- [ ] Handler for `contract_received` (owner) same as status change
- [ ] Notification tap opens that contract
- [ ] All FCM `data` values treated as strings (`is_draft === "1"`)
- [ ] Employee app (if in scope) handles `new_contract*` types

## Out of scope

- Firebase Realtime Database (`FIREBASE_DATABASE_URL`) is used for **SEO crawl status**, not contract tracking. Do not listen to RTDB for contracts.
- Do not call admin `POST /api/admin/notifications/*` from the customer app.
- Do not use API v1 contract resources for the tracking screen.

When done, list the screens/files you changed and how FCM `type` maps to navigation.
