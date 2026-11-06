import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { Button, Notice, PageLoader, Select } from '../../components/common';
import { PublicLayout } from '../../layouts/PublicLayout';
import { apiRequest, refreshSession } from '../../lib/api';
import '../../styles/common.css';

const roleLabel = (role) => ({ admin: 'Administrator', content_manager: 'Content manager', customer: 'Customer' }[role] || role);

function AdminUsersView() {
    const [users, setUsers] = useState(null);
    const [currentUserId, setCurrentUserId] = useState(null);
    const [busyUserId, setBusyUserId] = useState(null);
    const [error, setError] = useState('');
    const [notice, setNotice] = useState('');

    useEffect(() => {
        refreshSession()
            .then(({ user }) => {
                if (user.role !== 'admin') {
                    throw new Error('Administrator access is required.');
                }

                setCurrentUserId(user.id);

                return apiRequest('/api/admin/users');
            })
            .then(({ data }) => setUsers(data))
            .catch((requestError) => setError(requestError.message));
    }, []);

    async function updateUser(user, changes, successMessage) {
        setBusyUserId(user.id);
        setNotice('');

        try {
            const { data } = await apiRequest(`/api/admin/users/${user.id}`, {
                method: 'PATCH',
                body: JSON.stringify(changes),
            });
            setUsers((current) => current.map((item) => item.id === data.id ? data : item));
            setNotice(successMessage);
        } catch (requestError) {
            setNotice(requestError.message);
        } finally {
            setBusyUserId(null);
        }
    }

    function changeRole(user, role) {
        if (role === user.role) {
            return;
        }

        if (!window.confirm(`Change ${user.name}’s role to ${roleLabel(role)}?`)) {
            return;
        }

        updateUser(user, { role }, `${user.name} is now ${roleLabel(role)}.`);
    }

    function toggleBan(user) {
        if (user.is_banned) {
            if (window.confirm(`Unblock ${user.name}?`)) {
                updateUser(user, { banned: false }, `${user.name} has been unblocked.`);
            }

            return;
        }

        const reason = window.prompt(`Why should ${user.name} be blocked? Leave empty if no reason is needed.`);

        if (reason !== null) {
            updateUser(user, { banned: true, ban_reason: reason || null }, `${user.name} has been blocked.`);
        }
    }

    if (error) {
        return <PublicLayout><main className="admin-table-shell"><section className="commerce-empty"><h1>Access unavailable</h1><p>{error}</p><a href="/profile">Return to profile →</a></section></main></PublicLayout>;
    }

    if (!users) {
        return <PageLoader>Opening the user table…</PageLoader>;
    }

    return (
        <PublicLayout>
            <main className="admin-table-shell">
                <header className="commerce-heading">
                    <div><p className="eyebrow dark">Administration</p><h1>Users</h1><p>Manage account roles, access, contact details, and order activity.</p></div>
                    <a href="/profile">Back to profile →</a>
                </header>
                <Notice onClose={() => setNotice('')}>{notice}</Notice>
                <div className="admin-table-scroll">
                    <table className="admin-table">
                        <thead><tr><th>ID</th><th>User</th><th>Role</th><th>Access</th><th>Phone</th><th>Orders</th><th>Registered</th></tr></thead>
                        <tbody>{users.map((user) => {
                            const isCurrentUser = user.id === currentUserId;
                            const isBusy = busyUserId === user.id;

                            return (
                                <tr key={user.id} className={user.is_banned ? 'admin-user-banned' : ''}>
                                    <td>#{user.id}</td>
                                    <td><strong>{user.name}</strong><span>{user.email}</span>{isCurrentUser && <span>Current account</span>}</td>
                                    <td>
                                        <Select value={user.role} disabled={isCurrentUser || isBusy} onChange={(event) => changeRole(user, event.target.value)} aria-label={`Role for ${user.name}`}>
                                            <option value="admin">Administrator</option>
                                            <option value="content_manager">Content manager</option>
                                            <option value="customer">Customer</option>
                                        </Select>
                                    </td>
                                    <td>
                                        <span className={`admin-access-status ${user.is_banned ? 'is-banned' : 'is-active'}`}>{user.is_banned ? 'Blocked' : 'Active'}</span>
                                        {user.ban_reason && <span title={user.ban_reason}>{user.ban_reason}</span>}
                                        <Button variant="secondary" className={user.is_banned ? '' : 'admin-ban-button'} disabled={isCurrentUser || isBusy} onClick={() => toggleBan(user)}>{isBusy ? 'Saving…' : user.is_banned ? 'Unblock' : 'Block'}</Button>
                                    </td>
                                    <td>{user.phone_number || 'Not provided'}</td>
                                    <td>{user.orders_count}</td>
                                    <td>{new Date(user.created_at).toLocaleString()}</td>
                                </tr>
                            );
                        })}</tbody>
                    </table>
                </div>
                {!users.length && <section className="commerce-empty"><h2>No users yet.</h2></section>}
            </main>
        </PublicLayout>
    );
}

createRoot(document.getElementById('admin-users-root')).render(<AdminUsersView />);
