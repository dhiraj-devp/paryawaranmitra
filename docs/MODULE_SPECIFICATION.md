# Module specification

Every module below has a permission pair, `{module}.view` and `{module}.manage`, defined in `config/access.php`. View shows the module. Manage creates and changes records. Neither permission allows silent deletion of financial history, applications, or audit logs.

Status in this phase:

| Status | Meaning |
| --- | --- |
| Available | A real screen exists and is backed by stored data. |
| Foundation | Permission, navigation, and audit or security behaviour exist. The business screen does not. |
| Reserved | Route reserved. No accounts and no workflow. |

## Main

### Dashboard — available

`/admin/dashboard` and `/staff/dashboard`. Shows the signed-in user's recent audit events. An administrator with `users.view` also sees active and inactive user counts. There are no sample charts.

### Notifications and reminders — foundation

Will list document, payment, consent, and compliance reminders. Automatic WhatsApp and email delivery belongs to Communications and Automation. The module does not send anything yet.

## Customer management

### Enquiries — available

`/admin/enquiries` and `/staff/enquiries`. `enquiries.view` opens the list and an enquiry. `enquiries.manage` creates and edits an enquiry, records follow-ups, assigns staff, and changes status. Records are not deleted.

An enquiry can be saved for a prospect with no client. Linking a client does not create another client. An industry unit is accepted only when it belongs to that client. A service is optional until conversion. When both a service and an industry unit are present, the service must be active and eligible for the unit's category. Inactive clients, units, and services cannot be newly linked.

Statuses are New, Contacted, Quoted, Follow-up, Converted, On hold, and Lost. Converted is set only by the conversion action. Sources are Phone, Walk-in, WhatsApp, Website, Referral, and Other.

Each follow-up is a new row: date and time, method, discussion, outcome, and an optional next date. A later follow-up does not change an earlier row. The method list is Phone, Walk-in, WhatsApp, Email, and Other. Nothing is sent by WhatsApp or email.

### Jobs and work orders — available

`/admin/work-orders` and `/staff/work-orders`. The permission slugs remain `work-orders.view` and `work-orders.manage`. `work-orders.view` opens the list, the work order, and its timeline. `work-orders.manage` converts an enquiry, corrects the description and dates, assigns staff, and changes status. Work orders are not deleted.

Conversion requires an active client, an active industry unit of that client, and an active service eligible for the unit. It allocates one work order number, creates one active work order, sets the enquiry to Converted, and writes both audit rows in one transaction. One enquiry can produce only one work order. A posted id that does not match a link already stored on the enquiry is rejected. Office executive can manage enquiries but cannot convert, because that role does not have `work-orders.manage`. Accounts can view both modules and cannot change them.

Work order statuses are Draft, Active, In progress, On hold, Completed, and Closed. A converted enquiry starts the work order as Active. The work order screen does not create a quotation, a checklist, an invoice, or an application. Staff who can manage quotations prepare the quotation from the work order.

### Clients — available

`/admin/clients` and `/staff/clients`. A person with `clients.view` can open the directory, a client profile, and that client's industry units. `clients.manage` creates and edits those records and changes status. Records are not deleted. Inactive is the closed state, and an inactive client cannot receive a new industry unit.

A client stores the legal name, trade name, constitution, contact, registered office, and optional PAN and GSTIN. GSTIN is an identity number. Tax treatment is still an open decision and is not calculated. The same normalised legal name must be confirmed before a second client is saved. PAN and GSTIN are unique when present, and a GSTIN must contain the PAN.

An industry unit belongs to one client. Its site address, plot, and site contact are separate from the registered office. The industry category comes from `config/industries.php`. Two units of the same client cannot share a normalised name. A unit cannot be moved onto another client by editing the form.

## Services and documents

### Services — available

`/admin/services` and `/staff/services`. `services.view` opens the catalogue and a service profile. `services.manage` creates, edits, and changes status. A service has a unique code, a name, an office category label, a short description, an optional detailed description, an estimated processing time, and internal notes. The category is a label the office types. It is not a statutory classification, and the catalogue is not limited to CTE, CTO, or any other preset service.

Eligible industry categories come from `config/industries.php`. One service can cover many categories, and one category can be covered by many services. A service with no selected category cannot be used for a new industry. An inactive service cannot be selected for new work and cannot receive a new fee schedule. Services are not deleted.

### Fees — available

