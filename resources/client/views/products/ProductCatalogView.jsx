import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { BrandMark, Button, Notice, PageLoader } from '../../components/common';
import { ProductCard, ProductEditor, ProductFilters } from '../../components/products';
import { apiRequest, refreshSession } from '../../lib/api';
import { PublicLayout } from '../../layouts/PublicLayout';
import { announceFavoritesUpdated } from '../../lib/favorites';
import '../../styles/common.css';

const emptySku = () => ({ key: crypto.randomUUID(), sku: '', name: '', stock_quantity: 0, price: 0, currency: 'EUR', quantity_unit: 'piece' });
const emptyAttribute = () => ({ key: crypto.randomUUID(), attribute_id: '', value: '' });
const emptyForm = () => ({ name: '', description: '', status: 'draft', category_id: '', skus: [emptySku()], attributes: [] });
const emptyFilters = () => ({ search: '', categories: [], priceFrom: '', priceTo: '', inStock: false, status: '', sort: 'newest', attributes: {} });

function filtersFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const attributes = {};

    for (const [key, value] of params.entries()) {
        const match = key.match(/^attributes\[(\d+)]\[\]$/);
        if (match) attributes[match[1]] = [...(attributes[match[1]] || []), value];
    }

    return {
        search: params.get('search') || '',
        categories: params.getAll('categories[]'),
        priceFrom: params.has('price_from') ? String(Number(params.get('price_from')) / 100) : '',
        priceTo: params.has('price_to') ? String(Number(params.get('price_to')) / 100) : '',
        inStock: params.get('in_stock') === '1',
        status: params.get('status') || '',
        sort: params.get('sort') || 'newest',
        attributes,
    };
}

function filterParams(filters, page = 1) {
    const params = new URLSearchParams();
    if (filters.search.trim()) params.set('search', filters.search.trim());
    filters.categories.forEach((category) => params.append('categories[]', category));
    if (filters.priceFrom !== '') params.set('price_from', String(Math.round(Number(filters.priceFrom) * 100)));
    if (filters.priceTo !== '') params.set('price_to', String(Math.round(Number(filters.priceTo) * 100)));
    if (filters.inStock) params.set('in_stock', '1');
    if (filters.status) params.set('status', filters.status);
    if (filters.sort !== 'newest') params.set('sort', filters.sort);
    Object.entries(filters.attributes).forEach(([attributeId, values]) => values.forEach((value) => params.append(`attributes[${attributeId}][]`, value)));
    if (page > 1) params.set('page', String(page));
    return params;
}

function formFromProduct(product) {
    return {
        name: product.name,
        description: product.description || '',
        status: product.status,
        category_id: product.category?.id || '',
        skus: product.skus.map((sku) => ({ ...sku, key: `sku-${sku.id}` })),
        attributes: product.attributes.map((attribute) => ({ ...attribute, key: `attribute-${attribute.attribute_id}` })),
    };
}

function payloadFrom(form) {
    return {
        name: form.name,
        description: form.description || null,
        status: form.status,
        category_id: form.category_id ? Number(form.category_id) : null,
        skus: form.skus.map(({ key, ...sku }) => ({ ...sku, stock_quantity: Number(sku.stock_quantity), price: Number(sku.price) })),
        attributes: form.attributes.map(({ key, id, name, type, unit, ...attribute }) => ({ attribute_id: Number(attribute.attribute_id), value: attribute.value })),
    };
}

