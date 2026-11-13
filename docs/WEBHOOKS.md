# Tablesit webhook rollout

Receiver: `https://booking.danshi.com.tw/tablesit/webhook`.

Set `TABLESIT_WEBHOOK_SECRET` to the entire vendor-generated signing secret, including `whsec_`. Without it the route returns 503. Do not put the secret in source control or chat. Existing polling stays every minute until delivery and reconciliation are verified.

The inbox verifies the HMAC over timestamp + dot + raw bytes, enforces 300-second tolerance, and deduplicates by event ID. Test events are recorded but never create bookings. Unknown organizations and unsupported types are ignored. Only routing metadata is retained; no event customer payload is stored.

The scheduler runs `tablesit:process-webhooks` every minute. It reads current API state to handle out-of-order events, retries API failures up to 12 times, and exposes statuses in Sync Center. Vendor-deleted bookings are marked cancelled locally, retaining history. Failed events remain visible for intervention. Do not reduce polling until provider test delivery, live Demo update, and duplicate delivery checks pass.

Partner-level setup requires the existing Partner Portal access. Demo-only setup can use Tablesit Settings > Developer > Webhooks if that menu is available. Do not configure both levels for the same data unnecessarily. Signing secrets remain server-only.
