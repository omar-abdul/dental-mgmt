# Coverage matrix — models, pages, forms

Catalog for the 2026-09-05 surface-coverage run. Expected vs existing before this run; new tests close the Gap column.

Legend: **HP** = existing happy path · **403** = existing forbidden role · **Val** = some validation · **Gap** = added this run

## Models (49)

| Model | HTTP surface | Existing tests | Expected persist | This run |
|-------|--------------|----------------|------------------|----------|
| User | staff/profile/auth | Staff, Auth, Profile | factory | ModelCatalog |
| Room | via chairs/appointments | DomainSchema | factory | ModelCatalog |
| Chair | appointments | Appointment, DomainSchema | factory | ModelCatalog |
| Dentist | appointments/treatments | Appointment, Treatment | factory | ModelCatalog |
| WorkingHour | appointments calendar | DomainSchema seeder | factory | ModelCatalog |
| Patient | patients.* | PatientTest | factory | ModelCatalog + abuse |
| EmergencyContact | patient form nested | PatientTest blank | factory | ModelCatalog |
| PatientAllergy | patient form nested | PatientTest | factory | ModelCatalog |
| PatientCondition | patient form nested | PatientTest | factory | ModelCatalog |
| PatientMedication | patient form nested | PatientTest | factory | ModelCatalog |
| FeeItem | treatments/billing | DomainSchema seeder | factory | ModelCatalog |
| Appointment | appointments.* | AppointmentTest | factory | ModelCatalog + abuse |
| AppointmentRevision | cancel/reschedule | AppointmentTest | no factory | ModelCatalog create() |
| Treatment | treatments.* | TreatmentTest | factory | ModelCatalog + abuse |
| TreatmentProcedure | treatment form nested | TreatmentTest | factory | ModelCatalog |
| Prescription | treatment form nested | TreatmentTest | factory | ModelCatalog |
| PrescriptionItem | treatment form nested | TreatmentTest | factory | ModelCatalog |
| Invoice | billing.* | BillingTest | factory | ModelCatalog |
| InvoiceItem | generate invoice | BillingTest | factory | ModelCatalog |
| Payment | billing.payments.store | BillingTest | factory | ModelCatalog + abuse |
| MobileMoneyTransaction | ZAAD pay | BillingTest | factory | ModelCatalog |
| Receipt | billing.receipts.show | BillingTest | factory | ModelCatalog |
| InventoryItem | inventory.* | InventoryTest | factory | ModelCatalog + abuse |
| InventoryMovement | adjust/receive | InventoryTest | factory | ModelCatalog |
| InventoryBatch | inventory batches | InventoryTest | factory | ModelCatalog |
| Supplier | inventory.suppliers | (thin) | factory | ModelCatalog + HTTP |
| PurchaseOrder | inventory.purchase-orders | InventoryTest | factory | ModelCatalog + abuse |
| PurchaseOrderItem | PO form nested | InventoryTest | factory | ModelCatalog |
| ActivityLog | dashboard | DashboardTest | factory | ModelCatalog |
| AuditLog | patient show | PatientTest | factory | ModelCatalog |
| Encounter | encounters.* | EncounterTest | factory | ModelCatalog |
| SoapNote | encounters.soap | EncounterTest | factory | ModelCatalog + abuse |
| SoapNoteAmendment | encounters.amendments | EncounterTest | factory | ModelCatalog + abuse |
| OdontogramTooth | odontogram | EncounterTest | factory | ModelCatalog |
| OdontogramSurface | odontogram nested | EncounterTest | factory | ModelCatalog |
| ToothHistory | odontogram | EncounterTest | factory | ModelCatalog |
| TreatmentPlan | chart plans | EncounterTest | factory | ModelCatalog + abuse |
| TreatmentPlanItem | plan items | EncounterTest | factory | ModelCatalog + abuse |
| LabOrder | lab.* | LabOrderTest | factory | ModelCatalog + abuse |
| ImagingOrder | imaging.* | ImagingOrderTest | factory | ModelCatalog + abuse |
| ImagingResult | imaging store nested | ImagingOrderTest | factory | ModelCatalog |
| ImageFile | imaging file | ImagingOrderTest | factory | ModelCatalog |
| Expense | expenses.store | FinanceExtras | factory | ModelCatalog + abuse |
| DailyCashClosing | expenses.daily-closings | FinanceExtras | factory | ModelCatalog + abuse |
| PaymentPlan | billing.payment-plans | FinanceExtras | factory | ModelCatalog + abuse |
| Installment | plan nested | FinanceExtras | factory | ModelCatalog |
| InsuranceClaim | billing.insurance-claims | FinanceExtras | factory | ModelCatalog + abuse |
| MobileMoneyReconciliation | expenses.mm-recon | FinanceExtras | factory | ModelCatalog + abuse |
| CommunicationTemplate | notification-templates | NotificationTemplateTest | factory | ModelCatalog + abuse |

