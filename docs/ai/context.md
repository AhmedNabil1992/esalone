# Project Context

This repository is a TALL stack Laravel application built around Filament for the administrative UI and Livewire for reactive workflows.

Critical architecture constraint: this is a Multi-Tenant SaaS application using a Multi-Database setup. Each tenant has an isolated database, and any feature work must respect tenant boundaries by default.

Domain focus: PlayStation Cafe Management System.

Core modules:

- Session / Time Management for consoles and devices
- Multi-Safe POS and invoicing flows
- Advanced inventory with multi-warehouse support
- Shift management for cashier handovers and operational control

When working in this codebase, assume tenant context matters for nearly every business operation. Central application data and tenant-scoped data are intentionally separated.
