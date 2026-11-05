import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { Button, PageLoader } from '../../components/common';
import { PublicLayout } from '../../layouts/PublicLayout';
import { apiRequest, refreshSession } from '../../lib/api';
import '../../styles/common.css';

const money = (amount, currency) => new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(amount / 100);

function PaymentSimulationView({ paymentId }) {
    const [payment, setPayment] = useState(null);
    const [busy, setBusy] = useState(false);
    const [message, setMessage] = useState('');

    useEffect(() => { refreshSession().then(() => apiRequest(`/api/payments/${paymentId}`)).then(({ data }) => setPayment(data)).catch(() => window.location.assign('/login')); }, [paymentId]);

    async function simulate(outcome) {
        setBusy(true);
        setMessage('');
        try {
            const { data } = await apiRequest(`/api/payments/${payment.id}/simulate`, { method: 'POST', body: JSON.stringify({ outcome }) });
            setPayment(data);
            setMessage(`Test payment is now ${data.status}.`);
        } catch (error) {
            setMessage(error.message);
        } finally {
            setBusy(false);
        }
    }

    if (!payment) return <PageLoader>Opening the test gateway…</PageLoader>;

    return <PublicLayout><main className="commerce-shell"><section className="payment-simulator"><p className="eyebrow dark">Local payment gateway</p><h1>Test payment</h1><dl><div><dt>Order</dt><dd>#{payment.order_id}</dd></div><div><dt>Amount</dt><dd>{money(payment.amount, payment.currency)}</dd></div><div><dt>Status</dt><dd>{payment.status}</dd></div></dl>{payment.status === 'pending' && <div className="payment-simulation-actions"><Button disabled={busy} onClick={() => simulate('succeeded')}>Simulate success</Button><Button variant="secondary" disabled={busy} onClick={() => simulate('failed')}>Simulate failure</Button><Button variant="secondary" disabled={busy} onClick={() => simulate('canceled')}>Simulate cancellation</Button></div>}{message && <p className="checkout-message" role="status">{message}</p>}<a href="/orders">Return to orders →</a></section></main></PublicLayout>;
}

const root = document.getElementById('payment-simulation-root');
createRoot(root).render(<PaymentSimulationView paymentId={root.dataset.paymentId} />);
