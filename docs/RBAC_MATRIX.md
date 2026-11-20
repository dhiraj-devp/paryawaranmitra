# Role and permission matrix

Permissions are `{module}.view` and `{module}.manage`. View is required to see a module. Manage includes view for that module. The super-admin role is stored as `*` and expands to every current permission at sync time. `User::hasPermission()` also treats an active super-admin as allowed for any slug, including one added after the last sync.

Policies still refuse creating, updating, and deleting users and audit logs. Those are not granted by `*`.

Portal must match. Staff roles cannot be assigned to an admin user.

## Roles

| Role | Portal | Intent |
| --- | --- | --- |
| super-admin | admin | Organisation administration. |
| office-manager | staff | Daily client work, with read access to money. |
| accounts | staff | Quotations, invoices, receipts, expenses, and revenue. |
| consultant | staff | Applications, compliance, and site work. |
| office-executive | staff | Enquiries, clients, and document collection. |

All five are system roles and cannot be deleted.

## Matrix

`M` means view and manage. `V` means view only. A blank cell means no access.

| Module | Super Admin | Office Manager | Accounts | Consultant | Office Executive |
| --- | --- | --- | --- | --- | --- |
| Notifications | M | M | V | V | M |
| Enquiries | M | M | V | M | M |
| Clients | M | M | V | V | M |
| Jobs and work orders | M | M | V | M | V |
| Services | M | M |  | V | V |
| Fees | M | M |  | V | V |
| Documents | M | M |  | M | M |
| Applications | M | M |  | M |  |
| Notices and queries | M | M |  | M |  |
| Quotations | M | V | M |  |  |
| Invoices | M | V | M |  |  |
| Payments and receipts | M | V | M |  |  |
| Expenses | M | V | M |  |  |
| Revenue and profit | M | V | M |  |  |
| Compliance | M | M |  | M |  |
| Authorizations and returns | M | V |  | M |  |
| Bank guarantees | M | V |  | M |  |
| ETP, STP, APCS | M | V |  | M |  |
| Site visits | M | M |  | M |  |
| Tasks and staff | M | M |  | M | M |
| WhatsApp and email | M | M |  |  | V |
| Reports | M | M | V | V |  |
| Automation | M |  |  |  |  |
| Users and permissions | M |  |  |  |  |
| Settings | M |  |  |  |  |
| Audit logs | M |  |  |  |  |

Dashboard is available to every signed-in user of that portal. It is not a permission.

Services and fees now have screens. Enquiries, work orders, and quotations now have screens as well. The cells above are unchanged. Office manager and consultant can manage both enquiries and work orders. Office executive can manage enquiries and can view work orders, so that role cannot convert an enquiry. Accounts can view both and cannot change them. Office manager can view quotations and cannot change them. Accounts can manage quotations. Consultant and office executive have no quotation access. There is no `quotations.approve` permission. Acceptance is a status change under `quotations.manage`. The super-admin role still expands to every permission. The work-order permission slug is `work-orders`, matching the module key already in `config/access.php`.

## What manage does not mean

- Manage on invoices, payments, expenses, or quotations will never mean hard delete. Void through a status, inside a transaction, with an audit row.
- Manage on applications will never mean deleting a submitted application.
- Manage on users does not currently expose a screen that creates or edits users. The directory is read-only.
- Audit manage does not allow editing or deleting log rows. The audit policy returns false for those abilities.

## Changing the matrix

Edit `config/access.php` and run the access seeder or `AccessCatalog::sync()`. The sync updates names and inserts missing rows. It does not delete a permission that was removed from the file. Remove an obsolete permission in a reviewed migration if it is truly unused.

Assign roles through `App\Actions\Access\AssignRole`. Do not attach `role_user` rows from a controller.
