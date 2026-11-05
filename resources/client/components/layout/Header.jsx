import React, { useEffect, useState } from 'react';
import { BrandMark, CartWineIcon } from '../common';
import { apiRequest, refreshSession } from '../../lib/api';
import { CART_UPDATED } from '../../lib/cart';

export function Header({ overlay = false }) {
    const [open, setOpen] = useState(false);
    const [cartCount, setCartCount] = useState(0);

    useEffect(() => {
        let active = true;
        refreshSession()
            .then(() => apiRequest('/api/cart'))
            .then(({ data }) => { if (active) setCartCount(data.items_count); })
            .catch(() => { if (active) setCartCount(0); });
        const updateCount = (event) => setCartCount(event.detail.itemsCount);
        window.addEventListener(CART_UPDATED, updateCount);

        return () => { active = false; window.removeEventListener(CART_UPDATED, updateCount); };
    }, []);

    return (
        <header className={`global-header ${overlay ? 'global-header-overlay' : ''}`}>
            <a className="global-brand" href="/" aria-label="Siberian Wine Garden home"><BrandMark /><span>Siberian<br />Wine Garden</span></a>
            <button className="global-menu-toggle" type="button" aria-expanded={open} aria-controls="global-navigation" onClick={() => setOpen((current) => !current)}><span /><span /></button>
            <nav id="global-navigation" className={`global-navigation ${open ? 'is-open' : ''}`}>
                <a href="/#story">Our story</a>
                <a href="/products">Products</a>
                <a href="/posts">Journal</a>
                <a className="global-cart-link" href="/cart" aria-label={`Shopping cart, ${cartCount} positions`}><span className="global-cart-icon"><CartWineIcon /></span><span className="global-cart-label">Cart</span><span className="global-cart-count" aria-hidden="true">{cartCount > 99 ? '99+' : cartCount}</span></a>
                <a className="global-account-link" href="/login">Member access <span>↗</span></a>
            </nav>
        </header>
    );
}
