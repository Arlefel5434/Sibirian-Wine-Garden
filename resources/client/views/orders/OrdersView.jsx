import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { Button, PageLoader } from '../../components/common';
import { PublicLayout } from '../../layouts/PublicLayout';
import { apiRequest, refreshSession } from '../../lib/api';
import { announceWalletUpdated } from '../../lib/wallet';
import '../../styles/common.css';

const money = (amount, currency) => new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(amount / 100);

function OrdersView() {
    const [orders, setOrders] = useState(null);
    const [busy, setBusy] = useState(null);
    const [message, setMessage] = useState('');

    async function loadOrders() {
        const { data } = await apiRequest('/api/orders');
        setOrders(data);
    }

    useEffect(() => { refreshSession().then(loadOrders).catch(() => window.location.assign('/login')); }, []);

    async function pay(order, provider) {
        setBusy(`${order.id}-${provider}`);
        setMessage('');

        try {
            const { data } = await apiRequest(`/api/orders/${order.id}/payments`, { method: 'POST', body: JSON.stringify({ provider, idempotency_key: crypto.randomUUID() }) });
            if (data.confirmation_url) return window.location.assign(data.confirmation_url);
            setMessage(`Order #${order.id} was paid successfully.`);
            if (provider === 'wallet') {
                const { data: wallet } = await apiRequest('/api/wallet');
                announceWalletUpdated(wallet);
            }
            await loadOrders();
        } catch (error) {
            setMessage(Object.values(error.errors || {})[0]?.[0] || error.message);
        } finally {
            setBusy(null);
        }
    }

    if (!orders) return <PageLoader>Opening purchase history…</PageLoader>;

    return <PublicLayout><main className="commerce-shell"><header className="commerce-heading"><div><p className="eyebrow dark">Your account</p><h1>Purchase history</h1></div><a href="/profile">Back to profile →</a></header>{message && <p className="checkout-message payment-page-message" role="status">{message}</p>}{!orders.length ? <section className="commerce-empty"><h2>No orders yet.</h2><a href="/products">Browse products →</a></section> : <div className="orders-list">{orders.map((order) => <article className="order-card" key={order.id}><div className="order-top"><div><span className={`order-status order-status-${order.status}`}>{order.status}</span><h2>Order #{order.id}</h2></div><strong>{money(order.total_amount, order.currency)}</strong></div><ul className="order-items">{order.items.map((item) => <li key={item.id}><span>{item.product_name} · {item.variant_name} × {item.quantity}</span><strong>{money(item.price * item.quantity, item.currency)}</strong></li>)}</ul>{order.status === 'pending' && <div className="order-payment-actions"><Button disabled={Boolean(busy)} onClick={() => pay(order, 'wallet')}>{busy === `${order.id}-wallet` ? 'Paying…' : 'Pay from wallet'}</Button><Button variant="secondary" disabled={Boolean(busy) || order.payments.some((payment) => payment.status === 'pending')} onClick={() => pay(order, 'fake')}>{busy === `${order.id}-fake` ? 'Opening…' : 'Use test gateway'}</Button>{order.payments.find((payment) => payment.status === 'pending')?.confirmation_url && <a href={order.payments.find((payment) => payment.status === 'pending').confirmation_url}>Continue test payment →</a>}</div>}<small>{new Date(order.created_at).toLocaleString()}</small></article>)}</div>}</main></PublicLayout>;
}

createRoot(document.getElementById('orders-root')).render(<OrdersView />);
