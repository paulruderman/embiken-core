# Terminal POS screen map

Status: claimed
GitHub: https://github.com/paulruderman/embiken-core/issues/9
Type: prototype

## Question

What should Terminal **look and flow like** as a restaurant POS: few screens, large one-finger hit targets (other hand holding a tablet, or a fixed counter screen), staff session only (no device pairing chrome)?

This family includes walk-in, assign, pickup, return, extend (requote vs keep owed), cancel (prompt if Out), cash/other, reveal signed MyRental URL, waiver checkbox, owed/paid reminder. No CFD, no Screen, no Device pairing, no damage charge, no Connect checkout on Terminal.

Build a **UI prototype** (prototype skill, UI branch): several radically different screen-map variations, throwaway, trivial to run. Capture the chosen map as the answer. Filament stays out of the prototype.

The prototype does not implement Availability. It must make the **screen count and navigation** reactable: what is always on screen vs a drill-in.

## Assets

- Throwaway UI: `/prototype/terminal?variant=A` … `T`
- Design intent (all twenty): [terminal-pos-screen-maps.md](../prototype/terminal-pos-screen-maps.md)
