import React, { useState } from 'react';
import { BrandMark } from '../common';

export function Header({ overlay = false }) {
    const [open, setOpen] = useState(false);

    return (
        <header className={`global-header ${overlay ? 'global-header-overlay' : ''}`}>
            <a className="global-brand" href="/" aria-label="Siberian Wine Garden home"><BrandMark /><span>Siberian<br />Wine Garden</span></a>
            <button className="global-menu-toggle" type="button" aria-expanded={open} aria-controls="global-navigation" onClick={() => setOpen((current) => !current)}><span /><span /></button>
            <nav id="global-navigation" className={`global-navigation ${open ? 'is-open' : ''}`}>
                <a href="/#story">Our story</a>
                <a href="/products">Products</a>
                <a href="/posts">Journal</a>
                <a className="global-account-link" href="/login">Member access <span>↗</span></a>
            </nav>
        </header>
    );
}
