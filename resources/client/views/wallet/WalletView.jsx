import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { Button, FormField, Input, PageLoader, Select } from '../../components/common';
import { PublicLayout } from '../../layouts/PublicLayout';
import { apiRequest, refreshSession } from '../../lib/api';
import '../../styles/common.css';

const money = (amount, currency) => new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(amount / 100);

function WalletView() {
    const [wallet, setWallet] = useState(null);
    const [user, setUser] = useState(null);
    const [form, setForm] = useState({ user_id: '', direction: 'credit', amount: '', reason: '' });
    const [message, setMessage] = useState('');
    const [busy, setBusy] = useState(false);

    async function loadWallet() {
        const { data } = await apiRequest('/api/wallet');
        setWallet(data);
    }

    useEffect(() => { refreshSession().then(({ user: sessionUser }) => { setUser(sessionUser); return loadWallet(); }).catch(() => window.location.assign('/login?redirect=/wallet')); }, []);

    async function adjust(event) {
        event.preventDefault();
        setBusy(true);
        setMessage('');
        try {
            await apiRequest(`/api/admin/users/${form.user_id}/wallet/adjustments`, { method: 'POST', body: JSON.stringify({ direction: form.direction, amount: Math.round(Number(form.amount) * 100), reason: form.reason }) });
            setMessage('Wallet adjustment recorded.');
            setForm({ user_id: '', direction: 'credit', amount: '', reason: '' });
        } catch (error) {
            setMessage(Object.values(error.errors || {})[0]?.[0] || error.message);
        } finally {
            setBusy(false);
        }
    }

    if (!wallet) return <PageLoader>Opening your wallet…</PageLoader>;

    return <PublicLayout><main className="commerce-shell"><header className="commerce-heading"><div><p className="eyebrow dark">Internal balance</p><h1>Your wallet</h1><p>Every balance change is recorded in an immutable ledger.</p></div><a href="/orders">View orders →</a></header><section className="wallet-balance"><span>Available balance</span><strong>{money(wallet.balance, wallet.currency)}</strong></section><section className="wallet-history"><h2>Transaction history</h2>{wallet.transactions.length ? <div>{wallet.transactions.map((transaction) => <article key={transaction.id}><div><strong>{transaction.reason}</strong><small>{new Date(transaction.created_at).toLocaleString()}</small></div><span className={transaction.balance_after >= transaction.balance_before ? 'wallet-credit' : 'wallet-debit'}>{transaction.balance_after >= transaction.balance_before ? '+' : '-'}{money(transaction.amount, wallet.currency)}</span></article>)}</div> : <p>No transactions yet.</p>}</section>{user?.role === 'admin' && <form className="wallet-admin" onSubmit={adjust}><h2>Admin adjustment</h2><p>Adjustments append a ledger entry and never overwrite history.</p><FormField name="user_id" label="Customer ID"><Input id="user_id" type="number" min="1" required value={form.user_id} onChange={(event) => setForm((current) => ({ ...current, user_id: event.target.value }))} /></FormField><FormField name="direction" label="Direction"><Select id="direction" value={form.direction} onChange={(event) => setForm((current) => ({ ...current, direction: event.target.value }))}><option value="credit">Credit</option><option value="debit">Debit</option></Select></FormField><FormField name="amount" label="Amount in EUR"><Input id="amount" type="number" min="0.01" step="0.01" required value={form.amount} onChange={(event) => setForm((current) => ({ ...current, amount: event.target.value }))} /></FormField><FormField name="reason" label="Reason"><Input id="reason" required maxLength="255" value={form.reason} onChange={(event) => setForm((current) => ({ ...current, reason: event.target.value }))} /></FormField>{message && <p className="checkout-message">{message}</p>}<Button type="submit" disabled={busy}>{busy ? 'Recording…' : 'Record adjustment'}</Button></form>}</main></PublicLayout>;
}

createRoot(document.getElementById('wallet-root')).render(<WalletView />);
