import React from 'react';
import { BrandMark } from '../common';
import { t } from '../../i18n';

export function Footer() {
    return (
        <footer className="global-footer">
            <div className="global-footer-main global-layout-shell">
                <div className="global-footer-brand">
                    <a className="global-brand" href="/"><BrandMark /><span>Siberian<br />Wine Garden</span></a>
                    <p>{t('footer.description')}</p>
                </div>
                <nav className="global-footer-navigation" aria-label={t('footer.sitemap')}>
                    <div><h2>{t('footer.explore')}</h2><a href="/products">{t('nav.products')}</a><a href="/posts">{t('nav.journal')}</a><a href="/#story">{t('nav.story')}</a></div>
                    <div><h2>{t('footer.account')}</h2><a href="/login">{t('footer.signIn')}</a><a href="/register">{t('footer.createAccount')}</a><a href="/profile">{t('nav.profile')}</a><a href="/favorites">{t('shop.favorites')}</a></div>
                    <div><h2>{t('footer.collections')}</h2><a href="/products">{t('footer.wine')}</a><a href="/products">{t('footer.grapes')}</a><a href="/products">{t('footer.tools')}</a></div>
                </nav>
            </div>
            <div className="global-footer-bottom global-layout-shell"><span>© {new Date().getFullYear()} Siberian Wine Garden</span><span>{t('footer.patience')}</span><button type="button" onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}>{t('footer.backToTop')}</button></div>
        </footer>
    );
}
