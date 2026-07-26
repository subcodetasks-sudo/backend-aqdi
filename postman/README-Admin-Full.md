# AQDI Admin API — Postman

## Full collection
File: [`AQDI-Admin-API-Full.postman_collection.json`](./AQDI-Admin-API-Full.postman_collection.json)

- **307** requests across **21** folders
- Generated from live Laravel `api/admin/*` routes
- Example JSON bodies for create/update endpoints
- Login saves `employee_token` automatically

## Import
1. Postman → Import → select `AQDI-Admin-API-Full.postman_collection.json`
2. Set collection variables:
   - `baseUrl` → `https://aqdi.sa` (or local)
   - run **01 Auth & Employees → POST /employees/login**
3. Token is stored in `employee_token`

## Regenerate after route changes
```bash
php postman/generate_admin_collection.php
```

## Folders
1. Auth & Employees  
2. Notifications (Firebase)  
3. Analytics & Dashboard  
4. Orders & Contracts (status, units, comments, receive)  
5. Refunds  
6. Payments & Finance  
7. Users  
8. Locations  
9. Real Estate & Units  
10. Tenant Roles  
11. Roles & Permissions  
12. Contract Statuses (+ draft statuses)  
13. Contract Periods  
14. WhatsApp Contracts  
15. Coupons  
16. Content & CMS  
17. Message Alerts  
18. Blogs  
19. SMS  
20. Settings  
21. Ads  
