# Action inventory for Book, Terminal, and ticks

Status: open
Type: grilling
Blocked by: 08

## Question

Which **named Actions** exist in this family, and where does one Action stop and another start?

Already named: CancelAction, SwapAssetAction, ExtendAction, RefundAction, PutAwayAction, ReturnAction, plus Availability `allocate` / `quoteOccupancy` / `swapAsset` / `release`.

Implied and unnamed: CreateTenant (provision), Book reserve, Book pay/confirm, Terminal walk-in, assign, set prepping/staged, set Confirmed, set owed, add/remove lines, Pickup, park bike, blocking service open/clear, InviteStaff / SetPassword, mint/reveal MyRental link, AcceptWaiver, RecordCashPayment / RecordOtherPayment, ExpireProvisional, NoShow, HealStaleOut, stage projector, Express refresh Account Link.

Lock:

- Book: one Action vs split quote / reserve / pay
- Terminal: which desk verbs are distinct Actions vs flags on one
- Ticks: one scheduled Action vs several
- Filament: buttons only; never a second `handle()` (pickup/return/put-away/checkout stay off Filament)

Blocked by [Terminal POS screen map](08-terminal-pos-screen-map.md) so Terminal verbs match the chosen screens. Use lorisleiva Actions as recorded. Do not invent Http\Controllers for the same use cases.
