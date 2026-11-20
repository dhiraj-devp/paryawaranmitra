# Business workflows

The enquiry-to-consent process below is the office path. These parts are available now:

- Staff record an enquiry with or without an existing client. The screen does not create a client. An industry unit can be linked only when it belongs to the selected client.
- `App\Services\Catalog\ServiceEligibility` refuses an inactive service and a category the service does not cover. Saving an enquiry checks that pair when both the service and the industry unit are present. Conversion checks it again.
- Converting an enquiry creates one work order in the same transaction, marks the enquiry converted, and writes the audit rows. A second conversion is rejected. The database also allows only one work order per enquiry.
- Fee schedules store separate government and consultancy amounts. `App\Services\Fees\ApplicableFee` returns the active schedule in force on a date, or nothing when the service is inactive or no schedule covers that date.
- A quotation belongs to one work order. The first revision copies the fee schedule in force on the issue date. Staff may change those copied amounts. The fee schedule itself does not change. If no schedule applies, the quotation is refused.
- Saving a quotation change inserts a new revision. Older revisions stay unchanged. Accepting a quotation records the accepted revision. A later revision does not replace that record unless staff accept the later revision explicitly.

Checklists, invoices, and applications are not built. A work order does not create those records. Sending a quotation by WhatsApp or email is not built.

## Enquiry to consent

1. An enquiry arrives by phone, visit, or message.
2. Staff create an enquiry and choose the service and the industry it applies to. A service that is not eligible for that industry cannot be selected.
3. The office prepares a quotation from the work order. The applicable fee schedule is copied onto revision 1. Staff may change the copied amounts. If no schedule covers the issue date, the quotation is not created.
4. A document checklist will be prepared for the service and the industry. That checklist is not built yet.
5. Existing documents are offered only when all of the following are true: the scope allows reuse, the file is verified, and the validity window includes the date of use.
6. A client-level document may be reused for another industry of the same client when the document type says so.
7. An industry-level document is not offered for a different industry unit.
8. An application-level document stays on its application.
9. The quotation and the missing-document list are sent to the customer. Sending is a recorded communication, not a side effect without a row. WhatsApp and email sending are not built yet.
10. Staff collect the pending documents. The checklist shows what is still missing.
11. Reminders go out through the automation rules once WhatsApp and email providers exist. The reminder schedule is data.
12. Staff verify each document. Unverified files cannot be assigned to an application.
13. An invoice is generated from the accepted quotation revision. Amounts are copied onto the invoice. Invoices are not built yet.
14. Advance or full payment is recorded as a receipt allocated to that invoice.
15. The application is prepared for the industry unit.
16. Verified documents are assigned to the application. Assignment does not move an industry document onto another unit.
17. A reviewer checks the application.
18. Submission and the authority acknowledgement are recorded.
19. Notices and queries are tracked against the application until closed.
20. The final consent and its validity dates are recorded.
21. Compliance and renewal items are created from that consent and from the deadline table.

An enquiry may be converted into one work order. The work order is the parent for the quotation, the checklist, the invoice, and the application. One client has many industry units. One industry unit has many applications.

## Quotation revisions

Saving an edit creates a new revision. Older revisions stay readable and downloadable. A revision stores its own line amounts, including separate government and consultancy totals. Changing a fee schedule later does not rewrite an issued revision. The customer-facing number stays on the quotation, and the revision number increases. Two revisions of the same quotation cannot share a number.

A quotation starts as Draft. Staff can mark it Sent, and from Sent they can mark it Accepted, Rejected, Expired, or Cancelled. Accepted can be Cancelled. Rejected, Expired, and Cancelled do not move again. Rejected, Expired, and Cancelled need a reason.

Accepting records the current revision as the accepted revision. Preparing another revision after acceptance requires a reason and a confirmation. That new revision returns the quotation to Draft and leaves the accepted revision id unchanged. Accepting the later revision, after it is sent, is a separate status change and is the only way the accepted revision id moves. The earlier revision row is not edited.

There is no tax percentage. `config/tax.php` is disabled, and every quotation stores tax as zero and says that tax settings are pending. A company GSTIN is printed only when `config/company.php` has one. The application does not invent one.

Quotation PDFs are written on the private `quotations` disk and downloaded only after `quotations.view` allows it. Internal notes stay on the screen and are left off the PDF.

## Money

- Government fees collected for an application are liabilities or pass-through amounts, not consultancy income.
- Consultancy fees are revenue.
- Expenses are office costs.
- Reports that say profit use consultancy revenue and expenses.
- A mistake is corrected by a reversing entry or a void status. The original row remains.
- The posting action and its audit row commit or roll back together.

## Documents

Before reuse, the screen shows verification state and validity. A missing required document stays visible after other files have arrived. Rejected documents keep their reason.

Private files are stored on the `documents` disk. The browser receives a file only after an authorized download action allows it.

## What must not happen

- A hardcoded fee or a hardcoded statutory due date.
- A service offered for an industry it does not cover.
- An edit to a quotation that updates the master fee.
- A document copied from one industry unit to another without an explicit, scope-checked assignment.
- A financial row or a historical application removed to fix a data-entry error.
- A customer login created inside the `users` table.
