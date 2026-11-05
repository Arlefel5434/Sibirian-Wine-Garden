import React from 'react';
import { BrandMark } from '../common';

export function Footer() {
    return (
        <footer className="global-footer">
            <div className="global-footer-main global-layout-shell">
                <div className="global-footer-brand">
                    <a className="global-brand" href="/"><BrandMark /><span>Siberian<br />Wine Garden</span></a>
                    <p>Wine, grapes, and garden craft shaped by the northern seasons.</p>
                </div>
                <nav className="global-footer-navigation" aria-label="Sitemap">
                    <div><h2>Explore</h2><a href="/products">Products</a><a href="/posts">Journal</a><a href="/#story">Our story</a></div>
                    <div><h2>Account</h2><a href="/login">Sign in</a><a href="/register">Create account</a><a href="/profile">Profile</a><a href="/favorites">Favourites</a></div>
                    <div><h2>Collections</h2><a href="/products">Wine</a><a href="/products">Grapes</a><a href="/products">Vineyard tools</a></div>
                </nav>
            </div>
            <div className="global-footer-bottom global-layout-shell"><span>© {new Date().getFullYear()} Siberian Wine Garden</span><span>Grown with patience in the north</span><button type="button" onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}>Back to top ↑</button></div>
        </footer>
    );
}
