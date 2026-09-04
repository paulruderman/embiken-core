import type { EnumMeta, EnumName, StatusColor } from '@/types/enums';
import { usePage } from '@inertiajs/vue3';

/**
 * Semantic badge classes for generic StatusColor names from PHP enums.
 * All classes are written out in full so Tailwind can detect them at build time.
 */
const semanticBadgeClasses: Record<StatusColor, string> = {
    gray: 'bg-muted text-muted-foreground',
    blue: 'bg-primary/15 text-primary',
    green: 'bg-success/15 text-success',
    red: 'bg-destructive/15 text-destructive',
    yellow: 'bg-warning/15 text-warning',
    indigo: 'bg-primary/15 text-primary',
    sky: 'bg-primary/15 text-primary',
    purple: 'bg-primary/15 text-primary',
    amber: 'bg-warning/15 text-warning',
    orange: 'bg-warning/15 text-warning',
};

const DEFAULT_COLOR: StatusColor = 'gray';

function isStatusColor(color: string | undefined): color is StatusColor {
    return (
        color !== undefined &&
        Object.prototype.hasOwnProperty.call(semanticBadgeClasses, color)
    );
}

/**
 * Resolve badge classes for an enum value from its shared meta color.
 */
export function resolveEnumBadgeClasses(
    enumName: EnumName,
    value: string | null | undefined,
    fallbackColor: StatusColor = DEFAULT_COLOR,
): string {
    const meta = getEnumMeta(enumName, value);
    const color = isStatusColor(meta?.color) ? meta.color : fallbackColor;

    return (
        semanticBadgeClasses[color] ?? semanticBadgeClasses[DEFAULT_COLOR]
    );
}

/**
 * Look up enum metadata for a given enum name and value from Inertia shared props.
 */
export function getEnumMeta(
    enumName: EnumName,
    value: string | null | undefined,
): EnumMeta | null {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const page = usePage();
    const enums = page.props.enums;

    return enums?.[enumName]?.[String(value)] ?? null;
}

/**
 * Get the human-readable label for an enum value.
 */
export function getEnumLabel(
    enumName: EnumName,
    value: string | null | undefined,
): string {
    return getEnumMeta(enumName, value)?.label ?? formatFallbackLabel(value);
}

/**
 * Get semantic badge classes for a given enum value.
 */
export function getStatusBadgeClasses(
    enumName: EnumName,
    value: string | null | undefined,
): string {
    const meta = getEnumMeta(enumName, value);
    const color = isStatusColor(meta?.color) ? meta.color : DEFAULT_COLOR;

    return resolveEnumBadgeClasses(enumName, value, color);
}

/**
 * Get semantic badge classes directly from a color name (no enum lookup needed).
 */
export function getBadgeClassesForColor(color: StatusColor): string {
    return (
        semanticBadgeClasses[color] ?? semanticBadgeClasses[DEFAULT_COLOR]
    );
}

function formatFallbackLabel(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    return value.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}
