import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { PageLoader } from '../../components/common';
import { PublicLayout } from '../../layouts/PublicLayout';
import { apiRequest, refreshSession } from '../../lib/api';
import '../../styles/common.css';

const money = (amount, currency) => new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(amount / 100);

function OrdersView() {
    const [orders, setOrders] = useState(null);
    useEffect(() => { refreshSession().then(() => apiRequest('/api/orders')).then(({ data }) => setOrders(data)).catch(() => window.location.assign('/login')); }, []);
    if (!orders) return <PageLoader>Opening purchase history…</PageLoader>;
    return <PublicLayout><main className="commerce-shell"><header className="commerce-heading"><div><p className="eyebrow dark">Your account</p><h1>Purchase history</h1></div><a href="/profile">Back to profile →</a></header>{!orders.length ? <section className="commerce-empty"><h2>No orders yet.</h2><a href="/products">Browse products →</a></section> : <div className="orders-list">{orders.map((order) => <article className="order-card" key={order.id}><div className="order-top"><div><span className="order-status">{order.status}</span><h2>Order #{order.id}</h2></div><strong>{money(order.total_amount, order.currency)}</strong></div><ul className="order-items">{order.items.map((item) => <li key={item.id}><span>{item.product_name} · {item.variant_name} × {item.quantity}</span><strong>{money(item.price * item.quantity, item.currency)}</strong></li>)}</ul><small>{new Date(order.created_at).toLocaleString()}</small></article>)}</div>}</main></PublicLayout>;
}
createRoot(document.getElementById('orders-root')).render(<OrdersView />);
