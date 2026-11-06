import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { PageLoader } from '../../components/common';
import { PublicLayout } from '../../layouts/PublicLayout';
import { apiRequest } from '../../lib/api';
import '../../styles/common.css';
import { currentLocale, formatMoney, t } from '../../i18n';

function ProductCard({ product }) {
    const sku = [...product.skus].sort((first, second) => first.price - second.price)[0];
    const price = sku ? formatMoney(sku.price, sku.currency) : null;

    return (
        <a className="home-product" href={`/products/${product.slug}`}>
            <div className="product-visual"><span>{product.name.slice(0, 1).toUpperCase()}</span><i /></div>
            <div className="product-card-top"><span>{product.category?.name || t('home.gardenSelection')}</span><span>↗</span></div>
            <h3>{product.name}</h3>
            <p>{product.description || t('home.productFallback')}</p>
            <div className="product-price">{price ? <><span>{t('home.from')}</span> {price}</> : <span>{t('home.exploreProduct')}</span>}</div>
        </a>
    );
}

function PostCard({ post, index }) {
    return (
        <article className="journal-card">
            <a className="journal-image" href={`/posts/${post.slug}`}>
                {post.preview_img ? <img src={post.preview_img} alt="" loading="lazy" /> : <span>{String(index + 1).padStart(2, '0')}</span>}
            </a>
            <div className="journal-meta"><span>{new Date(post.created_at).toLocaleDateString(currentLocale() === 'ru' ? 'ru-RU' : 'en-GB')}</span><span>{post.author?.name}</span></div>
            <h3><a href={`/posts/${post.slug}`}>{post.title}</a></h3>
            <p>{post.preview_text || t('home.postFallback')}</p>
            <a className="card-link" href={`/posts/${post.slug}`}>{t('home.readStory')} <span>→</span></a>
        </article>
    );
}

function HomeView() {
    const [content, setContent] = useState({ products: [], posts: [] });
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        apiRequest('/api/home')
            .then(setContent)
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => {
        if (loading) return undefined;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });

        document.querySelectorAll('[data-reveal]').forEach((element) => observer.observe(element));

        return () => observer.disconnect();
    }, [loading]);

    if (loading) return <PageLoader className="home-loading" markClassName="home-loading-mark">{t('home.loading')}</PageLoader>;

    return (
        <PublicLayout overlayHeader>
            <main>
                <section className="hero">
                    <div className="hero-copy" data-reveal><p className="eyebrow"><span />{t('home.fromSoil')}</p><h1>{t('home.title')}</h1><p className="hero-lead">{t('home.lead')}</p><div className="hero-actions"><a className="button button-light" href="/products">{t('home.explore')} <span>→</span></a><a className="text-link" href="#story">{t('home.discover')}</a></div></div>
                    <div className="hero-art" aria-hidden="true" data-reveal><div className="hero-orbit" /><div className="hero-bottle-glow" /><img className="hero-bottle" src="/images/hero-autumn-wine.png" alt="" /><span className="hero-note">27th Autumn · 2024</span></div>
                    <a className="scroll-cue" href="#story"><span>{t('home.scroll')}</span><i /></a>
                </section>
                <section id="story" className="story section-shell">
                    <div className="section-index" data-reveal>{t('home.storyIndex')}</div>
                    <div className="story-grid"><div className="story-heading" data-reveal><p className="eyebrow dark"><span />{t('home.beginning')}</p><h2>{t('home.storyTitle')}</h2></div><div className="story-copy" data-reveal><p className="story-intro">{t('home.storyIntro')}</p><p>{t('home.storyOne')}</p><p>{t('home.storyTwo')}</p><a className="arrow-link" href="/posts">{t('home.notes')} <span>→</span></a></div></div>
                    <div className="story-panels" data-reveal><article><span>01</span><h3>Resilient varieties</h3><p>Grapes selected for short summers, cold nights, and a distinct northern expression.</p></article><article><span>02</span><h3>Patient cellar</h3><p>Small batches and careful decisions that preserve the honest character of each harvest.</p></article><article><span>03</span><h3>Shared knowledge</h3><p>A living journal of experiments, growing notes, and lessons from every season.</p></article></div>
                </section>
                <section className="products-section"><div className="section-shell"><div className="section-topline" data-reveal><div><p className="eyebrow"><span />{t('home.productsKicker')}</p><h2>{t('home.productsTitle')}</h2></div><a className="arrow-link light" href="/products">{t('home.allProducts')} <span>→</span></a></div><div className="home-products" data-reveal>{content.products.length ? content.products.map((product) => <ProductCard key={product.id} product={product} />) : <div className="content-empty">{t('home.productsEmpty')}</div>}</div></div></section>
                <section className="journal section-shell"><div className="section-index" data-reveal>{t('home.journalIndex')}</div><div className="section-topline journal-heading" data-reveal><div><p className="eyebrow dark"><span />{t('home.fieldNotes')}</p><h2>{t('home.journalTitle')}</h2></div><p>{t('home.journalLead')}</p></div><div className="journal-grid" data-reveal>{content.posts.length ? content.posts.map((post, index) => <PostCard key={post.id} post={post} index={index} />) : <div className="content-empty journal-empty">{t('home.journalEmpty')}</div>}</div><div className="journal-action"><a className="button button-dark" href="/posts">{t('home.visitJournal')} <span>→</span></a></div></section>
            </main>
        </PublicLayout>
    );
}

createRoot(document.getElementById('home-root')).render(<HomeView />);
