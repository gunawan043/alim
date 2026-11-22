# Dokumentasi ALIM

Indeks ini membantu menemukan dokumen yang menjadi acuan implementasi. Dokumen bertanda **final** boleh dijadikan baseline; dokumen bertanda **proposal** masih memerlukan persetujuan.

## Integrasi ALIM Core ↔ ALIM Connect

| Dokumen | Status | Isi |
|---|---|---|
| `integration/alim-core-organization-data-audit.md` | Audit | Struktur organisasi, GTK, assignment, permission, API |
| `integration/alim-connect-api-design.md` | Proposal | Desain API organisasi read-only, snapshot, changes |
| `integration/alim-connect-engineering-review.md` | Review | Review fundamental dan risiko integrasi |
| `integration/alim-core-production-safety-plan.md` | Plan | Perlindungan data, backup, mitigasi |
| `integration/alim-core-backup-restore-runbook.md` | Runbook | Backup/restore |
| `integration/alim-core-backup-responsibility-matrix.md` | Governance | RACI backup/restore |
| `integration/alim-core-readonly-verification-queries.md` | Runbook | Query verifikasi read-only |
| `integration/alim-core-delete-guard-design.md` | Proposal | Guard delete/archive |
| `integration/alim-core-seeder-safety-design.md` | Proposal | Pengamanan seeder |
| `integration/alim-core-permission-and-placement-lifecycle-design.md` | Proposal | Permission snapshot + lifecycle GTK |
| `integration/alim-core-gtk-work-unit-final-audit.md` | Audit | Audit final pembaca/penulis `gtk_work_unit` |
| `integration/alim-core-governance-decision-log.md` | Decision log | Keputusan pemilik sistem |
| `integration/alim-connect-approval-command-api.md` | **Final** | Kontrak Approval Command API |
| `integration/alim-core-whatsapp-connect-embed-design.md` | Proposal | Desain SSO + iframe WhatsApp Connect |
| `integration/alim-core-whatsapp-connect-implementation.md` | **Implementation report** | Hasil PDCA implementasi Core |

## Arsitektur, otorisasi, dan operasional

- `authorization-domain-model.md`
- `authorization-architecture-validation.md`
- `authorization-engineering-constitution.md`
- `authorization-governance-operational-architecture.md`
- `authorization-implementation-transition-plan.md`
- `org-driven-authorization-audit.md`
- `org-driven-authorization-architecture-revision.md`
- `security/P0-HARDENING.md`
- `security-rotation-guide.md`
- `adr/ADR-016-permission-snapshot-schema.md`
- `adr/ADR-017-write-time-authorization-guard.md`
- `adr/ADR-018-mobile-authentication-strategy.md`

## Akademik dan modul

- `RANGKUMAN-SISTEM-ALIM.md`
- `CHANGELOG-2026-10.md`
- `features/`
- `asrama/`
- `testing/`

## Catatan

- Root `README.md` adalah panduan instalasi aplikasi.
- Dokumen di `integration/` adalah dokumen lintas aplikasi; jangan mengubah kontrak bertanda **Final** tanpa koordinasi dengan ALIM Connect.
