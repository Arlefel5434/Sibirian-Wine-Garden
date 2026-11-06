import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { Button, FormField, Input, PageLoader } from '../../components/common';
import { PublicLayout } from '../../layouts/PublicLayout';
import { apiRequest, refreshSession } from '../../lib/api';
import { announceCartUpdated } from '../../lib/cart';
import '../../styles/common.css';
import { formatMoney, t } from '../../i18n';

const money = formatMoney;

function CartView() {
    const [cart, setCart] = useState(null);
    const [message, setMessage] = useState('');
    const [busy, setBusy] = useState(false);
    const [delivery, setDelivery] = useState({ recipient_name: '', country_code: '', city: '', postal_code: '', address_line: '' });

    useEffect(() => { refreshSession().then(() => apiRequest('/api/cart')).then(({ data }) => setCart(data)).catch(() => window.location.assign('/login')); }, []);
    const changeDelivery = (event) => setDelivery((current) => ({ ...current, [event.target.name]: event.target.value }));

    async function updateItem(item, quantity) {
        if (quantity < 1 || quantity > 99) return;
        try { const { data } = await apiRequest(`/api/cart/items/${item.id}`, { method: 'PATCH', body: JSON.stringify({ quantity }) }); setCart(data); announceCartUpdated(data.items_count); setMessage(''); } catch (error) { setMessage(error.message); }
    }

    async function removeItem(item) {
        const { data } = await apiRequest(`/api/cart/items/${item.id}`, { method: 'DELETE' }); setCart(data); announceCartUpdated(data.items_count);
    }

    async function checkout(event) {
        event.preventDefault(); setBusy(true); setMessage('');
        try { const { data } = await apiRequest('/api/checkout', { method: 'POST', body: JSON.stringify({ ...delivery, country_code: delivery.country_code.toUpperCase() }) }); window.location.assign(`/orders?created=${data.id}`); }
        catch (error) { setMessage(Object.values(error.errors || {})[0]?.[0] || error.message); setBusy(false); }
    }

    if (!cart) return <PageLoader>{t('Opening your cart…')}</PageLoader>;
    return <PublicLayout><main className="commerce-shell"><header className="commerce-heading"><div><p className="eyebrow dark">{t('Your selection')}</p><h1>{t('Shopping cart')}</h1></div><a href="/products">{t('Continue shopping →')}</a></header>
        {!cart.items.length ? <section className="commerce-empty"><h2>{t('Your cart is empty.')}</h2><p>{t('Explore the cellar and garden to begin your selection.')}</p><a href="/products">{t('Browse products →')}</a></section> : <div className="cart-layout"><section className="cart-list">{cart.items.map((item) => <article className="cart-item" key={item.id}><div><h2>{item.product.name}</h2><p>{item.sku.name} · {money(item.sku.price, item.sku.currency)}</p></div><div className="cart-item-actions"><Input aria-label={`${item.product.name}: ${item.quantity}`} type="number" min="1" max="99" value={item.quantity} onChange={(event) => updateItem(item, Number(event.target.value))} /><button className="cart-remove" type="button" onClick={() => removeItem(item)}>{t('Remove')}</button></div><strong className="cart-line-price">{money(item.line_total, item.sku.currency)}</strong></article>)}</section>
            <form className="checkout-card" onSubmit={checkout}><h2>{t('Delivery details')}</h2>{[['recipient_name','Recipient name'],['country_code','Country code'],['city','City'],['postal_code','Postal code'],['address_line','Address']].map(([name,label]) => <FormField key={name} name={name} label={t(label)}><Input id={name} value={delivery[name]} required maxLength={name === 'country_code' ? 2 : 255} onChange={changeDelivery} /></FormField>)}<div className="checkout-total"><span>{t('Total')}</span><strong>{money(cart.total_amount, cart.currency)}</strong></div>{message && <p className="checkout-message">{message}</p>}<Button type="submit" disabled={busy}>{t(busy ? 'Placing order…' : 'Place order')}</Button></form></div>}
    </main></PublicLayout>;
}
createRoot(document.getElementById('cart-root')).render(<CartView />);
