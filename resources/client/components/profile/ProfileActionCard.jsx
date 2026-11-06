import React from 'react';

export function ProfileActionCard({ title, description, href, action }) {
    return <section className="account-panel"><h2>{title}</h2><p>{description}</p><a className="ui-button ui-button-secondary" href={href}>{action}</a></section>;
}
