import React from 'react';
import { ProfileActionCard } from '../../components/profile/ProfileActionCard';
import { ProfileContactForm } from '../../components/profile/ProfileContactForm';
import { ProfileLogout } from '../../components/profile/ProfileLogout';

export function ContentManagerProfileView({ user, onUpdated }) {
    return <main className="account-shell account-shell-content"><p className="eyebrow dark">Editorial workspace</p><h1>Content studio</h1><p className="account-intro">Welcome, {user.name}. Shape the journal and prepare stories for readers.</p><section className="profile-workspace-section"><h2>Journal</h2><div className="account-sections content-dashboard-sections"><ProfileActionCard title="Journal studio" description="Review drafts, published stories, and archived material." href="/posts" action="Open journal" /><ProfileActionCard title="Create a story" description="Start a new field note, harvest story, or wine culture article." href="/posts?create=1" action="Write story" /></div></section><section className="profile-workspace-section"><h2>Products</h2><div className="account-sections content-dashboard-sections"><ProfileActionCard title="Product catalogue" description="Review active products, drafts, variants, prices, and availability." href="/products" action="Open products" /><ProfileActionCard title="Add a product" description="Create a new product with variants, stock, pricing, and characteristics." href="/products?create=1" action="Add product" /></div></section><ProfileContactForm user={user} onUpdated={onUpdated} /><ProfileLogout /></main>;
}
