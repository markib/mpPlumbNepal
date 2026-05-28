---
name: request-to-contract
description: Plumbing request to contract workflow
---

# Request-to-Contract Workflow

## Overview
This workflow upgrades PlumbNepal from simple booking acceptance to a full contract proposal flow.

### User Roles
- **Customer**: Creates a booking request.
- **Plumber**: Receives nearby open requests, sends proposals, and later starts the job.
- **Service Provider / Shop Keeper / Admin**: Can monitor or manage the contract lifecycle.

## Logic Flow
1. **Discovery**
   - Customer submits a booking request.
   - System searches `plumber_profiles` for plumbers within 10km, online, available, and verified.
   - Nearby plumbers are notified.

2. **Handshake / Deal Proposal**
   - Plumber reviews open request.
   - Plumber sends a proposal with: `base_fee`, `material_cost`, `eta_minutes`, and optional `proposal_terms`.
   - Booking status transitions to `proposed`.

3. **Contractual Acceptance**
   - Customer reviews proposals.
   - On accept:
     - Booking `workflow_status` transitions to `contracted`.
     - `accepted_by_id` is recorded.
     - `contract_terms` JSONB is created.
     - `contract_start_code` (4-digit OTP) is generated.
     - Other proposals for the booking are marked `expired`.
   - A `Job Order` is generated as structured JSON, and optionally exported as PDF later.

## Technical Requirements
### Database Schema Updates
#### `bookings`
- `workflow_status` enum: `pending`, `proposed`, `contracted`, `in_progress`, `completed`
- `accepted_by_id` foreign key -> `plumber_profiles`
- `contract_terms` JSONB
- `contract_start_code` string
- `contracted_at` timestamp

#### `booking_proposals`
- `booking_id`
- `plumber_profile_id`
- `base_fee`
- `material_cost`
- `eta_minutes`
- `proposal_terms` JSONB
- `status` enum: `pending`, `proposed`, `expired`, `accepted`, `rejected`

### Concurrency Control
- When customer accepts a proposal, update all other `booking_proposals` for that booking to `expired` in a transaction.
- Use a database transaction around the accept action and job order creation.

### Safety & Trust
- Plumbers must have `citizenship_verified = true` before they can accept proposals.
- Generate a 4-digit `contract_start_code` for customer -> plumber verification at arrival.