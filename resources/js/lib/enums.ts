import type { EnumMeta, EnumName, StatusColor } from '@/types/enums';
import { usePage } from '@inertiajs/vue3';

/**
 * Badge classes use the hue named by the enum's color metadata.
 * Keep each class literal so Tailwind detects it at build time.
 */
const enumBadgeClasses: Record<StatusColor, string> = {
    gray: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
    blue: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200',
    green: 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-200',
    red: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200',
    yellow: 'bg-yellow-100 text-yellow-900 dark:bg-yellow-950 dark:text-yellow-200',
    indigo: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-200',
    sky: 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-200',
    purple: 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-200',
    amber: 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
    orange: 'bg-orange-100 text-orange-900 dark:bg-orange-950 dark:text-orange-200',
};

const DEFAULT_COLOR: StatusColor = 'gray';

function isStatusColor(color: string | undefined): color is StatusColor {
    return (
        color !== undefined &&
        Object.prototype.hasOwnProperty.call(enumBadgeClasses, color)
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
        enumBadgeClasses[color] ?? enumBadgeClasses[DEFAULT_COLOR]
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
        enumBadgeClasses[color] ?? enumBadgeClasses[DEFAULT_COLOR]
    );
}

function formatFallbackLabel(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    return value.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}
