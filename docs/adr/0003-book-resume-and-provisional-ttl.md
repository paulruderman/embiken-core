# Book resume and Provisional TTL bump

A persistent random reservation token in a signed MyRental URL makes the URL reusable while retaining revocation. Its signature expires at `ends_at` plus three days. Reservation notices include that link, and a customer can re-request the same link by entering the reservation email on `/myrental`; the endpoint does not reveal whether it found a match. Managers can revoke all of a Customer's reservation links from the Customer record.

We persist `expires_at` and bump it on every mutating Action on that Provisional, including a failed Connect capture (idle on checkout still expires). `/book` with a Customer session resumes checkout or Confirmed; the signed URL token is minted once and **kept visible** on Confirmed. Re-requesting access reuses the same URL. Minutes of TTL stay a schema ticket.

## Considered Options

- Hide the URL after first Confirmed paint — rejected: the customer cannot copy it later or prove they had a link
- Derive expiry from `created_at` only — rejected: a failed pay would still burn the hold
- Re-mint a new token on each request — rejected: saved links would stop working without a security benefit; staff revocation remains available
