import React, { useEffect, useState } from 'react';
import { BrandMark, CartWineIcon, DoorIcon, FavoriteIcon } from '../common';
import { apiRequest, refreshSession } from '../../lib/api';
import { CART_UPDATED } from '../../lib/cart';
import { FAVORITES_UPDATED } from '../../lib/favorites';
import { WALLET_UPDATED } from '../../lib/wallet';

const money = (amount, currency) => new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency,
    maximumFractionDigits: 2,
}).format(amount / 100);

function ShopActions({ cartCount, favoritesCount, wallet, className = '' }) {
    return <div className={`global-shop-actions ${className}`}>{wallet && <a className="global-wallet-link" href="/wallet" aria-label={`Wallet balance ${money(wallet.balance, wallet.currency)}`}><span>Balance</span><strong>{money(wallet.balance, wallet.currency)}</strong></a>}<a className="global-favorites-link" href="/favorites" aria-label={`Favourites, ${favoritesCount} products`}><span className="global-favorites-icon"><FavoriteIcon /></span><span className="global-favorites-label">Favourites</span><span className="global-favorites-count" aria-hidden="true">{favoritesCount > 99 ? '99+' : favoritesCount}</span></a><a className="global-cart-link" href="/cart" aria-label={`Shopping cart, ${cartCount} positions`}><span className="global-cart-icon"><CartWineIcon /></span><span className="global-cart-label">Cart</span><span className="global-cart-count" aria-hidden="true">{cartCount > 99 ? '99+' : cartCount}</span></a></div>;
}

export function Header({ overlay = false }) {
    const [open, setOpen] = useState(false);
    const [cartCount, setCartCount] = useState(0);
    const [favoritesCount, setFavoritesCount] = useState(0);
    const [wallet, setWallet] = useState(null);
    const [user, setUser] = useState(null);
    const returnPath = `${window.location.pathname}${window.location.search}${window.location.hash}`;
    const loginHref = window.location.pathname === '/login' ? '/login' : `/login?redirect=${encodeURIComponent(returnPath)}`;

    useEffect(() => {
        let active = true;
        refreshSession()
            .then(({ user: sessionUser }) => { if (active) setUser(sessionUser); return Promise.all([apiRequest('/api/cart'), apiRequest('/api/favorites'), apiRequest('/api/wallet')]); })
            .then(([{ data: cart }, favorites, { data: walletData }]) => { if (active) { setCartCount(cart.items_count); setFavoritesCount(favorites.meta.total); setWallet(walletData); } })
            .catch(() => { if (active) { setCartCount(0); setFavoritesCount(0); setWallet(null); setUser(null); } });
        const updateCount = (event) => setCartCount(event.detail.itemsCount);
        const updateFavoritesCount = (event) => setFavoritesCount(event.detail.itemsCount);
        const updateWallet = (event) => setWallet(event.detail);
        window.addEventListener(CART_UPDATED, updateCount);
        window.addEventListener(FAVORITES_UPDATED, updateFavoritesCount);
        window.addEventListener(WALLET_UPDATED, updateWallet);

        return () => { active = false; window.removeEventListener(CART_UPDATED, updateCount); window.removeEventListener(FAVORITES_UPDATED, updateFavoritesCount); window.removeEventListener(WALLET_UPDATED, updateWallet); };
    }, []);

    return (
        <header className={`global-header ${overlay ? 'global-header-overlay' : ''}`}>
            <a className="global-brand" href="/" aria-label="Siberian Wine Garden home"><BrandMark /><span>Siberian<br />Wine Garden</span></a>
            <ShopActions className="global-mobile-shop-actions" cartCount={cartCount} favoritesCount={favoritesCount} wallet={wallet} />
            <button className="global-menu-toggle" type="button" aria-expanded={open} aria-controls="global-navigation" onClick={() => setOpen((current) => !current)}><span /><span /></button>
            <nav id="global-navigation" className={`global-navigation ${open ? 'is-open' : ''}`}>
                <a href="/#story">Our story</a>
                <a href="/products">Products</a>
                <a href="/posts">Journal</a>
                <ShopActions className="global-desktop-shop-actions" cartCount={cartCount} favoritesCount={favoritesCount} wallet={wallet} />
                {user ? <a className="global-account-link" href="/profile">Profile <span>↗</span></a> : <a className="global-account-link global-login-link" href={loginHref}><DoorIcon /> Login</a>}
            </nav>
        </header>
    );
}
