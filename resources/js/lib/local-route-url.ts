/**
 * Keep a Wayfinder route on the current origin, including a development port.
 * Domain routes encode only the hostname, so their generated URL omits ports.
 */
export function localRouteUrl(route: string | { url: string }): string {
    const url = new URL(typeof route === 'string' ? route : route.url, 'http://wayfinder.local');

    return `${url.pathname}${url.search}${url.hash}`;
}
