import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { BrandMark, Button, Notice, PageLoader } from '../../components/common';
import { ProductCard, ProductEditor } from '../../components/products';
import { apiRequest, refreshSession } from '../../lib/api';
import { PublicLayout } from '../../layouts/PublicLayout';
import '../../styles/common.css';

const emptySku = () => ({ key: crypto.randomUUID(), sku: '', name: '', stock_quantity: 0, price: 0, currency: 'EUR', quantity_unit: 'piece' });
const emptyAttribute = () => ({ key: crypto.randomUUID(), attribute_id: '', value: '' });
const emptyForm = () => ({ name: '', description: '', status: 'draft', category_id: '', skus: [emptySku()], attributes: [] });

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

    const canManage = user?.role === 'admin';

    async function loadProducts(page = 1) {
        const payload = await apiRequest(`/api/products?page=${page}`);
        setProducts(payload.data);
        setMeta(payload.meta);
    }

    useEffect(() => {
        refreshSession()
            .catch(() => null)
            .then(async (session) => {
                setUser(session?.user || null);
                const requests = [loadProducts()];

                if (session?.user?.role === 'admin') {
                    requests.push(apiRequest('/api/product-options').then(setOptions));
                }

                await Promise.all(requests);

                const editId = new URLSearchParams(window.location.search).get('edit');
                if (editId && session?.user?.role === 'admin') {
                    const payload = await apiRequest(`/api/products/${editId}`);
                    openEdit(payload.data);
                }
            })
            .finally(() => setLoading(false));
    }, []);

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
            await loadProducts(meta?.current_page || 1);
        } catch (error) {
            setErrors(error.errors || {});
            if (!Object.keys(error.errors || {}).length) setNotice(error.message);
        } finally {
            setSaving(false);
        }
    }

    async function deleteProduct(product) {
        if (!window.confirm(`Delete “${product.name}”? This cannot be undone.`)) return;

        try {
            await apiRequest(`/api/products/${product.id}`, { method: 'DELETE' });
            setNotice('Product deleted.');
            await loadProducts(products.length === 1 && meta?.current_page > 1 ? meta.current_page - 1 : meta?.current_page || 1);
        } catch (error) {
            setNotice(error.message);
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
                {products.length ? (
                    <section className="products-grid" aria-label="Products">
                        {products.map((product) => <ProductCard key={product.id} product={product} canManage={canManage} onEdit={openEdit} onDelete={deleteProduct} />)}
                    </section>
                ) : (
                    <section className="products-empty"><BrandMark className="products-mark" /><h2>The cellar is waiting.</h2><p>No products are available yet.</p>{canManage && <Button onClick={openCreate}>Create first product</Button>}</section>
                )}
                {meta?.last_page > 1 && (
                    <nav className="products-pagination" aria-label="Product pages">
                        <Button variant="secondary" disabled={meta.current_page === 1} onClick={() => loadProducts(meta.current_page - 1)}>Previous</Button>
                        <span>{meta.current_page} / {meta.last_page}</span>
                        <Button variant="secondary" disabled={meta.current_page === meta.last_page} onClick={() => loadProducts(meta.current_page + 1)}>Next</Button>
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
