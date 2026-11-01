import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';

let accessToken = null;

async function request(path, options = {}, retry = true) {
    const response = await fetch(path, {
        ...options,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            ...(accessToken ? { Authorization: `Bearer ${accessToken}` } : {}),
            ...options.headers,
        },
    });
    const payload = await response.json().catch(() => ({}));

    if (response.status === 401 && retry && !path.endsWith('/login') && !path.endsWith('/refresh')) {
        await refreshSession();

        return request(path, options, false);
    }

    if (!response.ok) {
        throw new Error(payload.message || 'Something went wrong. Please try again.');
    }

    return payload;
}

async function refreshSession() {
    const response = await fetch('/api/auth/refresh', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        },
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        accessToken = null;
        throw new Error(payload.message || 'Your session has expired. Please sign in again.');
    }

    accessToken = payload.access_token;

    return payload;
}

function VineyardMark() {
    return (
        <svg viewBox="0 0 64 64" aria-hidden="true" className="brand-mark">
            <path d="M31.7 12.4c5.1-7.2 13-7.4 18-5.8-1.2 7.2-6.9 12.7-15.6 12.5" />
            <path d="M31.8 15.2c-1.6 7.1-.6 11.9 1.7 16.3" />
            <circle cx="24" cy="29" r="7" />
            <circle cx="37" cy="29" r="7" />
            <circle cx="18" cy="40" r="7" />
            <circle cx="31" cy="41" r="7" />
            <circle cx="43" cy="40" r="7" />
            <circle cx="25" cy="52" r="7" />
            <circle cx="38" cy="51" r="7" />
        </svg>
    );
}

function EyeIcon({ open }) {
    return open ? (
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18M10.6 10.6a2 2 0 002.8 2.8M9.9 4.2A10.6 10.6 0 0112 4c5.5 0 9 5 9 5a16.8 16.8 0 01-2.5 2.8M6.2 6.2C4.2 7.5 3 9 3 9s3.5 5 9 5a10.4 10.4 0 004-.8" /></svg>
    ) : (
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12s3.5-5 9-5 9 5 9 5-3.5 5-9 5-9-5-9-5z" /><circle cx="12" cy="12" r="2.5" /></svg>
    );
}

function SignedIn({ user, onLogout }) {
    return (
        <main className="auth-shell">
            <section className="welcome-card" aria-labelledby="welcome-title">
                <div className="signed-in-mark"><VineyardMark /></div>
                <p className="eyebrow">Cellar access granted</p>
                <h1 id="welcome-title">Welcome back, {user.name}.</h1>
                <p className="welcome-copy">Your account is authenticated with a short-lived JWT access token.</p>
                <dl className="account-details">
                    <div><dt>Email</dt><dd>{user.email}</dd></div>
                    <div><dt>Role</dt><dd>{user.role || 'customer'}</dd></div>
                </dl>
                <div className="welcome-actions">
                    <a href="/" className="primary-link">Enter the garden</a>
                    <button type="button" className="text-button" onClick={onLogout}>Sign out</button>
                </div>
            </section>
        </main>
    );
}

function LoginPage() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [loading, setLoading] = useState(false);
    const [restoring, setRestoring] = useState(true);
    const [error, setError] = useState('');
    const [authenticated, setAuthenticated] = useState(false);
    const [user, setUser] = useState(null);

    useEffect(() => {
        refreshSession()
            .then((payload) => {
                setUser(payload.user);
                setAuthenticated(true);
            })
            .catch(() => setAuthenticated(false))
            .finally(() => setRestoring(false));
    }, []);

    async function handleSubmit(event) {
        event.preventDefault();
        setLoading(true);
        setError('');

        try {
            const payload = await request('/api/auth/login', {
                method: 'POST',
                body: JSON.stringify({ email, password }),
            });
            accessToken = payload.access_token;
            setAuthenticated(true);
            setUser(payload.user);
        } catch (loginError) {
            setError(loginError.message);
        } finally {
            setLoading(false);
        }
    }

    async function handleLogout() {
        try {
            await request('/api/auth/logout', {
                method: 'POST',
            }, false);
        } finally {
            accessToken = null;
            setAuthenticated(false);
            setUser(null);
            setPassword('');
        }
    }

    if (restoring) {
        return <main className="auth-shell"><div className="loading-card" role="status">Opening the cellar…</div></main>;
    }

    if (user && authenticated) {
        return <SignedIn user={user} onLogout={handleLogout} />;
    }

    return (
        <main className="auth-shell">
            <section className="story-panel" aria-label="Siberian Wine Garden">
                <div className="brand-lockup"><VineyardMark /><span>Siberian<br />Wine Garden</span></div>
                <div className="story-copy">
                    <p className="eyebrow">From northern soil</p>
                    <h1>A private path into the garden.</h1>
                    <p>Discover small-batch wines, resilient grape varieties, and the tools behind every harvest.</p>
                </div>
                <p className="vintage-note">Est. in Siberia · Crafted with patience</p>
            </section>

            <section className="form-panel" aria-labelledby="login-title">
                <div className="form-wrap">
                    <a href="/" className="mobile-brand" aria-label="Siberian Wine Garden home"><VineyardMark /></a>
                    <p className="eyebrow">Members' cellar</p>
                    <h2 id="login-title">Welcome back</h2>
                    <p className="form-intro">Sign in to continue to your account.</p>

                    <form onSubmit={handleSubmit} noValidate>
                        {error && <div className="error-message" role="alert">{error}</div>}

                        <label htmlFor="email">Email address</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            autoComplete="email"
                            value={email}
                            onChange={(event) => setEmail(event.target.value)}
                            placeholder="you@example.com"
                            required
                        />

                        <div className="label-row">
                            <label htmlFor="password">Password</label>
                            <a href="/forgot-password">Forgot password?</a>
                        </div>
                        <div className="password-field">
                            <input
                                id="password"
                                name="password"
                                type={showPassword ? 'text' : 'password'}
                                autoComplete="current-password"
                                value={password}
                                onChange={(event) => setPassword(event.target.value)}
                                placeholder="Enter your password"
                                required
                            />
                            <button type="button" onClick={() => setShowPassword((value) => !value)} aria-label={showPassword ? 'Hide password' : 'Show password'}>
                                <EyeIcon open={showPassword} />
                            </button>
                        </div>

                        <button className="submit-button" type="submit" disabled={loading}>
                            {loading ? 'Opening cellar…' : 'Sign in'}
                        </button>
                    </form>

                    <p className="register-copy">New to the garden? <a href="/register">Create an account</a></p>
                    <p className="secure-note"><span>✓</span> Secured with encrypted JWT authentication</p>
                </div>
            </section>
        </main>
    );
}

createRoot(document.getElementById('auth-root')).render(<LoginPage />);
