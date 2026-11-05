import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { BrandMark, Button, FavoriteButton, PageLoader, StatusBadge } from '../../components/common';
import { apiRequest, refreshSession } from '../../lib/api';
import { PublicLayout } from '../../layouts/PublicLayout';
import { announceCartUpdated } from '../../lib/cart';
import { announceFavoritesUpdated } from '../../lib/favorites';
import '../../styles/common.css';

function price(sku) {
    return new Intl.NumberFormat(undefined, { style: 'currency', currency: sku.currency }).format(sku.price / 100);
}

function ProductView({ productId }) {
    const [product, setProduct] = useState(null);
    const [user, setUser] = useState(null);
    const [error, setError] = useState('');
    const [cartMessage, setCartMessage] = useState('');
    const [addingSku, setAddingSku] = useState(null);
    const [favoriteBusy, setFavoriteBusy] = useState(false);

    useEffect(() => {
        refreshSession()
            .catch(() => null)
            .then((session) => {
                setUser(session?.user || null);
                return apiRequest(`/api/products/${productId}`);
            })
            .then((payload) => setProduct(payload.data))
            .catch((requestError) => {
                if (requestError.status === 403) return setError('This product is not available.');
                setError('The product could not be loaded.');
            });
    }, [productId]);

    async function addToCart(sku) {
        if (!user) return window.location.assign('/login');
        setAddingSku(sku.id);
        setCartMessage('');
        try {
            const { data } = await apiRequest('/api/cart/items', { method: 'POST', body: JSON.stringify({ sku_id: sku.id, quantity: 1 }) });
            announceCartUpdated(data.items_count);
            setCartMessage(`${sku.name} was added to your cart.`);
        } catch (requestError) {
            setCartMessage(Object.values(requestError.errors || {})[0]?.[0] || requestError.message);
        } finally {
            setAddingSku(null);
        }
    }

    async function toggleFavorite() {
        if (!user) return window.location.assign(`/login?redirect=${encodeURIComponent(window.location.pathname)}`);
        setFavoriteBusy(true);

        try {
            if (product.is_favorite) {
                await apiRequest(`/api/favorites/${product.id}`, { method: 'DELETE' });
            } else {
                await apiRequest(`/api/favorites/${product.id}`, { method: 'POST' });
            }

            setProduct((current) => ({ ...current, is_favorite: !current.is_favorite }));
            const { meta } = await apiRequest('/api/favorites');
            announceFavoritesUpdated(meta.total);
        } finally {
            setFavoriteBusy(false);
        }
    }

    if (error) return <main className="product-state"><BrandMark className="products-mark" /><h1>Product unavailable</h1><p>{error}</p><a href="/products">Back to products</a></main>;
    if (!product) return <PageLoader className="product-state" markClassName="products-mark">Opening the cellar…</PageLoader>;

    return (
        <PublicLayout>
            <div className="product-page">
            <header className="product-page-nav"><a href="/products">← All products</a>{user?.role === 'admin' ? <a href={`/products?edit=${product.id}`}>Edit product</a> : <span />}</header>
            <main className="product-page-main">
                <section className="product-hero"><div className="product-hero-symbol">{product.name.slice(0, 1).toUpperCase()}</div><div className="product-hero-copy"><div className="product-hero-meta"><span>{product.category?.name || 'Uncategorised'}</span><StatusBadge status={product.status} /><FavoriteButton active={product.is_favorite} busy={favoriteBusy} onClick={toggleFavorite} /></div><h1>{product.name}</h1><p>{product.description || 'No description is available for this product.'}</p></div></section>
                <section className="product-page-section"><div className="section-heading"><p className="product-kicker">Available formats</p><h2>Choose your variant</h2>{cartMessage && <p>{cartMessage} <a href="/cart">View cart →</a></p>}</div><div className="sku-grid">{product.skus.map((sku) => <article key={sku.id} className="sku-card"><div><span>{sku.sku}</span><h3>{sku.name}</h3></div><strong>{price(sku)}</strong><p>{sku.stock_quantity > 0 ? `${sku.stock_quantity} in stock` : 'Out of stock'} · per {sku.quantity_unit}</p><Button disabled={sku.stock_quantity < 1 || addingSku === sku.id} onClick={() => addToCart(sku)}>{addingSku === sku.id ? 'Adding…' : 'Add to cart'}</Button></article>)}</div></section>
                {!!product.attributes.length && <section className="product-page-section characteristics"><div className="section-heading"><p className="product-kicker">Product profile</p><h2>Characteristics</h2></div><dl>{product.attributes.map((attribute) => <div key={attribute.id}><dt>{attribute.name}</dt><dd>{attribute.value}{attribute.unit ? ` ${attribute.unit}` : ''}</dd></div>)}</dl></section>}
            </main>
            </div>
        </PublicLayout>
    );
}

const root = document.getElementById('product-root');
createRoot(root).render(<ProductView productId={root.dataset.productId} />);
