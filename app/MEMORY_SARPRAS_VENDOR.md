# Sarpras Enterprise Portal

## Vendor Portal Routes
- `/vendor/login` — GET: Login form | POST: Authenticate
- `/vendor` — Dashboard (authenticated)
- `/vendor/procurement` — Procurement Request list
- `/vendor/procurement/create` — Create new PR
- `/vendor/procurement/{id}` — PR detail
- `/vendor/procurement/{id}/status` — Update delivery status (POST)
- `/vendor/orders` — Order/Purchase list
- `/vendor/orders/{id}` — Order detail
- `/vendor/invoices` — Invoice list
- `/vendor/performance` — Vendor performance metrics

## Controller Files
- `app/Http/Controllers/Vendor/Auth/LoginController.php` — Vendor auth
- `app/Http/Controllers/Vendor/VendorPortalController.php` — Dashboard/orders/invoices/performance
- `app/Http/Controllers/Vendor/ProcurementController.php` — CRUD for procurement requests
- `app/Http/Controllers/Vendor/VendorRegisterController.php` — Vendor master registration
- `app/Http/Controllers/Vendor/VendorRankController.php` — Vendor ranking

## View Files
- `resources/views/vendor/auth/login.blade.php` — Vendor login page
- `resources/views/vendor/layouts/app.blade.php` — Portal layout
- `resources/views/vendor/dashboard.blade.php` — Dashboard
- `resources/views/vendor/procurement/*.{blade.php,index.blade.php,create.blade.php,show.blade.php}`
- `resources/views/vendor/orders/*.{blade.php,index.blade.php,show.blade.php}`
- `resources/views/vendor/invoices/index.blade.php`
- `resources/views/vendor/performance.blade.php`

## Vendor Authentication
- Uses `auth:vendor` middleware with `User` model
- `portal_login` (varchar 100) — username
- `portal_password` (hashed) — password
- `portal_setup_complete` (bool, default 0) — requires password change

## Key Models
- `ProcurementRequest` — purchases/orders (fields: purpose, urgency, budget_source, procurement_method, delivery_date, total_estimated_budget, status, vendor_name, notes)
- `ProcurementRequestItem` — individual items in PR
- `Vendor` — vendor master
- `VendorInvoice` — linked to vendor_id (foreign key to vendors.id)
- `WorkOrder` — linked to repair requests
- `PurchaseOrder` — vendor purchase orders

## Status Flow (ProcurementRequest)
draft → pending → approved → ordered → delivered → completed
(rejected/cancelled at any step)

## Data Query Pattern
- VendorPortalController uses `$vendor->name` for filtering ProcurementRequests by vendor_name
- VendorInvoice uses `vendor_id` FK for filtering
