import React from 'react';
import { ProfileActionCard } from '../../components/profile/ProfileActionCard';
import { ProfileLogout } from '../../components/profile/ProfileLogout';

export function AdminProfileView({ user }) {
    return <main className="account-shell account-shell-admin"><p className="eyebrow dark">Administration</p><h1>Control centre</h1><p className="account-intro">Welcome, {user.name}. Review the marketplace, editorial work, and customers.</p><div className="account-sections admin-dashboard-sections"><ProfileActionCard title="Orders" description="Review every order, customer, amount, and payment state." href="/admin/orders" action="Open order table" /><ProfileActionCard title="Users" description="Review registered accounts, roles, contacts, and purchase activity." href="/admin/users" action="Open user table" /><ProfileActionCard title="Products" description="Create products, edit variants, prices, attributes, and availability." href="/products" action="Manage products" /><ProfileActionCard title="Journal" description="Create, publish, archive, and review all editorial content." href="/posts" action="Manage journal" /></div><ProfileLogout /></main>;
}
