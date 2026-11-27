const priceFormatter = new Intl.NumberFormat('en-GB', {
    style: 'currency',
    currency: 'GBP',
    maximumFractionDigits: 0,
});

export function formatPrice(price) {
    return priceFormatter.format(price);
}

export function formatDate(iso) {
    if (!iso) {
        return null;
    }

    return new Intl.DateTimeFormat('en-GB', { dateStyle: 'long' }).format(new Date(iso));
}

export function criteriaLabel(search) {
    if (!search) {
        return 'Saved search removed';
    }

    const parts = [];

    if (search.property_type_label) {
        parts.push(search.property_type_label);
    }
    if (search.region) {
        parts.push(search.region);
    }
    if (search.min_bedrooms != null) {
        parts.push(`${search.min_bedrooms}+ beds`);
    }
    if (search.max_price != null) {
        parts.push(`up to ${formatPrice(search.max_price)}`);
    }

    return parts.length ? parts.join(' · ') : 'Any listing';
}