## Pages (Vue)

| Page | Route | Forms on page | Existing browser | Expected GET | This run |
|------|-------|---------------|------------------|--------------|----------|
| auth/Login | login | login | LoginTest | 200 guest | HTTP + smoke |
| auth/ForgotPassword | password.request | email | — | 200 guest | HTTP + smoke |
| auth/ResetPassword | password.reset | new password | PasswordResetTest HTTP | 200 guest | HTTP + smoke |
| auth/ConfirmPassword | password.confirm | password | PasswordConfirmationTest | auth | HTTP + smoke |
| Dashboard | dashboard | — | DashboardTest | auth all roles | HTTP + smoke |
| patients/Index | patients.index | GET search | PatientTest | view roles | HTTP + smoke |
| patients/Create | patients.create | register | via Index click | write roles | HTTP + abuse UI |
| patients/Show | patients.show | archive | PatientTest | view roles | HTTP + smoke |
| patients/Edit | patients.edit | update | — | write roles | HTTP + smoke |
| appointments/Index | appointments.index | book, edit, cancel, check-in | AppointmentTest | view roles | HTTP + smoke |
| treatments/Index | treatments.index | GET search | TreatmentTest | view roles | HTTP + smoke |
| treatments/Create | treatments.create | record Rx | TreatmentTest | write roles | HTTP + smoke |
| treatments/Show | treatments.show | complete, generate invoice | TreatmentTest | view roles | HTTP + smoke |
| chart/Index | chart.index | — | Navigation (nav only) | chart view | HTTP + smoke |
| chart/PatientChart | patients.chart | odontogram, plan, plan item | EncounterTest (sign) | chart view | HTTP + nurse read-only smoke + admin writable smoke |
| encounters/Show | encounters.show | SOAP, sign, amend | EncounterTest | chart view | HTTP + smoke |
| billing/Index | billing.index | GET search | BillingTest | billing view | HTTP + smoke |
| billing/Show | billing.show | pay, refund | BillingTest | billing view | HTTP + smoke |
| billing/Receipt | billing.receipts.show | — | BillingTest | billing view | HTTP + smoke |
| expenses/Index | expenses.index | expense, cash close, MM recon | ExpensesBrowserTest | Admin/Accountant | HTTP + smoke |
| lab/Index | lab.index | GET search | LabOrderTest | lab roles | HTTP + smoke |
| lab/Create | lab.create | create order | LabOrderTest | lab write | HTTP + smoke |
| lab/Show | lab.show | status transition | LabOrderTest | lab view | HTTP + smoke |
| imaging/Index | imaging.index | GET search | ImagingOrderTest | imaging view | HTTP + smoke |
| imaging/Create | imaging.create | create + file | ImagingOrderTest | imaging write | HTTP + smoke |
| imaging/Show | imaging.show | — | ImagingOrderTest | imaging view | HTTP + smoke |
| inventory/Index | inventory.index | search, add, adjust | InventoryTest | inventory view | HTTP + smoke |
| inventory/suppliers/Index | inventory.suppliers.index | search, add | — | inventory view | HTTP + smoke |
| inventory/purchase-orders/Index | inventory.purchase-orders.index | GET search | — | inventory view | HTTP + smoke |
| inventory/purchase-orders/Create | inventory.purchase-orders.create | PO lines | InventoryTest receive | inventory write | HTTP + smoke |
| inventory/purchase-orders/Show | inventory.purchase-orders.show | receive | InventoryTest | inventory view | HTTP + smoke |
| reports/Index | reports.index | date range | ReportsTest | all roles | HTTP + smoke |
| reports/DailyAppointments | reports.daily-appointments | date range | — | ops roles | HTTP + smoke |
| reports/PatientRegistration | reports.patient-registration | date range | — | ops roles | HTTP + smoke |
| reports/OutstandingBalances | reports.outstanding-balances | date range | — | finance | HTTP + smoke |
| reports/Payments | reports.payments | date range | ReportsTest hub | finance | HTTP + smoke |
| reports/InventoryStock | reports.inventory-stock | date range | — | ops | HTTP + smoke |
| reports/LowStock | reports.low-stock | date range | — | ops | HTTP + smoke |
| reports/TreatmentStatistics | reports.treatment-statistics | date range | ReportsTest | clinical scoped | HTTP + smoke |
| settings/Profile | profile.edit | profile, delete | ProfileUpdateTest HTTP | auth | HTTP + smoke |
| settings/Security | security.edit | password | SecurityTest HTTP | auth + confirm | HTTP + smoke (password-confirmed session) |
| settings/Appearance | appearance.edit | appearance tabs (client) | — | auth | HTTP + smoke |
| settings/Staff | staff.index / staff.create | create staff | StaffTest | Admin | HTTP + smoke |
| settings/NotificationTemplates | notification-templates.index | edit body | NotificationTemplateTest | Admin | HTTP + smoke |
| modules/Placeholder | unused (no route) | — | PlaceholderModuleTest leftover | n/a | skip (dead controller) |

