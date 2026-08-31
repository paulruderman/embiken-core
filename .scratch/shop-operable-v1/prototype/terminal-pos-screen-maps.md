# Terminal POS prototype — design intent by variant

Throwaway screen maps at `/prototype/terminal?variant=A` … `J`. Staff session, one-finger POS. Not Availability, not Filament, not production chrome.

The question for every variant is the same: **what is always on screen vs what is a drill-in.** The winning map can steal pieces; it does not have to be one letter.

---

## A — Floor board

**Always on:** every bike, grouped by situation (out / staged / prepping / back / home), plus Walk-in.  
**Drill-in:** ticket as a bottom sheet; assign as an overlay. The board never leaves.

**Intent:** Occupancy is the job. Staff think in bids (“where is B1?”). Pickup and return are seeing a tile change color.

**Spirit:** Kitchen expo board. Dense, dark, physical assets.

**Use when:** a small-to-medium fleet, counter staff who already know the bikes, and the pain is “who is out / who is home,” not “who is at 2pm.” Weak if the day is a pile of overlapping intervals and you cannot see collisions.

---

## B — Ticket queue

**Always on:** today’s reservations as a stack of chits.  
**Drill-in:** full screens (ticket, floor, extend, cancel). The board is a destination, not a home.

**Intent:** The unit of work is a customer, not a bike. Restaurant ticket rail.

**Spirit:** Toast / Square tickets. Paper, large type, Back.

**Use when:** walk-in and named bookings dominate, staff call people not bids, and you accept extra taps to see the floor. Weak for “is this hour sellable” — you cannot see overlap.

---

## C — Verb deck

**Always on:** function keys, ticket list, bike column. No page stack.  
**Drill-in:** none. Context and a pane change.

**Intent:** Trained speed. The interface is the verbs (Pickup, Return, Cash). Occupancy is a sidebar.

**Spirit:** Aloha / Micros. Green keys, black well, memorized.

**Use when:** the same three people work the counter all summer and will learn the deck. Weak for a substitute Counter on a Saturday who needs the floor to explain itself.

---

## D — Day timeline

**Always on:** the shop day as a time ribbon; tickets are bars.  
**Drill-in:** bottom drawer.

**Intent:** Time is the scarce resource. Overlap, gaps, and “can we extend” are visible as geometry.

**Spirit:** A paper booking diary laid on its side. Cyan bars on a dark day.

**Use when:** you need to see collisions and idle holes without opening tickets. Walk-in is “is there a hole,” not “is there a bike.” Weak as the only home if staff still ask “where is B1?” — bids are tiny on the bar. **Keep this idea** even if another variant wins the chrome.

---

## E — Split panes

**Always on:** bikes left and ticket right. Neither leaves.  
**Drill-in:** none. Home bikes on the left assign into the open ticket.

**Intent:** Stop choosing between floor and ticket. Two-handed metaphor, one-finger use: tap left then tap a verb on the right.

**Spirit:** Parts counter. Neutral, split, no drama.

**Use when:** tablets in landscape, or a wide counter monitor. Weak in portrait: the ticket pane starves. Weak for a packed day of overlap — left is situation, not time.

---

## F — Due well

**Always on:** urgency buckets (late / out / next / back) plus Walk-in.  
**Drill-in:** Floor is a tab.

**Intent:** The next physical act. Not the catalog, not the calendar.

**Spirit:** Airport boards / “now serving.” Yellow on black, shouty.

**Use when:** late returns and put-away are the afternoon failure mode. Weak in the morning when the question is still “what does 11:00 look like?”

---

## G — Bike lanes (Gantt)

**Always on:** time on X, **each bid a row**, reservations as bars on that bike.  
**Drill-in:** drawer, same as D.

**Intent:** D’s timeline fused with A’s occupancy. This is the hotel rack: you see that B1 is busy 10–16 and C2 is a gap. Swap and extend become “does this bar fit.”

**Spirit:** Resource schedule. Indigo lanes, one row per asset.

**Use when:** you liked D but still need to know *which* bike is occupied, not only that *someone* is. This is the strongest “we will need something like the timeline” candidate for a shop that assigns real bikes (not an anonymous pool). Weak if the fleet is huge (too many rows) unless you filter by model.

---

## H — Now playhead

**Always on:** three columns — Behind / Live / Later — with NOW as a fat spine. Hours are not equal.  
**Drill-in:** drawer.

**Intent:** D’s timeline, but the present is magnified. Overdue returns scream on the left; the next start is on the right.

**Spirit:** Mixing-desk / live news. Red NOW, not a spreadsheet.

**Use when:** the counter lives in the current hour (pickup/return waves) and a full-day Gantt is noise. Weak for planning 16:00 at 10:00 — Later is a list, not geometry.

---

## I — Clock face

**Always on:** twelve hour marks on a ring; the well is “who starts in this hour.”  
**Drill-in:** drawer.

**Intent:** Time as a watch, not a grid. Staff tap “2” the way they glance at a wall clock.

**Spirit:** Analog. Warm stone and amber. Slightly playful on purpose — to test whether a non-table timeline still reads.

**Use when:** the day is a handful of starts, and you want a memorable home for a tablet held in one hand (thumb on the ring). Weak for overlap duration (a 10–16 bar is not on the ring) and weak for accessibility if the ring is too precious. Steal the *hour filter*, not necessarily the circle.

---

## J — Dual dock

**Always on:** two bays, OUT and IN. No calendar.  
**Drill-in:** drawer.

**Intent:** The shop as doors. Outbound = not yet gone (prepping/staged/home on a ticket). Inbound = rented_out or back. Pickup is an OUT tap; return is an IN tap.

**Spirit:** Loading dock / ferry. Two big mouths.

**Use when:** the building has a literal out door and in door, or staff already talk that way. Weak for “can we take a 2pm” — time is only a number on the card.

---

## How to mix (especially timeline)

If the diary (D) felt right:

- **D + G:** keep the ribbon, add bid rows (G *is* that mix).
- **D + H:** full-day geometry for planning, NOW columns for the rush.
- **D + A:** timeline home, situation tiles as a strip or overlay — “where” without abandoning “when.”
- **D + J:** timeline for the office iPad; docks for the door tablet. Same Actions, two homes, later if we split devices (out of this family’s Device slice).

Do not ship two competing homes in shop-operable v1 without a reason. One Terminal tree; Screen is a later board and must not show PII or money.

---

## Out of scope in every variant

CFD, Screen, Device pairing, Connect checkout, damage fees, Filament chrome, Availability math (extend here only bumps a stub `ends` / `owed`).
