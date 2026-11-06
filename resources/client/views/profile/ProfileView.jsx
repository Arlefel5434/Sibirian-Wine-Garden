import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { PageLoader } from '../../components/common';
import { PublicLayout } from '../../layouts/PublicLayout';
import { apiRequest, refreshSession } from '../../lib/api';
import { AdminProfileView } from './AdminProfileView';
import { ContentManagerProfileView } from './ContentManagerProfileView';
import { CustomerProfileView } from './CustomerProfileView';
import '../../styles/common.css';
import { t } from '../../i18n';

const profileViews = {
    admin: AdminProfileView,
    content_manager: ContentManagerProfileView,
    customer: CustomerProfileView,
};

function ProfileView() {
    const [user, setUser] = useState(null);
    const [failed, setFailed] = useState(false);

    useEffect(() => {
        refreshSession()
            .then(() => apiRequest('/api/auth/me'))
            .then(({ data }) => setUser(data))
            .catch(() => setFailed(true));
    }, []);

    if (failed) return <PublicLayout><main className="account-shell"><section className="account-panel"><h1>{t('Your account awaits')}</h1><p>{t('Please sign in to view your profile.')}</p><a href="/login">{t('Sign in →')}</a></section></main></PublicLayout>;
    if (!user) return <PageLoader>{t('Opening your account…')}</PageLoader>;

    const RoleProfileView = profileViews[user.role] || CustomerProfileView;

    return <PublicLayout><RoleProfileView user={user} onUpdated={setUser} /></PublicLayout>;
}

createRoot(document.getElementById('profile-root')).render(<ProfileView />);
