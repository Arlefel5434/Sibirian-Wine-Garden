import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { PageLoader } from '../../components/common';
import { PublicLayout } from '../../layouts/PublicLayout';
import { apiRequest } from '../../lib/api';
import '../../styles/common.css';

function ProductCard({ product }) {
    const sku = [...product.skus].sort((first, second) => first.price - second.price)[0];
    const price = sku ? new Intl.NumberFormat(undefined, { style: 'currency', currency: sku.currency }).format(sku.price / 100) : null;

    return (
        <a className="home-product" href={`/products/${product.slug}`}>
            <div className="product-visual"><span>{product.name.slice(0, 1).toUpperCase()}</span><i /></div>
            <div className="product-card-top"><span>{product.category?.name || 'Garden selection'}</span><span>↗</span></div>
            <h3>{product.name}</h3>
            <p>{product.description || 'A selection from the northern garden.'}</p>
            <div className="product-price">{price ? <><span>From</span> {price}</> : <span>Explore product</span>}</div>
        </a>
    );
}

function PostCard({ post, index }) {
    return (
        <article className="journal-card">
            <a className="journal-image" href={`/posts/${post.slug}`}>
                {post.preview_img ? <img src={post.preview_img} alt="" loading="lazy" /> : <span>{String(index + 1).padStart(2, '0')}</span>}
            </a>
            <div className="journal-meta"><span>{new Date(post.created_at).toLocaleDateString()}</span><span>{post.author?.name}</span></div>
            <h3><a href={`/posts/${post.slug}`}>{post.title}</a></h3>
            <p>{post.preview_text || 'A new story from the Siberian Wine Garden.'}</p>
            <a className="card-link" href={`/posts/${post.slug}`}>Read story <span>→</span></a>
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

    if (loading) return <PageLoader className="home-loading" markClassName="home-loading-mark">Opening the garden…</PageLoader>;

    return (
        <PublicLayout overlayHeader>
            <main>
                <section className="hero">
                    <div className="hero-copy" data-reveal><p className="eyebrow"><span />From northern soil</p><h1>Wine shaped<br />by <em>the cold.</em></h1><p className="hero-lead">A garden for resilient grapes, patient craft, and the rare character that only a northern season can create.</p><div className="hero-actions"><a className="button button-light" href="/products">Explore the collection <span>→</span></a><a className="text-link" href="#story">Discover our story</a></div></div>
                    <div className="hero-art" aria-hidden="true" data-reveal><div className="hero-orbit" /><div className="hero-bottle-glow" /><img className="hero-bottle" src="/images/hero-autumn-wine.png" alt="" /><span className="hero-note">27th Autumn · 2024</span></div>
                    <a className="scroll-cue" href="#story"><span>Scroll to explore</span><i /></a>
                </section>
                <section id="story" className="story section-shell">
                    <div className="section-index" data-reveal>01 / Our story</div>
                    <div className="story-grid"><div className="story-heading" data-reveal><p className="eyebrow dark"><span />The beginning</p><h2>A garden where winter is part of the craft.</h2></div><div className="story-copy" data-reveal><p className="story-intro">Siberian Wine Garden began as an exploration: can grapes do more than survive the northern climate?</p><p>The answer lives in every season. Long summer light builds character slowly. The first frost concentrates flavour. Winter asks the vines to become resilient, and spring begins the work again.</p><p>This project brings that cycle together — wines, grape varieties, vineyard tools, and practical stories from a garden that chooses patience over shortcuts.</p><a className="arrow-link" href="/posts">Read notes from the garden <span>→</span></a></div></div>
                    <div className="story-panels" data-reveal><article><span>01</span><h3>Resilient varieties</h3><p>Grapes selected for short summers, cold nights, and a distinct northern expression.</p></article><article><span>02</span><h3>Patient cellar</h3><p>Small batches and careful decisions that preserve the honest character of each harvest.</p></article><article><span>03</span><h3>Shared knowledge</h3><p>A living journal of experiments, growing notes, and lessons from every season.</p></article></div>
                </section>
                <section className="products-section"><div className="section-shell"><div className="section-topline" data-reveal><div><p className="eyebrow"><span />From the cellar &amp; garden</p><h2>Our products</h2></div><a className="arrow-link light" href="/products">View all products <span>→</span></a></div><div className="home-products" data-reveal>{content.products.length ? content.products.map((product) => <ProductCard key={product.id} product={product} />) : <div className="content-empty">The next garden selection is being prepared.</div>}</div></div></section>
                <section className="journal section-shell"><div className="section-index" data-reveal>03 / Journal</div><div className="section-topline journal-heading" data-reveal><div><p className="eyebrow dark"><span />Field notes</p><h2>Stories from<br />the north</h2></div><p>Seasonal observations, practical discoveries, and quiet moments from the vineyard and cellar.</p></div><div className="journal-grid" data-reveal>{content.posts.length ? content.posts.map((post, index) => <PostCard key={post.id} post={post} index={index} />) : <div className="content-empty journal-empty">The first field notes will appear here soon.</div>}</div><div className="journal-action"><a className="button button-dark" href="/posts">Visit the journal <span>→</span></a></div></section>
            </main>
        </PublicLayout>
    );
}

createRoot(document.getElementById('home-root')).render(<HomeView />);