## Forms / mutations (expected vs abuse)

| Form / route | Valid expected | Empty/invalid expected | Abuse expected | Existing | This run |
|--------------|----------------|------------------------|----------------|----------|----------|
| login.store | 302 dashboard | invalid password stays guest | XSS/SQLi email 422 or guest, no 500 | HP + wrong pw | AuthAbuse |
| password.email | status | invalid email | overlong | PasswordResetTest | AuthAbuse |
| password.update | reset | short/invalid token | overlong | PasswordResetTest | AuthAbuse |
| logout | guest | — | guest 302 login | AuthenticationTest | — |
| patients.store | PAT- number | required 422 | XSS stored escaped; extra patient_number ignored | HP + dup | FormAbuse |
| patients.update | persist | required 422 | same | HP | FormAbuse |
| patients.archive | archived | — | guest login; dentist 403 | HP | FormAbuse guest |
| appointments.store | APT- number | required 422 | Friday/SQL ids 422 | overlap/Friday | FormAbuse |
| appointments.update | persist | invalid ids 422 | extra keys ignored | HP | FormAbuse |
| appointments.cancel / check-in | status | wrong status 422 | guest login | HP | FormAbuse guest |
| treatments.store | Rx + procedures | required 422 | XSS diagnosis stored; extra status | HP | FormAbuse |
| treatments.complete | completed | — | 403 receptionist | HP | FormAbuse guest |
| billing.invoices.generate | INV- | incomplete 422 | 403 dentist | HP | FormAbuse guest |
| billing.payments.store | receipt | missing amount 422 | amount_cents extra ignored; negative 422 | HP | FormAbuse |
| billing.refunds.store | refund | missing 422 | 403 dentist | HP | FormAbuse |
| billing.payment-plans.store | plan | over-balance 422 | extra total_cents ignored | HP | FormAbuse |
| billing.insurance-claims.store | stub | missing 422 | XSS provider stored | HP | FormAbuse |
| expenses.store | cents | extra decimals 422 | XSS description stored | HP | FormAbuse |
| expenses.daily-closings.store | closing | duplicate 422 | negative 422 | HP | FormAbuse |
| expenses.mobile-money-reconciliations.store | recon | missing 422 | bad provider 422 | HP | FormAbuse |
| lab.store | LAB- | required 422 | XSS description | HP | FormAbuse |
| lab.transition | next status | illegal transition 422 | random status 422 | HP | FormAbuse |
| imaging.store | IMG- | required 422 | exe file 422 | HP | FormAbuse |
| inventory.store | item+batch | required 422 | negative qty 422; extra unit_cost_cents ignored | HP | FormAbuse |
| inventory.adjust | movement | expired consume 422 | non-admin 403 | HP | FormAbuse |
| inventory.suppliers.store | supplier | required name | XSS name stored | — | FormAbuse |
| inventory.purchase-orders.store | PO- | missing items 422 | extra number ignored | HP receive | FormAbuse |
| inventory.purchase-orders.receive | qty up | already received 422 | 403 dentist? | HP | FormAbuse |
| patients.odontogram.update | tooth history | invalid FDI 422 | other-patient encounter_id 422 | HP | FormAbuse |
| patients.chart.plans.store | plan | missing dentist 422 | XSS title | HP | FormAbuse |
| treatment-plans.items.store/update | item | missing desc 422 | bad acceptance 422 | HP | FormAbuse |
| encounters.soap.update | draft | overlong 422 | signed 403 | HP | FormAbuse |
| encounters.sign | signed | — | receptionist 403 | HP | FormAbuse guest |
| encounters.amendments.store | amendment | empty body 422 | XSS stored | HP | FormAbuse |
| staff.store | user | short password 422 | role=superadmin 422; extra is_admin ignored | HP | FormAbuse |
| profile.update | persist | invalid email 422 | extra role ignored | HP | FormAbuse |
| profile.destroy | deleted | wrong password | — | HP | — |
| user-password.update | hash | wrong current | short 422 | HP | AuthAbuse |
| notification-templates.update | body | empty 422 | overlong 422 | HP | FormAbuse |
| search GET params | 200 | — | `' OR 1=1` no 500 | some | FormAbuse |
| reports `from`/`to` | 200 swapped/fallback | garbage dates fallback | no 500 | HP | FormAbuse |

## Out of HTTP (covered via parent or unused)

- PlaceholderModuleController — no route
- TwoFactorAuthenticationRequest — unused (JSON 2FA off)
- Appearance tabs — client-only; GET page still tested
