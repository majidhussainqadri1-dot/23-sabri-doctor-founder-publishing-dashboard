# Sabri Doctor and Founder Publishing Dashboard

File 23 of the **Sabri Social Homeopathy Platform**.

## Purpose

This plugin provides one private, role-aware, federated publishing command center for the Founder and verified doctors. It unifies operational views of content, reviews, schedules, interactions, analytics summaries, cross-module collections, and system health while preserving each native module as the source of truth.

## Governing Principle

> One Federated Publishing Command Center — Role-Specific Workspaces — Native Ownership Preserved — Every Action Executed through an Authorized Versioned Adapter.

## Architectural Boundaries

- File 00 owns identity, verification, roles, and capabilities.
- File 20 owns the global application shell and global Safe Mode.
- File 21 owns social/news publications, Newsroom review, sources, comments, corrections, retractions, and public feed behavior.
- File 22 owns creation, draft persistence, autosave, editing, preview, validation, submission, and native adapter orchestration.
- File 23 owns dashboard preferences, saved views, cross-module tasks, cross-module collections/campaign metadata, bounded aggregate caches, adapter health, and dashboard-specific audit events.
- File 24 owns public profile timelines and visual presentation.

File 23 must not duplicate publication bodies, native drafts, review decisions, native schedules, source records, media binaries, comments, corrections, retractions, raw analytics events, profiles, identities, or notification delivery records.

## Corrective Architecture

The Phase 23A review found that the original bootstrap trusted provider-declared production acceptance and a caller-supplied environment flag. Corrective build `0.1.1` closes those paths:

- Adapter Contract: `2.0.0`
- providers declare technical capability only;
- File 23 governance independently controls staging/production acceptance;
- WordPress resolves the environment server-side;
- File 00 availability and approved/verified status are mandatory;
- all dashboard mutations pass through the guarded operation broker;
- provider exceptions are isolated;
- native object re-read is required before confirmed success;
- executable contract tests run on PHP 8.0–8.3.

## Current Phase

**Phase 23A — Governance, Contracts, Corrective Audit, and Bootstrap**

This phase establishes:

- responsibility and data-ownership matrices;
- fail-closed capability and Membership Core contracts;
- versioned provider adapter contracts;
- independent adapter acceptance gates;
- four-dimensional state projection;
- guarded native operation routing;
- security and privacy boundaries;
- executable contract and architecture tests;
- baseline plugin bootstrap and automated integrity checks.

No production publishing action is enabled in this phase.

## Repository Workflow

- Default branch: `main`
- Active implementation branch: `phase/23a-governance-contracts`
- Draft pull request: `#1`
- Changes are promoted only through completed review and accepted pull requests.
- **No merge is permitted before review completion, defect correction, exact-head test rerun, and acceptance.**

## Package Target

`23-sabri-doctor-founder-publishing-dashboard-1.0.0.zip`

## Current Corrective Version

- Plugin: `0.1.1`
- Adapter Contract: `2.0.0`

## Minimum Environment

- WordPress 6.5+
- PHP 8.0+
- Sabri Membership Core 1.0.1 or a formally accepted compatible version for privileged use

## Status

Implementation started on 30 July 2026. Phase 23A review is complete and corrective commits are present, but PR #1 remains Draft and unmerged pending exact corrected-head CI and Founder acceptance.
