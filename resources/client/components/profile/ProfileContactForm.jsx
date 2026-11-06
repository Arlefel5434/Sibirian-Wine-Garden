import React, { useState } from 'react';
import { parsePhoneNumberFromString } from 'libphonenumber-js/max';
import { Button, FormField, Input } from '../common';
import { PhoneInput } from '../common/PhoneInput';
import { apiRequest } from '../../lib/api';

export function ProfileContactForm({ user, onUpdated }) {
    const savedPhone = parsePhoneNumberFromString(user.phone_number || '');
    const [email, setEmail] = useState(user.email);
    const [phone, setPhone] = useState(savedPhone?.formatNational() || '');
    const [country, setCountry] = useState(savedPhone?.country || '');
    const [errors, setErrors] = useState({});
    const [message, setMessage] = useState('');
    const [saving, setSaving] = useState(false);
    const emailError = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) && email.length <= 255 ? '' : 'Enter a valid email address.';
    const parsedPhone = country && phone ? parsePhoneNumberFromString(phone, country) : null;
    const phoneError = phone && (!parsedPhone?.isValid() || parsedPhone.country !== country) ? 'Enter a valid phone number for the selected country.' : '';

    async function save(event) {
        event.preventDefault();
        if (emailError || phoneError) return;
        setSaving(true);
        setErrors({});
        setMessage('');

        try {
            const { data } = await apiRequest('/api/profile', { method: 'PATCH', body: JSON.stringify({ email, phone_number: parsedPhone?.number || null }) });
            onUpdated(data);
            setMessage('Your contact details have been saved.');
        } catch (error) {
            setErrors(error.errors || {});
            setMessage(error.message);
        } finally {
            setSaving(false);
        }
    }

    return <section className="account-panel account-contact-panel"><h2>Contact details</h2><form onSubmit={save}><FormField name="email" label="Email address" error={emailError || errors.email?.[0]}><Input id="email" type="email" autoComplete="email" required aria-invalid={Boolean(emailError)} value={email} onChange={(event) => { setEmail(event.target.value); setErrors({}); setMessage(''); }} /></FormField><PhoneInput country={country} onCountryChange={(value) => { setCountry(value); setPhone(''); setErrors({}); setMessage(''); }} value={phone} onChange={(value) => { setPhone(value); setErrors({}); setMessage(''); }} error={phoneError || errors.phone_number?.[0]} /><div className="account-actions"><Button type="submit" disabled={saving || Boolean(emailError || phoneError)}>{saving ? 'Saving…' : 'Save changes'}</Button><span role="status">{message}</span></div></form></section>;
}
