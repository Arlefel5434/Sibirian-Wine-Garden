import React from 'react';
import { ProfileActionCard } from '../../components/profile/ProfileActionCard';
import { ProfileContactForm } from '../../components/profile/ProfileContactForm';
import { ProfileLogout } from '../../components/profile/ProfileLogout';
import { t } from '../../i18n';

export function CustomerProfileView({ user, onUpdated }) {
    return <main className="account-shell account-shell-customer"><p className="eyebrow dark">{t('Your garden account')}</p><h1>{t('Welcome')}, {user.name}.</h1><p className="account-intro">{t('Your details and your next discoveries, all in one place.')}</p><div className="account-sections"><ProfileActionCard title={t('Cart')} description={t('Your next selection starts here.')} href="/cart" action={t('Open cart')} /><ProfileActionCard title={t('Favourites')} description={t('Keep the wines and garden finds you love.')} href="/favorites" action={t('View favourites')} /><ProfileActionCard title={t('Wallet')} description={t('View your test balance and its complete transaction history.')} href="/wallet" action={t('Open wallet')} /><ProfileActionCard title={t('Purchase history')} description={t('A place for every past discovery.')} href="/orders" action={t('View orders')} /></div><ProfileContactForm user={user} onUpdated={onUpdated} /><ProfileLogout /></main>;
}
