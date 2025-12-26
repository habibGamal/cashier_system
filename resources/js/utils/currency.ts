import { usePage } from '@inertiajs/react';

export interface CurrencySettings {
    symbol: string;
    code: string;
    name: string;
    decimals: number;
}

/**
 * Get currency settings from Inertia shared props
 */
export const useCurrency = (): CurrencySettings => {
    const { currency } = usePage().props as any;
    return currency || {
        symbol: 'ج.م',
        code: 'EGP',
        name: 'جنيه',
        decimals: 2,
    };
};

/**
 * Get currency symbol
 */
export const getCurrencySymbol = (): string => {
    const { currency } = usePage().props as any;
    return currency?.symbol || 'ج.م';
};

/**
 * Get currency code
 */
export const getCurrencyCode = (): string => {
    const { currency } = usePage().props as any;
    return currency?.code || 'EGP';
};

/**
 * Get currency name (Arabic)
 */
export const getCurrencyName = (): string => {
    const { currency } = usePage().props as any;
    return currency?.name || 'جنيه';
};

/**
 * Get currency decimal places
 */
export const getCurrencyDecimals = (): number => {
    const { currency } = usePage().props as any;
    return currency?.decimals || 2;
};

/**
 * Format a monetary amount with currency symbol
 * @param amount The amount to format
 * @param decimals Number of decimal places (null = use system default)
 * @param showSymbol Whether to show currency symbol
 * @returns Formatted amount with currency
 */
export const formatMoney = (
    amount: number,
    decimals?: number,
    showSymbol: boolean = true
): string => {
    const { currency } = usePage().props as any;
    const currencyDecimals = decimals ?? (currency?.decimals || 2);
    const formatted = Number(amount).toFixed(currencyDecimals);

    if (showSymbol) {
        return `${formatted} ${currency?.symbol || 'ج.م'}`;
    }

    return formatted;
};

/**
 * Format currency without using hooks (for non-component contexts)
 * Use this when you cannot use hooks
 */
export const formatMoneyStatic = (
    amount: number,
    currencySettings: CurrencySettings,
    decimals?: number,
    showSymbol: boolean = true
): string => {
    const currencyDecimals = decimals ?? currencySettings.decimals;
    const formatted = Number(amount).toFixed(currencyDecimals);

    if (showSymbol) {
        return `${formatted} ${currencySettings.symbol}`;
    }

    return formatted;
};

/**
 * Format currency (alias for formatMoney)
 * Required for compatibility with files expecting formatCurrency export
 */
export const formatCurrency = formatMoney;
