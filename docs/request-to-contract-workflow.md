# Request-to-Contract Workflow Architecture for PlumbNepal

## Overview
This workflow upgrades PlumbNepal from simple booking acceptance to a full contract proposal flow.

### User roles
- **Customer**: creates a booking request.
- **Plumber**: receives nearby open requests, sends proposals, and later starts the job.
- **Service Provider / Shop Keeper / Admin**: can monitor or manage the contract lifecycle.

## 1. Logic Flow

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
     - booking `workflow_status` transitions to `contracted`.
     - `accepted_by_id` is recorded.
     - `contract_terms` JSONB is created.
     - `contract_start_code` (4-digit OTP) is generated.
     - Other proposals for the booking are marked `expired`.
   - A `Job Order` is generated as structured JSON, and optionally exported as PDF later.

## 2. Technical Requirements

### Database schema updates

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

### Concurrency control
- When customer accepts a proposal, update all other `booking_proposals` for that booking to `expired` in a transaction.
- Use a database transaction around the accept action and job order creation.

### Safety & Trust
- Plumbers must have `citizenship_verified = true` before they can accept proposals.
- Generate a 4-digit `contract_start_code` for customer -> plumber verification at arrival.

## 3. Geospatial Query

### Node.js / PostgreSQL (PostGIS) example

```js
const findNearbyPlumbers = async (client, longitude, latitude, radiusMeters = 10000) => {
  const point = `SRID=4326;POINT(${longitude} ${latitude})`;
  const bboxSize = 0.1; // approximate bounding box degrees; tune by density

  return client.query(
    `SELECT pp.*, u.name, u.phone,
            ST_DistanceSphere(pp.location, ST_GeogFromText($1)) AS distance_meters
       FROM plumber_profiles pp
       JOIN users u ON u.id = pp.user_id
      WHERE pp.is_available = true
        AND pp.verified = true
        AND u.citizenship_verified = true
        AND pp.location && ST_MakeEnvelope(
              $2, $3, $4, $5, 4326)
        AND ST_DWithin(pp.location, ST_GeogFromText($1), $6)
      ORDER BY distance_meters
      LIMIT 20;`,
    [
      point,
      longitude - bboxSize,
      latitude - bboxSize,
      longitude + bboxSize,
      latitude + bboxSize,
      radiusMeters,
    ]
  );
};
```

### Why this is efficient
- `&& ST_MakeEnvelope(...)` uses a fast bounding-box index scan.
- `ST_DWithin` filters down to the exact radius.
- Use `GIST` indexes on geography columns.

## 4. Real-Time Notifications

### Recommended architecture
- Use **Socket.io** for browser-based real-time notifications.
- Use **Firebase Cloud Messaging** for mobile push notifications.

### Notification flow
- Customer submits booking.
- Backend finds nearby plumbers.
- For each eligible plumber:
  - emit a socket event: `new_booking_request`
  - optionally send FCM push if plumber is offline.

### Example Socket.io event
```js
io.to(`plumber-${plumber.id}`).emit('new_booking_request', {
  booking_id: booking.id,
  customer_name: booking.user.name,
  service_type: booking.serviceType.name,
  location: {
    latitude: booking.latitude,
    longitude: booking.longitude,
    tole_name: booking.tole_name,
  },
});
```

## 5. Change Order Function

### State transition
- `in_progress` jobs can create a `change_order`
- Plumber proposes updates to `contract_terms`
- Customer must approve before the change is applied

### Data model example
```sql
CREATE TABLE contract_change_orders (
  id SERIAL PRIMARY KEY,
  booking_id INTEGER REFERENCES bookings(id) ON DELETE CASCADE,
  plumber_profile_id INTEGER REFERENCES plumber_profiles(id),
  proposed_terms JSONB NOT NULL,
  status TEXT CHECK (status IN ('pending', 'approved', 'rejected')) DEFAULT 'pending',
  created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
);
```

## 6. State Machine

| State | Action | Result |
|---|---|---|
| pending | customer submits form | nearby plumbers notified |
| proposed | plumber sends quote | customer receives `Deal Offered` |
| contracted | customer accepts quote | other proposals invalidated; job locked |
| in_progress | plumber arrives + OTP match | service clock starts |
| completed | work finished + customer confirms | booking closed |

## 7. Node.js/Express Controller Samples

### `proposeDeal`
```js
export const proposeDeal = async (req, res) => {
  const { bookingId } = req.params;
  const { base_fee, material_cost, eta_minutes, proposal_terms } = req.body;
  const plumberId = req.user.plumber_profile_id;

  const proposal = await BookingProposal.create({
    booking_id: bookingId,
    plumber_profile_id: plumberId,
    base_fee,
    material_cost,
    eta_minutes,
    proposal_terms,
    status: 'proposed',
  });

  await Booking.query().findById(bookingId).patch({ workflow_status: 'proposed' });

  // Notify customer
  emitToCustomer(booking.customer_id, 'deal_proposed', { bookingId, proposal });

  res.status(201).json({ proposal });
};
```

### `acceptDeal`
```js
export const acceptDeal = async (req, res) => {
  const { bookingId, proposalId } = req.params;

  await knex.transaction(async (trx) => {
    const proposal = await trx('booking_proposals')
      .where({ id: proposalId, booking_id: bookingId })
      .whereNotIn('status', ['expired', 'accepted'])
      .first();

    if (!proposal) {
      throw new Error('Proposal not found or already expired');
    }

    const contractStartCode = String(Math.floor(1000 + Math.random() * 9000));

    await trx('bookings')
      .where('id', bookingId)
      .update({
        workflow_status: 'contracted',
        accepted_by_id: proposal.plumber_profile_id,
        contract_terms: JSON.stringify({
          base_fee: proposal.base_fee,
          material_cost: proposal.material_cost,
          eta_minutes: proposal.eta_minutes,
          details: proposal.proposal_terms,
        }),
        contract_start_code: contractStartCode,
        contracted_at: trx.fn.now(),
      });

    await trx('booking_proposals')
      .where('booking_id', bookingId)
      .whereNot('id', proposalId)
      .update({ status: 'expired' });

    await trx('booking_proposals')
      .where('id', proposalId)
      .update({ status: 'accepted' });

    emitToPlumber(proposal.plumber_profile_id, 'deal_accepted', { bookingId, contractStartCode });
    emitToCustomer(booking.customer_id, 'contract_confirmed', { bookingId, contractStartCode });
  });

  res.status(200).json({ message: 'Deal accepted' });
};
```

## 8. Recommended Implementation Path

1. Add `workflow_status` and contract metadata to `bookings`.
2. Add `booking_proposals` table.
3. Add background notifier / socket event emitter.
4. Implement plumber open requests query with bounding-box optimization.
5. Build plumber dashboard UI with quote modal.
6. Add customer review flow and contract acceptance.
7. Add OTP verification at job start.

## 9. Notes for PlumbNepal
- Because Kathmandu/Lalitpur density is high, keep queries bounded with `ST_MakeEnvelope(...)` before `ST_DWithin(...)`.
- Use `JSONB` for `contract_terms` to allow mutable deal state and audit trails.
- Use transactional updates for accept/expire behavior.
- Use `citizenship_verified` and `verified` flags to lock out untrusted plumbers.

## 10. PDF / Job Order generation
- Construct the job order as structured JSON immediately after contract acceptance.
- Optionally generate a PDF using a service like `pdfmake` or Laravel Snappy in the backend.
- Store the JSON as part of the booking or send it to the customer.
