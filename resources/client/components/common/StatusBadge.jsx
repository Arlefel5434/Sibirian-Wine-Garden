import React from 'react';

export function StatusBadge({ status, className = '' }) {
    return <span className={`ui-status ui-status-${status} ${className}`.trim()}>{status}</span>;
}
