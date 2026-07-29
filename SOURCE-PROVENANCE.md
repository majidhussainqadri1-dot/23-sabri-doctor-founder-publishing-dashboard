# Source Provenance

## Governing Specification

This repository implements File 23 — **Doctor and Founder Publishing Dashboard** — from the approved project planning stream for the Sabri Social Homeopathy Platform.

The immediate implementation basis is the consolidated **Harmonized Draft 2** prepared on 30 July 2026 after the original encyclopedic plan and its corrective architectural review were reconciled.

## Governing Architectural Decisions

- File 23 is a separate plugin and repository.
- File 23 is a federated private operational dashboard, not a duplicate publishing backend.
- File 21 remains the native owner of Social/News publishing and Newsroom workflows.
- File 22 remains the native owner of Composer, drafts, autosave, editing, preview, validation, and submission.
- File 24 remains the native owner of public profile timelines and visual presentation.
- Native ownership is preserved through versioned adapters.
- A four-dimensional display projection replaces a destructive universal state machine.
- Capabilities, not presentation labels, control authority.
- Production write actions require Production-Accepted adapters.

## Repository Creation Record

- Repository: `majidhussainqadri1-dot/23-sabri-doctor-founder-publishing-dashboard`
- Default branch: `main`
- Phase branch: `phase/23a-governance-contracts`
- Initial implementation date: 30 July 2026

## Evidence Status

This repository records implementation decisions and code as they are committed. A planning document, source file, ZIP, merged pull request, or green syntax check is not by itself proof of staging acceptance, production deployment, or operational completion.