`/admin/fees` and `/staff/fees`. `fees.view` opens the schedule list and the applicable-fee lookup. `fees.manage` creates a schedule, corrects that schedule, and changes its status. Each schedule belongs to one service and stores `government_fee` and `consultancy_fee` as `decimal(14,2)` INR. Staff enter both amounts. The application does not calculate a government fee.

A service keeps every schedule. A new schedule does not change an older row. Active schedules for the same service cannot overlap, including a later schedule that would fall inside an open-ended one. Future dates are allowed. The lookup uses the active schedule in force on the requested date, and only when the service itself is active. If none applies, the screen says that no fee schedule is configured. Inactive schedules stay in the table and are ignored by the lookup. Correcting a schedule writes the previous values to the audit log. Schedules are not deleted. A quotation copies the applicable amounts onto its own revision and does not write back to this table.

### Documents — foundation

Private files, verification, validity, and scope (`client`, `industry`, `application`). Reuse rules are in [BUSINESS_WORKFLOWS.md](BUSINESS_WORKFLOWS.md). Downloads will require authorization. The storage disk already refuses public serving.

### Applications — foundation

One industry unit has many applications. Each application keeps its own document links, submission, acknowledgement, and consent. Historical applications stay in the database.

### Notices and queries — foundation

Authority notices and queries against an application, with a response status and due date taken from stored rules rather than hardcoded offsets.

## Financial management

### Quotations — available

`/admin/quotations` and `/staff/quotations`. `quotations.view` opens the list, a quotation, its revisions, the comparison, and the PDF download. `quotations.manage` creates a quotation, saves a new revision, and changes status. There is no separate approve permission. Quotations are not deleted.

A quotation can be created only from a work order that is not closed, whose client and industry unit are active, and whose service is active. The client, unit, and service come from the work order. A posted client or service id is rejected. The issue date must have an applicable fee schedule. The service line copies that schedule's government and consultancy amounts. Staff may change the copied amounts, and the audit row records both the schedule amounts and the entered amounts. Additional lines are either government or consultancy, not both. Totals are calculated on the server. Tax is zero because `config/tax.php` is disabled.

The first save creates revision 1 and a number `QT-YYYY-NNNN`. A later save creates the next revision and leaves the number unchanged. Draft can become Sent or Cancelled. Sent can become Accepted, Rejected, Expired, or Cancelled. Accepted can become Cancelled, or staff can prepare a new draft revision without moving the accepted revision id. Rejected, Expired, and Cancelled cannot be revised.

### Billing and invoices — foundation

Raised from an accepted quotation revision after documents are verified. Invoice lines copy amounts. They do not point live at the fee master.

### Payments and receipts — foundation

Advance or full payment against an invoice. Receipts allocate. They do not erase the invoice.

### Expenses — foundation

Office expenses. Separate from government fees collected on behalf of a client.

### Revenue and profit — foundation

Consultancy revenue minus expenses. Government fees are excluded from revenue.

## Operations

### Compliance — foundation

Future obligations derived from consents and deadline data.

### Authorizations and returns — foundation

Returns and authorizations tied to an industry unit.

### Bank guarantees — foundation

Instrument, amount, issuer, and validity. Amounts use the money convention.

### ETP, STP, and APCS — foundation

Treatment and control systems installed at an industry unit.

### Site visits — foundation

Visit date, staff, industry unit, and notes.

### Tasks and staff — foundation

Internal tasks assigned to staff users. This is not the customer portal.

## Communication and reports

### WhatsApp and email — foundation

Outbound messages and the template used. The provider is not chosen. No message is sent in this phase.

### Reports — foundation

Operational and financial reports over the real tables. No report is stubbed with static numbers.

## System

### Automation — foundation

Rules that create reminders. Deadlines come from data. The queue driver is database today and Redis later. No scheduler entries pretend to send messages.

### Users and permissions — available to administrators with `users.view`

`/admin/users` lists name, email, portal, roles, status, and last login. It is read-only. Creating users, editing roles in the browser, and deleting users are not implemented. Role assignment exists as `AssignRole` for seeders and later screens.

### Settings and audit logs — available to administrators

`/admin/settings` shows non-secret runtime facts: environment, timezone, locales, session lifetime, cache store, queue driver, and whether the administrator two-factor flag is on. It does not show `APP_KEY` or passwords.

`/admin/settings/audit-logs` lists the latest audit events for users with `audit.view`. Rows cannot be edited or deleted.

## Customer portal — reserved

`/customer/login` and `/customer/dashboard` render one holding page. There is no password field, no POST login, and no registration route. Customer accounts will be a future `customer_accounts` table and a `customer` guard.
