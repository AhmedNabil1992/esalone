# Testing Policy

## Testing Approach

Use PHPUnit or Pest depending on the surrounding project conventions, but keep tests explicit and behavior-focused.

## Tenant-Aware Test Requirements

Tests must:

- Create a tenant explicitly
- Switch to the tenant database connection before asserting tenant-scoped behavior
- Assert that data isolation is preserved between central and tenant databases

## Priority Test Coverage

Focus feature tests on the most critical business flows:

- Shift closures
- Session timers
- Inventory deductions

## Test Design Notes

- Prefer end-to-end feature coverage for multi-step business processes
- Include assertions for shift state, active register state, and tenant ownership where relevant
- Verify that tenant operations fail safely when the tenant context is missing or invalid
