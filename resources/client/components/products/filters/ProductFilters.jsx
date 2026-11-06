import React, { useEffect, useState } from 'react';
import { Button, Input, Select } from '../../common';
import { t } from '../../../i18n';

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

    return <form className="product-filters" onSubmit={submit}><div className="product-filter-main"><label><span>{t('filters.search')}</span><Input type="search" placeholder={t('filters.searchPlaceholder')} value={draft.search} onChange={(event) => setDraft((current) => ({ ...current, search: event.target.value }))} /></label><label><span>{t('filters.priceFrom')}</span><Input type="number" min="0" step="0.01" value={draft.priceFrom} onChange={(event) => setDraft((current) => ({ ...current, priceFrom: event.target.value }))} /></label><label><span>{t('filters.priceTo')}</span><Input type="number" min="0" step="0.01" value={draft.priceTo} onChange={(event) => setDraft((current) => ({ ...current, priceTo: event.target.value }))} /></label><label><span>{t('filters.sort')}</span><Select value={draft.sort} onChange={(event) => setDraft((current) => ({ ...current, sort: event.target.value }))}><option value="newest">{t('filters.newest')}</option><option value="name_asc">{t('filters.name')}</option><option value="price_asc">{t('filters.priceAsc')}</option><option value="price_desc">{t('filters.priceDesc')}</option></Select></label></div><div className="product-filter-groups"><fieldset><legend>{t('filters.categories')}</legend>{configuration.categories.map((category) => <label key={category.id}><input type="checkbox" checked={draft.categories.includes(String(category.id))} onChange={() => setDraft((current) => ({ ...current, categories: toggleValue(current.categories, String(category.id)) }))} />{category.name}</label>)}</fieldset>{configuration.attributes.map((attribute) => <fieldset key={attribute.id}><legend>{attribute.name}{attribute.unit ? `, ${attribute.unit}` : ''}</legend>{attribute.values.map((option) => <label key={option.value}><input type="checkbox" checked={(draft.attributes[attribute.id] || []).includes(option.value)} onChange={() => setDraft((current) => ({ ...current, attributes: { ...current.attributes, [attribute.id]: toggleValue(current.attributes[attribute.id] || [], option.value) } }))} />{option.label}</label>)}</fieldset>)}{canManage && <label className="product-filter-status"><span>{t('filters.status')}</span><Select value={draft.status} onChange={(event) => setDraft((current) => ({ ...current, status: event.target.value }))}><option value="">{t('filters.allStatuses')}</option>{configuration.statuses.map((status) => <option key={status} value={status}>{status}</option>)}</Select></label>}<label className="product-filter-stock"><input type="checkbox" checked={draft.inStock} onChange={(event) => setDraft((current) => ({ ...current, inStock: event.target.checked }))} />{t('filters.inStock')}</label></div><div className="product-filter-actions"><Button type="submit">{t('filters.apply')}</Button><Button type="button" variant="secondary" onClick={onReset}>{t('filters.reset')}</Button></div></form>;
}
