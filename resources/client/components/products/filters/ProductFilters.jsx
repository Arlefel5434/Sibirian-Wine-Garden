import React, { useEffect, useState } from 'react';
import { Button, Input, Select } from '../../common';

function toggleValue(values, value) {
    return values.includes(value) ? values.filter((item) => item !== value) : [...values, value];
}

export function ProductFilters({ configuration, filters, canManage, onApply, onReset }) {
    const [draft, setDraft] = useState(filters);

    useEffect(() => setDraft(filters), [filters]);

    function submit(event) {
        event.preventDefault();
        onApply(draft);
    }

    return <form className="product-filters" onSubmit={submit}><div className="product-filter-main"><label><span>Search</span><Input type="search" placeholder="Wine, grapes, tools…" value={draft.search} onChange={(event) => setDraft((current) => ({ ...current, search: event.target.value }))} /></label><label><span>Price from, EUR</span><Input type="number" min="0" step="0.01" value={draft.priceFrom} onChange={(event) => setDraft((current) => ({ ...current, priceFrom: event.target.value }))} /></label><label><span>Price to, EUR</span><Input type="number" min="0" step="0.01" value={draft.priceTo} onChange={(event) => setDraft((current) => ({ ...current, priceTo: event.target.value }))} /></label><label><span>Sort</span><Select value={draft.sort} onChange={(event) => setDraft((current) => ({ ...current, sort: event.target.value }))}><option value="newest">Newest</option><option value="name_asc">Name A–Z</option><option value="price_asc">Price: low to high</option><option value="price_desc">Price: high to low</option></Select></label></div><div className="product-filter-groups"><fieldset><legend>Categories</legend>{configuration.categories.map((category) => <label key={category.id}><input type="checkbox" checked={draft.categories.includes(String(category.id))} onChange={() => setDraft((current) => ({ ...current, categories: toggleValue(current.categories, String(category.id)) }))} />{category.name}</label>)}</fieldset>{configuration.attributes.map((attribute) => <fieldset key={attribute.id}><legend>{attribute.name}{attribute.unit ? `, ${attribute.unit}` : ''}</legend>{attribute.values.map((value) => <label key={value}><input type="checkbox" checked={(draft.attributes[attribute.id] || []).includes(value)} onChange={() => setDraft((current) => ({ ...current, attributes: { ...current.attributes, [attribute.id]: toggleValue(current.attributes[attribute.id] || [], value) } }))} />{value}</label>)}</fieldset>)}{canManage && <label className="product-filter-status"><span>Status</span><Select value={draft.status} onChange={(event) => setDraft((current) => ({ ...current, status: event.target.value }))}><option value="">All statuses</option>{configuration.statuses.map((status) => <option key={status} value={status}>{status}</option>)}</Select></label>}<label className="product-filter-stock"><input type="checkbox" checked={draft.inStock} onChange={(event) => setDraft((current) => ({ ...current, inStock: event.target.checked }))} />In stock only</label></div><div className="product-filter-actions"><Button type="submit">Apply filters</Button><Button type="button" variant="secondary" onClick={onReset}>Reset</Button></div></form>;
}
