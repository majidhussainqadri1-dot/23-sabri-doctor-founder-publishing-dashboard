# Source Provenance

## Governing Specification

This repository implements File 23 — **Doctor and Founder Publishing Dashboard** — from the approved project planning stream for the Sabri Social Homeopathy Platform.

The current governing implementation basis is **File 23 — Doctor and Founder Publishing Dashboard — Final Central-Plan-Harmonized Specification v3.0**, read under the current consolidated central governing plan and the project precedence rule. The earlier **Harmonized Draft 2** remains historical provenance only; any conflicting clause is superseded by the final v3.0 specification.

The final File 23 specification explicitly preserves native ownership, makes File 24 the Security/Privacy/Compliance/Resilience assurance center, and makes File 25 the owner of public profile/timeline presentation and the global visual experience/design system.

## Verified File 00 Dependency Source

The original corrective review inspected the supplied baseline package:

- Package: `00 sabri-membership-core-1.0.1.zip`
- SHA-256: `1418dff3410ebd66f6d440453f4bc4fe487828920d8fdaf8190df42844d426af`
- Declared version: `1.0.1`
- Verified canonical symbols:
  - `SMC_VERSION`
  - `smc_user_status()`
  - `smc_is_founder()`
  - `smc_is_trusted_publisher()`
- Verified account statuses include draft/pending states, `approved`, `verified`, `rejected`, `suspended`, `expired_document`, and `appeal_review`.

That package is historical compatibility evidence, not the present production-safety authority. Current release candidates consume the later File 00 canonical assertion/publishing contract and pin the exact reviewed File 00 integration head in the release workflows and sign-off documents. File 23 therefore distinguishes **contract compatibility** from **File 00 production acceptance**.

## Governing Architectural Decisions

- File 23 is a separate plugin and repository.
- File 23 is a federated private operational dashboard, not a duplicate publishing backend.
- File 21 remains the native owner of Social/News publishing and Newsroom workflows.
- File 22 remains the native owner of Composer, drafts, autosave, editing, preview, validation, and submission.
- File 24 owns cross-cutting security/privacy/compliance/resilience assurance and sanitized evidence coordination; it does not own public visual presentation.
- File 25 owns public profile/timeline rendering, public presentation, design tokens/components and visual regression.
- File 20 owns the structural application shell, route mount and global Safe Mode/Repair/Rollback surfaces.
- Native ownership is preserved through versioned adapters.
- A four-dimensional display projection replaces a destructive universal state machine.
- Capabilities and current server-side File 00 assertions, not presentation labels, control authority.
- Providers declare technical capability; File 23 governance independently records staging/production acceptance.
- Production write actions require File 23-controlled Production-Accepted status and complete operation authorization.

## Repository Creation Record

- Repository: `majidhussainqadri1-dot/23-sabri-doctor-founder-publishing-dashboard`
- Default branch: `main`
- Initial phase branch: `phase/23a-governance-contracts`
- Initial implementation date: 30 July 2026

## Evidence Status

This repository records implementation decisions and code as they are committed. A planning document, source file, ZIP, merged pull request, or green syntax check is not by itself proof of staging acceptance, production deployment, or operational completion. Exact-head automated QA, Hostinger staging acceptance, rollback/restore evidence, dependency production-safety evidence and Founder approval remain distinct release gates.