import React from 'react';
import { ProfileActionCard } from '../../components/profile/ProfileActionCard';
import { ProfileContactForm } from '../../components/profile/ProfileContactForm';
import { ProfileLogout } from '../../components/profile/ProfileLogout';

export function CustomerProfileView({ user, onUpdated }) {
    return <main className="account-shell account-shell-customer"><p className="eyebrow dark">Your garden account</p><h1>Welcome, {user.name}.</h1><p className="account-intro">Your details and your next discoveries, all in one place.</p><div className="account-sections"><ProfileActionCard title="Cart" description="Your next selection starts here." href="/cart" action="Open cart" /><ProfileActionCard title="Favourites" description="Keep the wines and garden finds you love." href="/favorites" action="View favourites" /><ProfileActionCard title="Wallet" description="View your test balance and its complete transaction history." href="/wallet" action="Open wallet" /><ProfileActionCard title="Purchase history" description="A place for every past discovery." href="/orders" action="View orders" /></div><ProfileContactForm user={user} onUpdated={onUpdated} /><ProfileLogout /></main>;
}
