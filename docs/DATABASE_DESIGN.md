# Database design

Phase 0 migrates the foundation tables. Clients, industry units, services, fee schedules, enquiries, follow-ups, work orders, and quotations are migrated as well. The names under "Conventions for later business tables" are not created yet, and the application does not pretend they exist.

## Engine and types

- Production target is MySQL 8 or MariaDB 10.11 or newer.
- The XAMPP install on this machine is MariaDB 10.4.32. That server was not running during foundation setup, and 10.4 is older than the production target. Do not rely on CHECK constraints until the production engine is confirmed. Enforce those rules in actions as well as in the database.
- Store enums as strings and back them with PHP enums. Do not use MySQL ENUM columns.
- Primary keys are `id` bigints.
- Foreign keys are required. Index foreign keys and the columns used by filters and uniqueness checks.
- Money is `decimal(14,2)`. Currency is INR. Do not use floats.
- Government fees and consultancy fees are separate columns. A single blended amount is not acceptable.
- Timestamps are stored by Laravel in UTC and displayed in `Asia/Kolkata`.
- Do not use soft deletes on financial records, applications, consents, or audit logs. Use an explicit status such as `void` or `withdrawn`, and keep the row.
- Number sequences for enquiries, work orders, and quotations are rows in `number_sequences`, allocated inside the same transaction as the record. Invoice, receipt, and application sequences are not implemented.

The Laravel queue table is already named `jobs`. Consultancy jobs will use `work_orders`. Never put business rows in `jobs`.

## Tables that exist

### users

Office accounts only. One email is unique across both portals.

| Column | Notes |
| --- | --- |
| portal | `admin` or `staff`. Indexed. Default `staff`. |
| locale | `en` or `mr`. Default `en`. |
| is_active | Indexed. Inactive users fail permission checks and are logged out by portal middleware. |
| last_login_at | Set on successful sign-in. |
| two_factor_secret | Encrypted, hidden, not fillable. |
| two_factor_recovery_codes | Encrypted array, hidden, not fillable. |
| two_factor_confirmed_at | Null until a future enrollment flow confirms a device. |

`password_reset_tokens` and `sessions` are the framework tables. Password reset screens are not built. Session payload is encrypted (`SESSION_ENCRYPT=true`).

### roles, permissions, permission_role, role_user

Roles carry `portal` and `is_system`. System roles cannot be deleted. Pivot tables use a composite primary key and cascade when a role or permission row is removed. The catalog sync does not delete missing permission rows by itself.

### audit_logs

Append only. No `updated_at`. Model hooks throw on update and delete.

| Column | Notes |
| --- | --- |
| actor_id | Nullable, nulls if the user is removed. |
| portal | Portal of the actor at the time of the event. |
| action | Indexed. Dotted name such as `auth.login`. |
| subject_type, subject_id | Nullable morph. |
| old_values, new_values | JSON. Secrets listed in `AuditLogger` are removed before insert. |
| ip_address, user_agent | Request metadata. |
| created_at | Indexed. |

### Framework tables

`cache`, `cache_locks`, `jobs`, `job_batches`, and `failed_jobs` support the database cache and queue drivers. They are infrastructure, not business data.

### clients

The legal customer. PAN and GSTIN are unique when present. Rows are not deleted.

| Column | Notes |
| --- | --- |
| legal_name, name_key | `name_key` is the normalised name used for duplicate detection. |
| trade_name | Optional. |
| constitution | String backed by `ClientConstitution`. |
| status | `active` or `inactive`. |
| pan, gstin | Optional identity numbers. Not a tax calculation. |
| contact and office columns | Email, phone, contact name, address, city, district, state, PIN. |
| created_by | Nullable user. Null if that user is removed. |

### industry_units

Many per client. `client_id` restricts deletion of the client. The pair `(client_id, name_key)` is unique. Site columns are the plant address, separate from the client's office. `industry_category` is a slug from `config/industries.php`.

### services

The consultancy catalogue. `code` is unique. `category` is an office label, not a statutory list. `expected_processing_days` is an estimate. `status` is `active` or `inactive`. Rows are not deleted. `created_by` and `updated_by` are nullable users.

### service_industries

One row per service and industry category. `industry_category` is a slug from `config/industries.php`. The pair `(service_id, industry_category)` is unique. Replacing the set is audited as `service.eligibility_changed`.

### fee_schedules

Many per service. `service_id` restricts deletion of the service. `effective_from` is required and `effective_until` is optional. `government_fee` and `consultancy_fee` are `decimal(14,2)`. `status` is `active` or `inactive`. Active ranges for one service must not overlap. Rows are not deleted. A correction updates that row and keeps the previous amounts in `audit_logs`.

### number_sequences

One row per series and year, such as `enquiry:2026`, `work_order:2026`, or `quotation:2026`. `last_number` is incremented inside the transaction that creates the record. The visible numbers are `ENQ-YYYY-NNNN`, `WO-YYYY-NNNN`, and `QT-YYYY-NNNN`. The year comes from the enquiry date, the work order start date, or the quotation issue date.

### enquiries

