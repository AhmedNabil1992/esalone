# Architecture Guide

## Multi-Database Tenancy

The application uses a central landlord database for platform-level data and a separate tenant database for each customer tenancy.

### Central / Landlord Models

Use the central connection for shared platform records such as:

- Tenants
- Domains
- Global subscriptions
- Billing and platform administration data

### Tenant Models

Use the tenant connection for operational business records such as:

- Devices and console sessions
- Shifts and cash register activity
- Sales, invoices, and payments
- Inventory, warehouses, stock movements, and deductions

## Migration Rules

Standard migrations belong to the central application schema when the data is shared globally. Tenant migrations must be kept separate and executed against each tenant database in the correct tenancy lifecycle.

Do not place tenant-only tables into the landlord migration flow. Do not assume a single shared database when defining new models, relations, or factories.

## Shift-Based Design

Financial transactions and time sessions should be tied to an active Shift and a specific Cash Register or Safe.

Important rules:

- A cashier should operate only within their assigned active shift
- Sales, cash movements, and session activity must resolve against the active shift context
- Closing a shift should finalize the associated financial state and operational handover

Always verify whether a new feature belongs to the central layer or the tenant layer before implementing it.
