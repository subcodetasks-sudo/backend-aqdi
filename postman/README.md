# Postman collections

## Import into Postman (v2.1)

| File | Use |
|------|-----|
| **`AQDI-Admin-Selected-API.postman_collection.json`** | **Recommended:** Orders POST, refund approve/reject, instruction-sections only |
| `AQDI-Admin-Analytics-API.postman_collection.json` | Analytics, refunds, orders, instruction sections (full set) |
| `AQDI-Admin-Orders-Refunds-Instructions.postman_collection.json` | Larger subset with GET lists |
| `AQDI-Admin-API.postman_collection.json` | Full admin API (if generated) |
| `AQDI-Admin-Employees-API.postman_collection.json` | Employees only |

## Do **not** import

- `admin-analytics-api-filters.en.json` — API reference / filter documentation only, not Postman format.

Regenerate analytics collection after editing the filters file:

```bash
php tools/convert_analytics_filters_to_postman.php
```

Set collection variable `baseUrl` (e.g. `http://localhost:8000`).
