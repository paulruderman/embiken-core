@php
    use Embiken\Weather\Support\NominatimPlaceLabel;
@endphp

<div class="weather-site-map-picker" data-start-zoom="12" data-min-zoom="4" data-max-zoom="16">
    @include('filament-map-picker::forms.components.map-picker')
</div>

@once('weather-site-map-constraints')
    <script>
        (() => {
            const localityKeys = @json(NominatimPlaceLabel::localityKeys());
            const regionKeys = @json(NominatimPlaceLabel::regionKeys());

            const pickAddress = (address, keys) => {
                if (! address || typeof address !== 'object') {
                    return null;
                }

                for (const key of keys) {
                    const value = address[key];

                    if (typeof value === 'string' && value.trim() !== '') {
                        return value.trim();
                    }
                }

                return null;
            };

            const formatNominatimPlace = (data) => {
                const address = data?.address ?? {};
                const locality = pickAddress(address, localityKeys);
                const region = pickAddress(address, regionKeys);
                const country = typeof address.country === 'string' && address.country.trim() !== ''
                    ? address.country.trim()
                    : null;

                if (locality && region) {
                    return locality + ', ' + region;
                }

                if (locality && country) {
                    return locality + ', ' + country;
                }

                if (region && country && region !== country) {
                    return region + ', ' + country;
                }

                return locality || region || country || '';
            };

            const bindWeatherMap = (wrapper) => {
                if (!(wrapper instanceof HTMLElement) || wrapper.dataset.weatherMapBound === '1') {
                    return;
                }

                const root = wrapper.closest('.weather-site-map-picker');
                const startZoom = Number(root?.dataset.startZoom ?? 12);
                const minZoom = Number(root?.dataset.minZoom ?? 4);
                const maxZoom = Number(root?.dataset.maxZoom ?? 16);

                let attempts = 0;

                const attach = () => {
                    const alpine = window.Alpine?.$data(wrapper);
                    const map = alpine?.map;

                    if (! map || typeof map.getZoom !== 'function' || typeof map.on !== 'function') {
                        attempts += 1;

                        if (attempts < 40) {
                            window.setTimeout(attach, 50);
                        }

                        return;
                    }

                    wrapper.dataset.weatherMapBound = '1';

                    if (typeof map.setMinZoom === 'function') {
                        map.setMinZoom(minZoom);
                    }

                    if (typeof map.setMaxZoom === 'function') {
                        map.setMaxZoom(maxZoom);
                    }

                    if (typeof map.setZoom === 'function') {
                        map.setZoom(startZoom);
                    }

                    alpine.reverseGeocode = async function (lat, lng) {
                        if (this.pendingReverseTimer) {
                            clearTimeout(this.pendingReverseTimer);
                            this.pendingReverseTimer = null;
                        }

                        const now = Date.now();

                        if (now < this.nextReverseAt) {
                            this.pendingReverseTimer = setTimeout(() => this.reverseGeocode(lat, lng), this.nextReverseAt - now);

                            return;
                        }

                        this.nextReverseAt = now + this.searchThrottleMs;

                        try {
                            const lang = document.documentElement.lang || 'en';
                            const url = new URL(this.reverseUrl);
                            url.searchParams.set('format', 'json');
                            url.searchParams.set('lat', String(lat));
                            url.searchParams.set('lon', String(lng));
                            url.searchParams.set('addressdetails', '1');
                            url.searchParams.set('zoom', '10');
                            url.searchParams.set('accept-language', lang);

                            if (this.nominatimEmail) {
                                url.searchParams.set('email', this.nominatimEmail);
                            }

                            const res = await fetch(url.toString(), {
                                headers: { 'Accept': 'application/json' },
                            });
                            const data = await res.json();
                            const label = formatNominatimPlace(data);

                            if (label) {
                                this.locationName = label;
                            }
                        } catch (e) {}
                    };

                    alpine.selectSuggestion = function (suggestion) {
                        this.setLocation(suggestion.lat, suggestion.lng);
                        this.map.setView([suggestion.lat, suggestion.lng], Math.min(startZoom, maxZoom));
                        this.searchQuery = suggestion.name;
                        this.showSuggestions = false;
                        this.reverseGeocode(suggestion.lat, suggestion.lng);
                    };

                    if (alpine.currentLat !== null && alpine.currentLng !== null) {
                        alpine.nextReverseAt = 0;
                        alpine.reverseGeocode(alpine.currentLat, alpine.currentLng);
                    }
                };

                attach();
            };

            const scan = (root = document) => {
                if (! (root instanceof Element) && root !== document) {
                    return;
                }

                (root.querySelectorAll?.('.weather-site-map-picker .filament-map-picker-wrapper') ?? []).forEach(bindWeatherMap);
            };

            scan();
            document.addEventListener('DOMContentLoaded', () => scan());
            document.addEventListener('livewire:navigated', () => scan());
        })();
    </script>
@endonce
