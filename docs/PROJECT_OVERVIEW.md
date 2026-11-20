# Paryavaran Mitra ERP

Paryavaran Mitra is an environmental and MPCB consultancy in Maharashtra. This application is the office system for enquiries, clients, documents, quotations, billing, applications, compliance, and the audit trail around that work.

Phase 0 establishes the foundation. It does not implement the business modules.

## What this phase includes

- A Laravel 12 application using Blade, Livewire, Alpine.js, and Tailwind CSS.
- Separate Super Admin and Staff areas, each with its own login, dashboard, and navigation.
- A reserved Customer area with no authentication and no registration.
- English and Marathi, with a language choice stored on the user and in the session.
- Role and permission records, policies, and an immutable audit log.
- The application shell: sidebar, header, search, forms, tables, and status badges.
- Automated tests for authentication, portal isolation, permissions, search, localization, and audit behaviour.

## What this phase does not include

Business screens for documents, invoices, and the rest of the sidebar are planned and permissioned. Clients, industry units, services, fee schedules, enquiries, work orders, and quotations are built. The remaining sidebar items are marked as later work.

## Stack

| Concern | Choice |
| --- | --- |
| Application | Laravel 12.12 (framework 12.x), PHP 8.2 |
| UI | Blade, Livewire 4, Alpine.js (bundled with Livewire), Tailwind CSS 4 |
| Database | MySQL or MariaDB in deployment. Local development currently uses SQLite because the XAMPP MariaDB service was not running. |
| Cache, queue, session | Database drivers today. Redis configuration is present and is the intended production switch once the PHP Redis extension exists. |
| Fonts | Inter for English. Noto Sans Devanagari for Marathi. Both are self-hosted. |

Laravel 13 requires PHP 8.3. This machine has PHP 8.2.12, so the project stays on the latest Laravel 12 release that supports it. There is no Node.js toolchain and no React application.

## Portals

| Area | Entry | Who uses it |
| --- | --- | --- |
| Super Admin | `/admin/login`, `/admin/dashboard` | Organisation administrators |
| Staff | `/staff/login`, `/staff/dashboard` | Office employees |
| Customer | `/customer/login`, `/customer/dashboard` | Reserved. Both addresses show the same holding page. |

Admin and staff accounts live in one `users` table, distinguished by `portal`. Customer accounts, when they are built, will use a separate table and a separate guard. They will not be rows in `users`.

## How to read the rest of the foundation

- [ARCHITECTURE.md](ARCHITECTURE.md) — how the code is organised.
- [DATABASE_DESIGN.md](DATABASE_DESIGN.md) — tables that exist, and the rules later tables must follow.
- [MODULE_SPECIFICATION.md](MODULE_SPECIFICATION.md) — each sidebar module and its current status.
- [SIDEBAR_STRUCTURE.md](SIDEBAR_STRUCTURE.md) — navigation groups and permission gates.
- [UI_DESIGN_SYSTEM.md](UI_DESIGN_SYSTEM.md) — visual rules and shared components.
- [SECURITY_POLICY.md](SECURITY_POLICY.md) — authentication, files, sessions, and audit.
- [RBAC_MATRIX.md](RBAC_MATRIX.md) — roles and what they may do.
- [BUSINESS_WORKFLOWS.md](BUSINESS_WORKFLOWS.md) — the consultancy process the later modules must follow.
- [TESTING_STRATEGY.md](TESTING_STRATEGY.md) — what is tested and how to run it.
- [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) — local setup, MySQL, backups, and Redis.
