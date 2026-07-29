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

## Current Phase

**Phase 23A — Governance and Contracts**

This phase establishes:

- responsibility and data-ownership matrices;
- capability contracts;
- versioned adapter contracts;
- four-dimensional state projection;
- dependency degradation rules;
- security and privacy boundaries;
- baseline plugin bootstrap and automated integrity checks.

No production publishing action is enabled in this phase.

## Repository Workflow

- Default branch: `main`
- Active implementation branch: `phase/23a-governance-contracts`
- Changes are promoted through reviewed pull requests.

## Package Target

`23-sabri-doctor-founder-publishing-dashboard-1.0.0.zip`

## Minimum Environment

- WordPress 6.5+
- PHP 8.0+
- Sabri Membership Core available for authenticated production use

## Status

Implementation started on 30 July 2026. Phase 23A is in progress.
