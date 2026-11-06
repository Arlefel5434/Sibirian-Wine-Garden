import React, { useState } from 'react';
import { Button } from '../common';
import { apiRequest, clearAccessToken } from '../../lib/api';

export function ProfileLogout() {
    const [busy, setBusy] = useState(false);

    async function logout() {
        setBusy(true);

        try {
            await apiRequest('/api/auth/logout', { method: 'POST' }, false);
        } finally {
            clearAccessToken();
            window.location.assign('/login');
        }
    }

    return <section className="profile-logout"><div><h2>Finish your session</h2><p>Sign out from this browser when you are finished.</p></div><Button variant="secondary" disabled={busy} onClick={logout}>{busy ? 'Signing out…' : 'Sign out'}</Button></section>;
}
