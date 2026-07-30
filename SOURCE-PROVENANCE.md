# Source Provenance

## Governing Specification

This repository implements File 23 — **Doctor and Founder Publishing Dashboard** — from the approved project planning stream for the Sabri Social Homeopathy Platform.

The immediate implementation basis is the consolidated **Harmonized Draft 2** prepared on 30 July 2026 after the original encyclopedic plan and its corrective architectural review were reconciled.

## Verified File 00 Dependency Source

The corrective review inspected the supplied original package:

- Package: `00 sabri-membership-core-1.0.1.zip`
- SHA-256: `1418dff3410ebd66f6d440453f4bc4fe487828920d8fdaf8190df42844d426af`
- Declared version: `1.0.1`
- Verified canonical symbols:
  - `SMC_VERSION`
  - `smc_user_status()`
  - `smc_is_founder()`
  - `smc_is_trusted_publisher()`
- Verified account statuses include draft/pending states, `approved`, `verified`, `rejected`, `suspended`, `expired_document`, and `appeal_review`.

This source verification caused two corrections: File 23 now enforces an explicit File 00 version range and preserves the plan-required restricted read-only/status/appeal workspace for non-approved doctors without granting mutation authority.

## Governing Architectural Decisions

- File 23 is a separate plugin and repository.
- File 23 is a federated private operational dashboard, not a duplicate publishing backend.
- File 21 remains the native owner of Social/News publishing and Newsroom workflows.
- File 22 remains the native owner of Composer, drafts, autosave, editing, preview, validation, and submission.
- File 24 remains the native owner of public profile timelines and visual presentation.
- Native ownership is preserved through versioned adapters.
- A four-dimensional display projection replaces a destructive universal state machine.
- Capabilities, not presentation labels, control authority.
- Providers declare technical capability; File 23 governance independently records staging/production acceptance.
- Production write actions require File 23-controlled Production-Accepted status and complete operation authorization.

## Repository Creation Record

- Repository: `majidhussainqadri1-dot/23-sabri-doctor-founder-publishing-dashboard`
- Default branch: `main`
- Phase branch: `phase/23a-governance-contracts`
- Initial implementation date: 30 July 2026

## Evidence Status

This repository records implementation decisions and code as they are committed. A planning document, source file, ZIP, merged pull request, or green syntax check is not by itself proof of staging acceptance, production deployment, or operational completion.
