import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { BrandMark, PageLoader } from '../../components/common';
import { ProductCard } from '../../components/products';
import { PublicLayout } from '../../layouts/PublicLayout';
import { apiRequest, refreshSession } from '../../lib/api';
import { announceFavoritesUpdated } from '../../lib/favorites';
import '../../styles/common.css';
import { t } from '../../i18n';

function FavoritesView() {
    const [products, setProducts] = useState(null);
    const [busy, setBusy] = useState(null);

    useEffect(() => {
        refreshSession()
            .then(() => apiRequest('/api/favorites'))
            .then(({ data }) => setProducts(data))
            .catch(() => window.location.assign('/login?redirect=/favorites'));
    }, []);

    async function removeFavorite(product) {
        setBusy(product.id);

        try {
            await apiRequest(`/api/favorites/${product.id}`, { method: 'DELETE' });
            setProducts((current) => current.filter((item) => item.id !== product.id));
            const { meta } = await apiRequest('/api/favorites');
            announceFavoritesUpdated(meta.total);
        } finally {
            setBusy(null);
        }
    }

    if (!products) return <PageLoader className="products-loading" markClassName="products-mark">{t('Opening your favourites…')}</PageLoader>;

    return <PublicLayout><main className="products-main favorites-main"><section className="products-heading"><div><p className="product-kicker">{t('Saved for later')}</p><h1>{t('Your favourites')}</h1><p>{t('A personal collection of wines, grapes, and garden finds worth returning to.')}</p></div><a className="product-login-action" href="/products">{t('Explore products')}</a></section>
        {products.length ? <section className="products-grid" aria-label={t('Your favourites')}>{products.map((product) => <ProductCard key={product.id} product={product} favoriteBusy={busy === product.id} onToggleFavorite={removeFavorite} />)}</section> : <section className="products-empty"><BrandMark className="products-mark" /><h2>{t('Nothing saved yet.')}</h2><p>{t('Use the heart on any product to keep it here.')}</p><a className="product-login-action" href="/products">{t('Browse the market')}</a></section>}
    </main></PublicLayout>;
}

createRoot(document.getElementById('favorites-root')).render(<FavoritesView />);