function ProductCatalog() {
    const [user, setUser] = useState(null);
    const [products, setProducts] = useState([]);
    const [meta, setMeta] = useState(null);
    const [options, setOptions] = useState({ categories: [], attributes: [] });
    const [loading, setLoading] = useState(true);
    const [editorOpen, setEditorOpen] = useState(false);
    const [editingProduct, setEditingProduct] = useState(null);
    const [form, setForm] = useState(emptyForm);
    const [errors, setErrors] = useState({});
    const [notice, setNotice] = useState('');
    const [saving, setSaving] = useState(false);
    const [favoriteBusy, setFavoriteBusy] = useState(null);
    const [filters, setFilters] = useState(filtersFromUrl);
    const [filterOptions, setFilterOptions] = useState({ categories: [], attributes: [], statuses: [] });

    const canManage = ['admin', 'content_manager'].includes(user?.role);

    async function loadProducts(nextFilters = filters, page = 1, syncUrl = false) {
        const params = filterParams(nextFilters, page);
        const payload = await apiRequest(`/api/products?${params}`);
        setProducts(payload.data);
        setMeta(payload.meta);
        if (syncUrl) window.history.pushState({}, '', `/products${params.size ? `?${params}` : ''}`);
    }

    useEffect(() => {
        refreshSession()
            .catch(() => null)
            .then(async (session) => {
                setUser(session?.user || null);
                const page = Number(new URLSearchParams(window.location.search).get('page') || 1);
                const requests = [loadProducts(filters, page), apiRequest('/api/product-filters').then(setFilterOptions)];

                if (['admin', 'content_manager'].includes(session?.user?.role)) {
                    requests.push(apiRequest('/api/product-options').then(setOptions));
                }

                await Promise.all(requests);

                const editId = new URLSearchParams(window.location.search).get('edit');
                const createRequested = new URLSearchParams(window.location.search).get('create') === '1';
                if (editId && ['admin', 'content_manager'].includes(session?.user?.role)) {
                    const payload = await apiRequest(`/api/products/${editId}`);
                    openEdit(payload.data);
                } else if (createRequested && ['admin', 'content_manager'].includes(session?.user?.role)) {
                    openCreate();
                }
            })
            .finally(() => setLoading(false));
    }, []);

    function applyFilters(nextFilters) {
        setFilters(nextFilters);
        loadProducts(nextFilters, 1, true);
    }

    function resetFilters() {
        const reset = emptyFilters();
        setFilters(reset);
        loadProducts(reset, 1, true);
    }

    function openCreate() {
        setEditingProduct(null);
        setForm(emptyForm());
        setErrors({});
        setEditorOpen(true);
    }

    function openEdit(product) {
        setEditingProduct(product);
        setForm(formFromProduct(product));
        setErrors({});
        setEditorOpen(true);
    }

    function closeEditor() {
        setEditorOpen(false);
        setEditingProduct(null);
        setErrors({});
    }

    function updateList(name, index, field, value) {
        setForm((current) => ({ ...current, [name]: current[name].map((item, itemIndex) => itemIndex === index ? { ...item, [field]: value } : item) }));
    }

    async function saveProduct(event) {
        event.preventDefault();
        setSaving(true);
        setErrors({});

        try {
            const path = editingProduct ? `/api/products/${editingProduct.id}` : '/api/products';
            await apiRequest(path, { method: editingProduct ? 'PUT' : 'POST', body: JSON.stringify(payloadFrom(form)) });
            setNotice(editingProduct ? 'Product updated.' : 'Product created.');
            closeEditor();
            await loadProducts(filters, meta?.current_page || 1);
        } catch (error) {
            setErrors(error.errors || {});
            if (!Object.keys(error.errors || {}).length) setNotice(error.message);
        } finally {
            setSaving(false);
        }
    }

    async function archiveProduct(product) {
        if (!window.confirm(`Archive “${product.name}”? Customers will no longer see or purchase it.`)) return;

        try {
            await apiRequest(`/api/products/${product.id}`, { method: 'DELETE' });
            setNotice('Product archived.');
            await loadProducts(filters, products.length === 1 && meta?.current_page > 1 ? meta.current_page - 1 : meta?.current_page || 1);
        } catch (error) {
            setNotice(error.message);
        }
    }

    async function toggleFavorite(product) {
        if (!user) return window.location.assign(`/login?redirect=${encodeURIComponent(window.location.pathname)}`);
        setFavoriteBusy(product.id);

        try {
            if (product.is_favorite) {
                await apiRequest(`/api/favorites/${product.id}`, { method: 'DELETE' });
            } else {
                await apiRequest(`/api/favorites/${product.id}`, { method: 'POST' });
            }

            setProducts((current) => current.map((item) => item.id === product.id ? { ...item, is_favorite: !product.is_favorite } : item));
            const { meta: favoritesMeta } = await apiRequest('/api/favorites');
            announceFavoritesUpdated(favoritesMeta.total);
        } catch (error) {
            setNotice(error.message);
        } finally {
            setFavoriteBusy(null);
        }
    }

    if (loading) return <PageLoader className="products-loading" markClassName="products-mark">Opening the market…</PageLoader>;

    return (
        <PublicLayout>
            <div className="products-shell">
            <main className="products-main">
                <section className="products-heading">
                    <div><p className="product-kicker">Northern marketplace</p><h1>Cellar &amp; garden</h1><p>Wine, grapes, tools, and everything shaped by the northern harvest.</p></div>
                    {canManage ? <Button onClick={openCreate}>Add product</Button> : !user && <a className="product-login-action" href="/login?redirect=/products">Admin sign in</a>}
                </section>
                <Notice className="products-notice" onClose={() => setNotice('')}>{notice}</Notice>
                <ProductFilters configuration={filterOptions} filters={filters} canManage={canManage} onApply={applyFilters} onReset={resetFilters} />
                {products.length ? (
                    <section className="products-grid" aria-label="Products">
                        {products.map((product) => <ProductCard key={product.id} product={product} canManage={canManage} favoriteBusy={favoriteBusy === product.id} onEdit={openEdit} onArchive={archiveProduct} onToggleFavorite={toggleFavorite} />)}
                    </section>
                ) : (
                    <section className="products-empty"><BrandMark className="products-mark" /><h2>The cellar is waiting.</h2><p>No products are available yet.</p>{canManage && <Button onClick={openCreate}>Create first product</Button>}</section>
                )}
                {meta?.last_page > 1 && (
                    <nav className="products-pagination" aria-label="Product pages">
                        <Button variant="secondary" disabled={meta.current_page === 1} onClick={() => loadProducts(filters, meta.current_page - 1, true)}>Previous</Button>
                        <span>{meta.current_page} / {meta.last_page}</span>
                        <Button variant="secondary" disabled={meta.current_page === meta.last_page} onClick={() => loadProducts(filters, meta.current_page + 1, true)}>Next</Button>
                    </nav>
                )}
            </main>
            {editorOpen && <>
                <button type="button" className="product-editor-backdrop" onClick={closeEditor} aria-label="Close editor" />
                <ProductEditor product={editingProduct} form={form} options={options} errors={errors} saving={saving} onChange={(name, value) => setForm((current) => ({ ...current, [name]: value }))} onSkuChange={(index, field, value) => updateList('skus', index, field, value)} onAddSku={() => setForm((current) => ({ ...current, skus: [...current.skus, emptySku()] }))} onRemoveSku={(index) => setForm((current) => ({ ...current, skus: current.skus.filter((_, itemIndex) => itemIndex !== index) }))} onAttributeChange={(index, field, value) => updateList('attributes', index, field, value)} onAddAttribute={() => setForm((current) => ({ ...current, attributes: [...current.attributes, emptyAttribute()] }))} onRemoveAttribute={(index) => setForm((current) => ({ ...current, attributes: current.attributes.filter((_, itemIndex) => itemIndex !== index) }))} onCancel={closeEditor} onSubmit={saveProduct} />
            </>}
            </div>
        </PublicLayout>
    );
}

createRoot(document.getElementById('products-root')).render(<ProductCatalog />);
