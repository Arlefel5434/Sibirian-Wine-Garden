import React, { useEffect, useState } from 'react';
import { BrandMark, CartWineIcon, DoorIcon, FavoriteIcon } from '../common';
import { apiRequest, refreshSession } from '../../lib/api';
import { CART_UPDATED } from '../../lib/cart';
import { FAVORITES_UPDATED } from '../../lib/favorites';
import { WALLET_UPDATED } from '../../lib/wallet';
import { changeLocale, currentLocale, formatMoney, supportedLocales, t } from '../../i18n';

function ShopActions({ cartCount, favoritesCount, wallet, className = '' }) {
    return <div className={`global-shop-actions ${className}`}>{wallet && <a className="global-wallet-link" href="/wallet" aria-label={`${t('shop.balance')} ${formatMoney(wallet.balance, wallet.currency)}`}><span>{t('shop.balance')}</span><strong>{formatMoney(wallet.balance, wallet.currency)}</strong></a>}<a className="global-favorites-link" href="/favorites" aria-label={`${t('shop.favorites')}: ${favoritesCount}`}><span className="global-favorites-icon"><FavoriteIcon /></span><span className="global-favorites-label">{t('shop.favorites')}</span><span className="global-favorites-count" aria-hidden="true">{favoritesCount > 99 ? '99+' : favoritesCount}</span></a><a className="global-cart-link" href="/cart" aria-label={`${t('shop.cart')}: ${cartCount}`}><span className="global-cart-icon"><CartWineIcon /></span><span className="global-cart-label">{t('shop.cart')}</span><span className="global-cart-count" aria-hidden="true">{cartCount > 99 ? '99+' : cartCount}</span></a></div>;
}

function LanguageSwitcher({ locales }) {
    return <label className="global-language-switcher"><select value={currentLocale()} onChange={(event) => changeLocale(event.target.value)}>{locales.map((locale) => <option key={locale.code} value={locale.code}>{locale.label || locale.code.toUpperCase()}</option>)}</select></label>;
}

export function Header({ overlay = false }) {
    const [open, setOpen] = useState(false);
    const [cartCount, setCartCount] = useState(0);
    const [favoritesCount, setFavoritesCount] = useState(0);
    const [wallet, setWallet] = useState(null);
    const [user, setUser] = useState(null);
    const [locales, setLocales] = useState(supportedLocales);
    const returnPath = `${window.location.pathname}${window.location.search}${window.location.hash}`;
    const loginHref = window.location.pathname === '/login' ? '/login' : `/login?redirect=${encodeURIComponent(returnPath)}`;

    useEffect(() => {
        apiRequest('/api/locales').then(({ supported }) => setLocales(supported)).catch(() => null);
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
            <LanguageSwitcher locales={locales} />
            <ShopActions className="global-mobile-shop-actions" cartCount={cartCount} favoritesCount={favoritesCount} wallet={wallet} />
            <button className="global-menu-toggle" type="button" aria-expanded={open} aria-controls="global-navigation" onClick={() => setOpen((current) => !current)}><span /><span /></button>
            <nav id="global-navigation" className={`global-navigation ${open ? 'is-open' : ''}`}>
                <a href="/#story">{t('nav.story')}</a>
                <a href="/products">{t('nav.products')}</a>
                <a href="/posts">{t('nav.journal')}</a>
                <ShopActions className="global-desktop-shop-actions" cartCount={cartCount} favoritesCount={favoritesCount} wallet={wallet} />
                {user ? <a className="global-account-link" href="/profile">{t('nav.profile')} <span>↗</span></a> : <a className="global-account-link global-login-link" href={loginHref}><DoorIcon /> {t('nav.login')}</a>}
            </nav>
        </header>
    );
}
