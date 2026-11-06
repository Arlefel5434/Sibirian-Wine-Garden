import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { PageLoader } from '../../components/common';
import { PublicLayout } from '../../layouts/PublicLayout';
import { apiRequest, refreshSession } from '../../lib/api';
import '../../styles/common.css';

const money = (amount, currency) => new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(amount / 100);

function AdminOrdersView() {
    const [orders, setOrders] = useState(null);
    const [error, setError] = useState('');

    useEffect(() => {
        refreshSession()
            .then(({ user }) => user.role === 'admin' ? apiRequest('/api/admin/orders') : Promise.reject(new Error('Administrator access is required.')))
            .then(({ data }) => setOrders(data))
            .catch((requestError) => setError(requestError.message));
    }, []);

    if (error) return <PublicLayout><main className="admin-table-shell"><section className="commerce-empty"><h1>Access unavailable</h1><p>{error}</p><a href="/profile">Return to profile →</a></section></main></PublicLayout>;
    if (!orders) return <PageLoader>Opening the order table…</PageLoader>;

    return <PublicLayout><main className="admin-table-shell"><header className="commerce-heading"><div><p className="eyebrow dark">Administration</p><h1>Orders</h1><p>All marketplace orders and their current payment state.</p></div><a href="/profile">Back to profile →</a></header><div className="admin-table-scroll"><table className="admin-table"><thead><tr><th>Order</th><th>Customer</th><th>Status</th><th>Payment</th><th>Positions</th><th>Total</th><th>Created</th></tr></thead><tbody>{orders.map((order) => <tr key={order.id}><td>#{order.id}</td><td><strong>{order.customer.name}</strong><span>{order.customer.email}</span></td><td><span className={`order-status order-status-${order.status}`}>{order.status}</span></td><td>{order.payment_status || 'Not started'}</td><td>{order.items_count}</td><td>{money(order.total_amount, order.currency)}</td><td>{new Date(order.created_at).toLocaleString()}</td></tr>)}</tbody></table></div>{!orders.length && <section className="commerce-empty"><h2>No orders yet.</h2></section>}</main></PublicLayout>;
}

createRoot(document.getElementById('admin-orders-root')).render(<AdminOrdersView />);
