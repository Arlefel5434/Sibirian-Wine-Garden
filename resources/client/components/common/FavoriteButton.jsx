import React from 'react';

export function FavoriteButton({ active = false, busy = false, onClick, className = '' }) {
    const label = active ? 'Remove from favourites' : 'Add to favourites';

    return <button className={`favorite-button ${active ? 'is-active' : ''} ${className}`} type="button" aria-label={label} aria-pressed={active} title={label} disabled={busy} onClick={onClick}>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.7a5.5 5.5 0 0 0-7.8 0L12 5.8l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.4 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z" /></svg>
    </button>;
}
