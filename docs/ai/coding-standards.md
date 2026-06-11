# Coding Standards

## Language and Style

- Follow PSR-12 strictly
- Use PHP 8+ features where they improve clarity and safety
- Enable strict typing and explicit type declarations whenever possible
- Prefer descriptive names for tenant-aware services, actions, and policies

## Database and Tenant Awareness

- Handle database connections explicitly when working with tenant models
- Never rely on implicit connection resolution for tenant-scoped data
- Be deliberate about whether a query runs against the central database or a tenant database

## Filament Conventions

- Prefer Filament v3 native Form and Table builders
- Prefer Filament Actions for state-changing flows and resource operations
- Reuse existing resource patterns before introducing custom abstractions
- Avoid custom Blade views unless the UI is highly specialized, such as advanced POS screens or timer interfaces that Filament cannot represent naturally

## Implementation Discipline

- Keep business logic in focused actions, services, or policies rather than in resources or views
- Maintain consistency with tenant scoping and shift-aware workflows
- Favor small, reviewable changes over large cross-cutting edits unless the feature requires them
