# Security Policy

## Priority One: Tenant Data Isolation

Tenant data isolation is the highest security requirement in this application.

Rules:

- Queries must never leak data across tenant databases
- Central and tenant records must not be mixed without an explicit and justified boundary
- Tenant-aware features must always resolve against the current tenant context

## Role Model

Roles must be explicit and scoped to the SaaS operating model.

Common roles include:

- Super Admin
- Tenant Owner
- Cashier

## Shift and Register Restrictions

- Cashiers must be restricted to their active shift
- Cashiers must only access their assigned cash register or safe
- Financial actions must be authorized against the current shift state

## Authorization Requirements

- Enforce strict Laravel Policies on all Filament Resources
- Prefer policy checks over UI-only restrictions
- Validate access again at the action and persistence layer, not only in the interface

Security-sensitive changes should always be reviewed with tenant isolation and role scope in mind before merging.
