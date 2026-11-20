# Sidebar structure

The sidebar is defined in `config/navigation.php` and rendered by `NavigationBuilder`. Labels come from `lang/*/ui.php`, so English and Marathi stay in the language files.

An item is included only when the signed-in user has its view permission. Dashboard has no permission and is always shown inside an authenticated portal. Clients, Enquiries, Work orders, Services, Fees, Quotations, Users, and Settings are linked because those routes exist. Every other business item has `route` set to null and is shown as later work. Staff never see Users, Settings, or Automation unless a future role grants those permissions. The seeded staff roles do not.

The desktop sidebar collapses to icons. The choice is stored in `localStorage` under `pm.sidebar`. The mobile sidebar is a checkbox drawer so it opens without JavaScript. There is no animation.

## Main

| Item | Key | Permission | Route |
| --- | --- | --- | --- |
| Dashboard | dashboard | none | `{portal}.dashboard` |
| Notifications and reminders | notifications | notifications.view | later |

## Customer management

| Item | Key | Permission | Route |
| --- | --- | --- | --- |
| Enquiries | enquiries | enquiries.view | `{portal}.enquiries.index` |
| Clients | clients | clients.view | `{portal}.clients.index` |
| Jobs and work orders | work_orders | work-orders.view | `{portal}.work-orders.index` |

## Services and documents

| Item | Key | Permission | Route |
| --- | --- | --- | --- |
| Services | services | services.view | `{portal}.services.index` |
| Fees | fees | fees.view | `{portal}.fees.index` |
| Documents | documents | documents.view | later |
| Applications | applications | applications.view | later |
| Notices and queries | notices | notices.view | later |

## Financial management

| Item | Key | Permission | Route |
| --- | --- | --- | --- |
| Quotations | quotations | quotations.view | `{portal}.quotations.index` |
| Billing and invoices | invoices | invoices.view | later |
| Payments and receipts | payments | payments.view | later |
| Expenses | expenses | expenses.view | later |
| Revenue and profit | revenue | revenue.view | later |

## Operations

| Item | Key | Permission | Route |
| --- | --- | --- | --- |
| Compliance | compliance | compliance.view | later |
| Authorizations and returns | authorizations | authorizations.view | later |
| Bank guarantees | bank_guarantees | bank-guarantees.view | later |
| ETP, STP, and APCS | treatment_plants | treatment-plants.view | later |
| Site visits | site_visits | site-visits.view | later |
| Tasks and staff | tasks | tasks.view | later |

## Communication and reports

| Item | Key | Permission | Route |
| --- | --- | --- | --- |
| WhatsApp and email | communications | communications.view | later |
| Reports | reports | reports.view | later |

## System

| Item | Key | Permission | Route |
| --- | --- | --- | --- |
| Automation | automation | automation.view | later |
| Users and permissions | users | users.view | `{portal}.users.index` |
| Settings and audit logs | settings | settings.view | `{portal}.settings.index` |

Settings is one navigation item. Audit logs are a section on the settings page, gated again by `audit.view`. A person could hold `settings.view` without `audit.view`; the seeded super-admin holds both.

## Quick Create

The header button reads `config/navigation.php` `quick_actions`. Enquiries register "New enquiry" for `enquiries.manage`. Clients register "New client" for `clients.manage`. Services register "New service" for `services.manage`. Fees register "New fee schedule" for `fees.manage`. Quotations register "New quotation" for `quotations.manage`. A later module adds an action with:

- `label` — a translation key
- `route` — the route name without the portal prefix, for example `invoices.create`
- `permission` — the manage permission required
- `portals` — `admin`, `staff`, or both

The link is rendered only when `Route::has("{portal}.{route}")` is true and the user has the permission. An action whose route is missing stays hidden. The button then explains that no create action is available.

## Header

The header contains global search, Quick Create, the language form, the signed-in name, and a POST logout control. Search submits to `{portal}.search`. Logout is POST only. GET logout is rejected.
