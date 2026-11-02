import React from 'react';

export function Notice({ children, onClose, className = '' }) {
    if (!children) {
        return null;
    }

    return <div className={`ui-notice ${className}`.trim()} role="status"><span>{children}</span>{onClose && <button type="button" onClick={onClose} aria-label="Close notification">×</button>}</div>;
}
