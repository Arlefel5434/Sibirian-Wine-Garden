import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { PasswordField } from '../../components/auth';
import { BrandMark } from '../../components/common';
import { PublicLayout } from '../../layouts/PublicLayout';
import { apiRequest, refreshSession } from '../../lib/api';
import '../../styles/common.css';
import { t } from '../../i18n';

function RegisterPage() {
    const [form, setForm] = useState({ name: '', email: '', password: '', password_confirmation: '' });
    const [errors, setErrors] = useState({});
    const [loading, setLoading] = useState(false);
    const [restoring, setRestoring] = useState(true);
    const [message, setMessage] = useState('');
    const [registeredEmail, setRegisteredEmail] = useState('');
    const [resending, setResending] = useState(false);
    const [passwordsVisible, setPasswordsVisible] = useState(false);

    useEffect(() => {
        refreshSession()
            .then(() => window.location.replace('/profile'))
            .catch(() => null)
            .finally(() => setRestoring(false));
    }, []);

    function update(field, value) {
        setForm((current) => ({ ...current, [field]: value }));
        setErrors((current) => ({ ...current, [field]: undefined }));
        setMessage('');
    }

    async function handleSubmit(event) {
        event.preventDefault();
        setLoading(true);
        setErrors({});
        setMessage('');

        try {
            const payload = await apiRequest('/api/auth/register', {
                method: 'POST',
                body: JSON.stringify(form),
            });
            setRegisteredEmail(payload.email);
            setMessage(payload.message);
        } catch (registrationError) {
            setErrors(registrationError.errors || {});
            setMessage(registrationError.errors ? '' : registrationError.message);
        } finally {
            setLoading(false);
        }
    }

    async function resendVerification() {
        setResending(true);

        try {
            const payload = await apiRequest('/api/auth/email/verification-notification', {
                method: 'POST',
                body: JSON.stringify({ email: registeredEmail }),
            });
            setMessage(payload.message);
        } catch (resendError) {
            setMessage(resendError.message);
        } finally {
            setResending(false);
        }
    }

    if (restoring) {
        return <PublicLayout><main className="auth-shell"><div className="loading-card" role="status">{t('Preparing registration…')}</div></main></PublicLayout>;
    }

    return (
        <PublicLayout>
            <main className="auth-shell">
                <section className="story-panel" aria-label="Siberian Wine Garden">
                    <div className="brand-lockup"><BrandMark className="brand-mark" /><span>Siberian<br />Wine Garden</span></div>
                    <div className="story-copy">
                        <p className="eyebrow">{t('Join the garden')}</p>
                        <h1>{t('Your cellar begins with a single vine.')}</h1>
                        <p>{t('Create an account to collect favourites, build orders, and follow stories from the northern vineyard.')}</p>
                    </div>
                    <p className="vintage-note">{t('A marketplace rooted in Siberia')}</p>
                </section>

                <section className="form-panel" aria-labelledby="register-title">
                    <div className="form-wrap">
                        <a href="/" className="mobile-brand" aria-label="Siberian Wine Garden home"><BrandMark className="brand-mark" /></a>
                        <p className="eyebrow">{t('Create your account')}</p>
                        <h2 id="register-title">{t('Enter the garden')}</h2>
                        <p className="form-intro">{t('Your account will be ready after you confirm your email address.')}</p>

                        {registeredEmail ? (
                            <section className="registration-success" role="status">
                                <span className="registration-success-mark">✓</span>
                                <h3>{t('Check your email')}</h3>
                                <p>{message}</p>
                                <strong>{registeredEmail}</strong>
                                <p>{t('The confirmation link is valid for 60 minutes.')}</p>
                                <a className="submit-link" href="/login">{t('Continue to sign in')}</a>
                                <button className="resend-button" type="button" disabled={resending} onClick={resendVerification}>{t(resending ? 'Sending…' : 'Send a new link')}</button>
                            </section>
                        ) : (
                            <form className="registration-form" onSubmit={handleSubmit} noValidate>
                                {message && <div className="error-message" role="alert">{message}</div>}

                                <div className="auth-field">
                                    <label htmlFor="name">{t('Full name')}</label>
                                    <input id="name" name="name" type="text" autoComplete="name" value={form.name} onChange={(event) => update('name', event.target.value)} aria-invalid={Boolean(errors.name)} required />
                                    {errors.name?.[0] && <span className="field-error" role="alert">{errors.name[0]}</span>}
                                </div>

                                <div className="auth-field">
                                    <label htmlFor="email">{t('Email address')}</label>
                                    <input id="email" name="email" type="email" autoComplete="email" value={form.email} onChange={(event) => update('email', event.target.value)} aria-invalid={Boolean(errors.email)} placeholder="you@example.com" required />
                                    {errors.email?.[0] && <span className="field-error" role="alert">{errors.email[0]}</span>}
                                </div>

                                <PasswordField id="password" label={t('Password')} autoComplete="new-password" value={form.password} onChange={(event) => update('password', event.target.value)} error={errors.password?.[0]} visible={passwordsVisible} onVisibilityChange={setPasswordsVisible} required />
                                <p className="password-hint">{t('Use at least 12 characters with letters and numbers.')}</p>
                                <PasswordField id="password_confirmation" label={t('Confirm password')} autoComplete="new-password" value={form.password_confirmation} onChange={(event) => update('password_confirmation', event.target.value)} error={errors.password_confirmation?.[0]} visible={passwordsVisible} onVisibilityChange={setPasswordsVisible} required />

                                <button className="submit-button" type="submit" disabled={loading}>{t(loading ? 'Creating account…' : 'Create account')}</button>
                            </form>
                        )}

                        {!registeredEmail && <p className="register-copy">{t('Already have an account?')} <a href="/login">{t('Sign in')}</a></p>}
                        <p className="secure-note"><span>✓</span> {t('Your password is securely hashed')}</p>
                    </div>
                </section>
            </main>
        </PublicLayout>
    );
}

createRoot(document.getElementById('auth-root')).render(<RegisterPage />);
