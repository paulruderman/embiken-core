---
paths:
  - 'packages/weather/**'
---

# Weather

## Weather Site is not Location
Observation points are Weather Sites (lat/lng, NWS grid), never Location. Other modules consume forecasts via WeatherReader and @weather Vue widgets (WeatherStrip, WeatherRadar), not Eloquent. Refresh, show, radar, sites, and grid resolve are Actions with weather:* commands. Do not store the full NWS payload on each period.
