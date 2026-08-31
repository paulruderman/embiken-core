# Terminal POS prototype — twenty screen maps

Throwaway UI: `/prototype/terminal?variant=A` … `T`. Staff session, one-finger POS. Not Availability, not Filament.

The question is always: **what is always on screen vs what is a drill-in.** Mix is allowed. Shop-operable v1 still wants **one** Terminal home; this set is for choosing and stealing.

A ticket is every `bike_reservation` on the Reservation. Floor / pickup / return / assign / swap target a **line**. The fixture includes a two-line party (Nguyen) and an unassigned second line on Maya.

---

## A — Floor board

**Description:** Every bike as a situation tile (out / staged / prepping / back / home). Walk-in on the chrome. Ticket is a bottom sheet; assign is an overlay. The board never leaves.

**Motivation:** Desk staff already think in bids. Pickup and return are “where is that bike.” Occupancy is the job.

**Spirit:** Kitchen expo. Dark, dense, physical assets.

---

## B — Ticket queue

**Description:** Today’s reservations as a stack of chits. Full-screen drill-ins for ticket, floor, extend, cancel.

**Motivation:** The unit of work is a customer, not a bike. Named walk-ins and “Maya’s ticket.”

**Spirit:** Toast / Square ticket rail. Paper, Back, large type.

---

## C — Verb deck

**Description:** Function keys always on; ticket list and bikes are columns. No page stack.

**Motivation:** Trained speed. Verbs (Pickup, Return, Cash) are the interface; occupancy is a sidebar.

**Spirit:** Aloha / Micros. Green keys, black well, memorized.

---

## D — Day timeline

**Description:** The shop day as a time ribbon; tickets are bars. Bottom drawer for the tap.

**Motivation:** Time is the scarce resource. Overlap, holes, and “can we extend” are geometry.

**Spirit:** A paper booking diary on its side. Cyan bars.

---

## E — Split panes

**Description:** Bikes always left, ticket always right. Home bike on the left assigns. No overlay.

**Motivation:** Stop choosing between floor and ticket. Landscape counter glass.

**Spirit:** Parts counter. Neutral split, no drama.

---

## F — Due well

**Description:** Urgency buckets (late / out / next / back). Floor is a tab.

**Motivation:** The next physical act, especially afternoon returns and put-away.

**Spirit:** Airport “now serving.” Yellow on black.

---

## G — Bike lanes

**Description:** Time on X, **each bid a row**, reservations as bars on that bike.

**Motivation:** D’s diary fused with occupancy. See that B1 is busy 10–16 and C2 is a hole. Swap/extend become “does this bar fit.”

**Spirit:** Hotel rack / resource Gantt. Indigo lanes.

---

## H — Now playhead

**Description:** Three columns — Behind / Live / Later — with NOW as a fat spine. Hours are not equal.

**Motivation:** The counter lives in the current hour. A full-day Gantt is noise during the rush.

**Spirit:** Mixing desk / live news. Red NOW.

---

## I — Clock face

**Description:** Twelve hour marks on a ring; the well is who **starts** in that hour.

**Motivation:** Staff already glance at a wall clock. Tap “2” like a watch.

**Spirit:** Analog, slightly playful. Warm stone and amber.

---

## J — Dual dock

**Description:** Two bays, OUT and IN. No calendar. Outbound = not yet gone; inbound = rented_out or back.

**Motivation:** The shop as doors. Pickup is an OUT tap; return is an IN tap.

**Spirit:** Loading dock / ferry. Two big mouths.

---

## K — Scrubber freeze-frame

**Description:** A row of hour keys. The floor shows who is busy **at that instant**, not “now.” Tap a lit tile to open that ticket.

**Motivation:** D shows duration; A shows now. K is time-travel occupancy — “what does 3pm look like” without reading bars.

**Spirit:** VCR / DAW scrubber. Fuchsia freeze-frame.

---

## L — Week ribbon

**Description:** Seven day rows. Today is fat with hour bars; other days are quiet stubs.

**Motivation:** Multi-day holds and “Saturday is slammed” live above a single afternoon. Book is the customer calendar; Terminal still needs a week glance so staff don’t walk into a wall.

**Spirit:** Wall planner. Emerald week.

---

## M — Hour filmstrip

**Description:** Horizontal reel. Each hour is a column of bid chips (busy gold / free mute). Thumb along the day.

**Motivation:** Gantt rows don’t fit a phone-width tablet. Filmstrip is G’s idea (bike × hour) turned 90° for one-finger scroll.

**Spirit:** Contact sheet / cinema. Yellow frames on black.

---

## N — Waterfall

**Description:** Tickets sorted by start. Indent is the first free overlap *lane*: a finished ticket frees its column so the next one wraps back left. Not a chain through ended reservations, and not inline wrapping.

**Motivation:** “Are we double-booked on class” should be visible as a staircase, not inferred from bars.

**Spirit:** Engineering Gantt / cascade. Orange, indent as meaning.

---

## O — Orbits

**Description:** Three rings — morning / midday / afternoon — with first-name beads. Start-hour as orbit, not a grid.

**Motivation:** Coarse time (“afternoon pile”) without 11 columns. A middle ground between clock and list.

**Spirit:** Orrery / gravity well. Sky beads on rings.

---

## P — Model staves

**Description:** Each BikeModel is a musical staff of time. Bars are parties on that class, not a specific bid.

**Motivation:** Book quotes remaining by variant/class. Staff need “Turbos at 2pm” as a line of music, then drill into lines.

**Spirit:** Sheet music. Rose staves.

---

## Q — Load histogram

**Description:** Each hour is a tower of how many bikes are busy. Tap a tower, then a bid in that hour.

**Motivation:** Capacity, not identity. “2pm is full” before “who is Maya.” Complements quoteOccupancy remaining.

**Spirit:** Mixer meters / EQ. Violet bars.

---

## R — Pigeonholes

**Description:** One cubby per start hour; tickets stuffed in like mail. Duration is not drawn.

**Motivation:** Starts are appointments. Staff say “the 10 o’clock,” not “the six-hour bar.”

**Spirit:** Hotel mail rack. Amber cubbies.

---

## S — Portraits

**Description:** One ticket fills the glass as a giant card. Prev/next. Lines listed. No board, no ribbon.

**Motivation:** Sometimes there is exactly one party at the counter. Everything else is a tap away.

**Spirit:** Tarot / boarding pass. White card, black theater.

---

## T — Radio tape

**Description:** A scrolling transcript of shop events; verbs are telegraphic (OUT, IN, EXT). Tickets are a chip row.

**Motivation:** Reality is a sequence of acts, not a map. Training, disputes, “what just happened,” and a future Screen-adjacent log without PII on Screen.

**Spirit:** Syslog / shortwave. Lime on black, monospace.

---

## Timeline family (steal from these first if D felt right)

| Letter | Time is… |
|---|---|
| D | duration bars for parties |
| G | duration bars per bid |
| H | now vs behind vs later |
| I | start hour on a watch |
| K | freeze-frame occupancy |
| L | week of days |
| M | bike × hour film |
| N | overlap depth |
| O | coarse start rings |
| P | class occupancy staves |
| Q | busy-count per hour |
| R | start-hour cubbies |

Non-time homes: A floor, B queue, C verbs, E split, F due, J docks, S portraits, T tape.

---

## Out of scope in every variant

CFD, Screen, Device pairing, Connect checkout, damage fees, Filament chrome. Extend here only stubs `ends` / `owed`.
