# Postman collections

## Import into Postman (v2.1)

| File | Use |
|------|-----|
| **`AQDI-Admin-Refundable-Contracts.postman_collection.json`** | **Refunds:** `POST /refundable-contracts` + `UpdateRefundableContractApprovalRequest` on analytics refunds |
| **`AQDI-Admin-Return-Contract-Status.postman_collection.json`** | **Return orders:** `POST …/return-contract-status` with `accept_retrun_contract` JSON |
| **`AQDI-Contracts-API.postman_collection.json`** | **V2 client API:** contract steps 1–6, listing, uncompleted |
| **`AQDI-Admin-Selected-API.postman_collection.json`** | **Recommended:** Orders POST, refund approve/reject, instruction-sections only |
| `AQDI-Admin-Analytics-API.postman_collection.json` | Analytics, refunds, orders, instruction sections (full set) |
| `AQDI-Admin-Orders-Refunds-Instructions.postman_collection.json` | Larger subset with GET lists |
| `AQDI-Admin-API.postman_collection.json` | Full admin API (if generated) |
| `AQDI-Admin-Employees-API.postman_collection.json` | Employees only |

## Do **not** import

- `admin-analytics-api-filters.en.json` — API reference / filter documentation only, not Postman format.

Regenerate collections after route changes:

```bash
php tools/generate_contracts_api_postman_collection.php
php tools/generate_admin_postman_collection.php
php tools/convert_analytics_filters_to_postman.php
```

Set collection variable `baseUrl` (e.g. `http://localhost:8000`).
