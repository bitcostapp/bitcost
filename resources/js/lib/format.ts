export function formatCost(amount: number, currency: string | null): string {
    if (!currency) {
        return amount.toFixed(2);
    }

    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
    }).format(amount);
}

export function formatTokens(total: number): string {
    return new Intl.NumberFormat(undefined, {
        notation: 'compact',
        maximumFractionDigits: 1,
    }).format(total);
}
