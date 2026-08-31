# Book resume and Provisional TTL bump

A one-shot signed MyRental URL and a cart TTL derived from `created_at` make retrying pay or finding the receipt hard. Email is out of this spec family; `GET /myrental` 404s until a later map, so Book on this browser *is* how the customer returns.

We persist `expires_at` and bump it on every mutating Action on that Provisional, including a failed Connect capture (idle on checkout still expires). `/book` with a Customer session resumes checkout or Confirmed; the signed URL token is minted once and **kept visible** on Confirmed. “Show once” and “email the link” are amended. Minutes of TTL stay a schema ticket.

## Considered Options

- Hide the URL after first Confirmed paint — rejected: the customer cannot copy it later or prove they had a link
- Derive expiry from `created_at` only — rejected: a failed pay would still burn the hold
- Email the signed URL in this family — rejected: no mail until a later map
