import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { Button, FormField, Input, PageLoader } from '../../components/common';
import { PublicLayout } from '../../layouts/PublicLayout';
import { apiRequest } from '../../lib/api';
import '../../styles/common.css';
import { PhoneInput } from '../../components/common/PhoneInput';
import { parsePhoneNumberFromString } from 'libphonenumber-js/max';

function ProfileView() {
    const [user, setUser] = useState(null);
    const [email, setEmail] = useState('');
    const [phone, setPhone] = useState('');
    const [country, setCountry] = useState('');
    const emailError = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) && email.length <= 255 ? '' : 'Enter a valid email address.';
    const parsedPhone = country && phone ? parsePhoneNumberFromString(phone, country) : null;
    const phoneError = phone && (!parsedPhone?.isValid() || parsedPhone.country !== country) ? 'Enter a valid phone number for the selected country.' : '';
    const [errors, setErrors] = useState({});
    const [message, setMessage] = useState('');
    const [saving, setSaving] = useState(false);
    const [failed, setFailed] = useState(false);

    useEffect(() => {
        apiRequest('/api/auth/me').then(({ data }) => {
            const savedPhone = parsePhoneNumberFromString(data.phone_number || '');
            setUser(data);
            setEmail(data.email);
            setPhone(savedPhone?.formatNational() || '');
            setCountry(savedPhone?.country || '');
        }).catch(() => setFailed(true));
    }, []);

    async function save(event) {
        event.preventDefault();
        if (emailError || phoneError) return;
        setSaving(true);
        setErrors({});
        setMessage('');
        try {
            const { data } = await apiRequest('/api/profile', { method: 'PATCH', body: JSON.stringify({ email, phone_number: parsedPhone?.number || null }) });
            setUser(data);
            setMessage('Your contact details have been saved.');
        } catch (error) {
            setErrors(error.errors || {});
            setMessage(error.message);
        } finally {
            setSaving(false);
        }
    }

    return <PublicLayout><main className="account-shell">
        {failed ? <section className="account-panel"><h1>Your account awaits</h1><p>Please sign in to view and edit your profile.</p><a href="/login">Sign in →</a></section> : !user ? <PageLoader>Opening your account…</PageLoader> : <>
            <p className="eyebrow dark">Your garden account</p><h1>Welcome, {user.name}.</h1><p className="account-intro">Your details and your next discoveries, all in one place.</p>
            <div className="account-sections"><section className="account-panel"><h2>Cart</h2><p>Your next selection starts here.</p><a className="ui-button ui-button-secondary" href="/cart">Open cart</a></section><section className="account-panel"><h2>Favourites</h2><p>Keep the wines and garden finds you love.</p><Button variant="secondary" disabled>Coming soon</Button></section><section className="account-panel"><h2>Purchase history</h2><p>A place for every past discovery.</p><a className="ui-button ui-button-secondary" href="/orders">View orders</a></section></div>
            <section className="account-panel"><h2>Contact details</h2><form onSubmit={save}>
                <FormField name="email" label="Email address" error={emailError || errors.email?.[0]}><Input id="email" type="email" autoComplete="email" required aria-invalid={Boolean(emailError)} value={email} onChange={(event) => { setEmail(event.target.value); setErrors({}); setMessage(''); }} /></FormField>
                <PhoneInput country={country} onCountryChange={(value) => { setCountry(value); setPhone(''); setErrors({}); setMessage(''); }} value={phone} onChange={(value) => { setPhone(value); setErrors({}); setMessage(''); }} error={phoneError || errors.phone_number?.[0]} />
                <div className="account-actions"><Button type="submit" disabled={saving || Boolean(emailError || phoneError)}>{saving ? 'Saving…' : 'Save changes'}</Button><span role="status">{message}</span></div>
            </form></section>
        </>}
    </main></PublicLayout>;
}

createRoot(document.getElementById('profile-root')).render(<ProfileView />);