An incoming request. `number` is unique. `client_id`, `industry_unit_id`, and `service_id` are optional until conversion. `source` and `status` are strings backed by PHP enums. `assigned_to`, `created_by`, and `updated_by` are nullable users. Rows are not deleted. After conversion, the client, industry unit, and service links stay as stored.

### enquiry_follow_ups

Append-only notes for one enquiry. The model throws on update and delete. `followed_up_at` is a date-time, `method` is a string, and `outcome` is a short office note. `created_at` is stored. There is no `updated_at`.

### work_orders

One confirmed job. `number` is unique. `enquiry_id` is unique, so a second work order for the same enquiry cannot be stored. `client_id`, `industry_unit_id`, and `service_id` are required. `status` is a string. Rows are not deleted. The table is `work_orders`, not the queue table `jobs`.

### quotations

One commercial offer for a work order. A work order can have more than one quotation. `quotation_number` is unique. `work_order_id` restricts deletion of the work order. The client, industry unit, and service are read from the work order and are not stored again. `current_revision_id` points at the working revision. `accepted_revision_id` points at the revision that was accepted, and it stays in place when a later revision is drafted. `status` is a string. Rows are not deleted.

`current_revision_id` and `accepted_revision_id` are nullable until the first revision exists. Each is unique. The foreign keys are added after `quotation_revisions` exists.

### quotation_revisions

Immutable snapshots. The model throws on update and delete. `(quotation_id, revision_number)` is unique. `issue_date` and `valid_until` are dates. `government_total`, `consultancy_total`, `subtotal`, `discount_amount`, `tax_amount`, and `grand_total` are `decimal(14,2)`. Tax is stored as `0.00` while tax settings are pending. `payment_terms`, `terms_and_conditions`, `customer_notes`, and `internal_notes` are optional text. `created_by` is a nullable user.

### quotation_lines

Immutable rows for one revision. The model throws on update and delete. `line_type` is `service` or `custom`. A service line keeps government and consultancy unit amounts in separate columns and records the fee schedule id plus the schedule amounts that were copied. `amounts_adjusted` is true when the staff member changed those copied amounts. A custom line sets `charge_side` to `government` or `consultancy` and leaves the other unit amount at `0.00`. `quantity` is `decimal(10,2)`. Line discounts and line totals are `decimal(14,2)`. `tax_code` is reserved and `tax_amount` is `0.00`.

## Conventions for later business tables

These names are the contract for the modules that are not built yet. Create them with migrations when the module is actually built. `clients`, `industry_units`, `services`, `service_industries`, `fee_schedules`, `enquiries`, `enquiry_follow_ups`, `work_orders`, `quotations`, `quotation_revisions`, and `quotation_lines` already exist and are described above.

### Parties and work

Document checklists, invoices, and applications will belong to a work order. Those tables are not created yet.

### Catalog

Regulatory deadlines live in data tables with effective dates. They are not constants in code. `services`, `service_industries`, and `fee_schedules` already exist and are described above.

### Quotations

Quotations are built. See the table sections above. A revision copies fee amounts. It does not update `fee_schedules`.
- `quotation_revisions` are immutable snapshots. Editing a quotation inserts a new revision. It never updates the fee schedule.
- `quotation_lines` copy `government_fee` and `consultancy_fee` onto the revision so later master changes do not rewrite history. Staff may edit those copied amounts.

### Documents

- `document_types` describe what is required, and the scope: `client`, `industry`, or `application`.
- `documents` store the private disk path, verification status, `valid_from`, `valid_until`, and the owning client, industry unit, or application.
- Client-scoped documents may be reused across industries of that client when the type allows it and the file is still verified and inside its validity window.
- Industry-scoped documents stay on that industry unit. They are not copied to another unit automatically.
- Application-scoped documents stay on that application.
- A checklist row records required, reused, missing, or rejected. Missing items are a query, not a blank in the user interface.
- File bytes go to the `documents` disk. The database stores the relative path. Nothing in `public/` points at those files.

### Applications and statutory follow-up

- `applications` belong to one industry unit and usually to one work order. Many applications per industry.
- `application_documents` links verified documents that were assigned to that application.
- Submission, acknowledgement, notices, queries, consent, and validity are statused records. Historical applications are not deleted.
- `compliance_items` carry the next due date from the consent or the deadline table.
- Authorizations, bank guarantees, treatment plants (ETP, STP, APCS), and site visits are separate tables. They reference the industry unit and, where relevant, the application.

### Money

- `invoices` and `invoice_lines` store government and consultancy amounts separately. Lines are copied from the accepted quotation revision.
- `payments` and `receipts` record advance or full payment. A receipt does not edit the invoice amounts; it allocates against them.
- `expenses` are office costs, distinct from government fees collected for a client.
- Revenue reporting reads consultancy amounts and expenses. It does not treat government fees as income.
- Voiding a financial document sets status and writes an audit row inside the same transaction. It does not delete the row.

## Deletion

Users may be deactivated. Roles that are system roles may not be deleted. Audit rows may not be updated or deleted. Later financial and application tables follow the same rule: status changes, never silent deletion.
