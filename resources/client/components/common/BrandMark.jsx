import React from 'react';

export function BrandMark({ className = '' }) {
    return (
        <svg viewBox="0 0 64 64" aria-hidden="true" className={className}>
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
