import React, { useEffect, useState } from 'react';
import { BrandMark, CartWineIcon, FavoriteIcon } from '../common';
import { apiRequest, refreshSession } from '../../lib/api';
import { CART_UPDATED } from '../../lib/cart';
import { FAVORITES_UPDATED } from '../../lib/favorites';

function ShopActions({ cartCount, favoritesCount, className = '' }) {
    return <div className={`global-shop-actions ${className}`}><a className="global-favorites-link" href="/favorites" aria-label={`Favourites, ${favoritesCount} products`}><span className="global-favorites-icon"><FavoriteIcon /></span><span className="global-favorites-label">Favourites</span><span className="global-favorites-count" aria-hidden="true">{favoritesCount > 99 ? '99+' : favoritesCount}</span></a><a className="global-cart-link" href="/cart" aria-label={`Shopping cart, ${cartCount} positions`}><span className="global-cart-icon"><CartWineIcon /></span><span className="global-cart-label">Cart</span><span className="global-cart-count" aria-hidden="true">{cartCount > 99 ? '99+' : cartCount}</span></a></div>;
}

export function Header({ overlay = false }) {
    const [open, setOpen] = useState(false);
    const [cartCount, setCartCount] = useState(0);
    const [favoritesCount, setFavoritesCount] = useState(0);

    useEffect(() => {
        let active = true;
        refreshSession()
            .then(() => Promise.all([apiRequest('/api/cart'), apiRequest('/api/favorites')]))
            .then(([{ data: cart }, favorites]) => { if (active) { setCartCount(cart.items_count); setFavoritesCount(favorites.meta.total); } })
            .catch(() => { if (active) { setCartCount(0); setFavoritesCount(0); } });
        const updateCount = (event) => setCartCount(event.detail.itemsCount);
        const updateFavoritesCount = (event) => setFavoritesCount(event.detail.itemsCount);
        window.addEventListener(CART_UPDATED, updateCount);
        window.addEventListener(FAVORITES_UPDATED, updateFavoritesCount);

        return () => { active = false; window.removeEventListener(CART_UPDATED, updateCount); window.removeEventListener(FAVORITES_UPDATED, updateFavoritesCount); };
    }, []);

    return (
        <header className={`global-header ${overlay ? 'global-header-overlay' : ''}`}>
            <a className="global-brand" href="/" aria-label="Siberian Wine Garden home"><BrandMark /><span>Siberian<br />Wine Garden</span></a>
            <ShopActions className="global-mobile-shop-actions" cartCount={cartCount} favoritesCount={favoritesCount} />
            <button className="global-menu-toggle" type="button" aria-expanded={open} aria-controls="global-navigation" onClick={() => setOpen((current) => !current)}><span /><span /></button>
            <nav id="global-navigation" className={`global-navigation ${open ? 'is-open' : ''}`}>
                <a href="/#story">Our story</a>
                <a href="/products">Products</a>
                <a href="/posts">Journal</a>
                <ShopActions className="global-desktop-shop-actions" cartCount={cartCount} favoritesCount={favoritesCount} />
                <a className="global-account-link" href="/login">Member access <span>↗</span></a>
            </nav>
        </header>
    );
}
