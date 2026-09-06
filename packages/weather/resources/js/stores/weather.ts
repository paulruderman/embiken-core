import { defineStore } from 'pinia';

export type WeatherPeriod = {
    starts_at: string;
    ends_at: string;
    temperature_f: number | null;
    temperature_c: number | null;
    short_forecast: string | null;
    detailed_forecast: string | null;
    wind_speed: string | null;
    wind_direction: string | null;
    relative_humidity: number | null;
    icon: string | null;
    is_daytime: boolean | null;
};

export type WeatherSiteSnapshot = {
    id: number;
    code: string;
    label: string;
    latitude: number;
    longitude: number;
    nws_office: string | null;
    nws_grid_x: number | null;
    nws_grid_y: number | null;
    current: WeatherPeriod | null;
};

export type WeatherSnapshot = {
    sites: WeatherSiteSnapshot[];
    settings?: {
        radar_source: string;
        stale_forecast_retention_minutes: number;
    };
};

export const useWeatherStore = defineStore('embiken-weather', {
    state: () => ({
        sites: [] as WeatherSiteSnapshot[],
        settings: {
            radar_source: 'rainviewer',
            stale_forecast_retention_minutes: 120,
        },
    }),
    actions: {
        hydrate(payload: WeatherSnapshot): void {
            this.sites = payload.sites ?? [];
            if (payload.settings) {
                this.settings = payload.settings;
            }
        },
        patch(payload: WeatherSnapshot): void {
            this.hydrate(payload);
        },
    },
});
