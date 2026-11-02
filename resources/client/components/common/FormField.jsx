import React from 'react';

export function FormField({ label, name, error, hint, children }) {
    return (
        <div className="ui-field">
            <label htmlFor={name}>{label}</label>
            {children}
            {(error || hint) && <div className="ui-field-meta">{error ? <span className="ui-field-error">{error}</span> : <span />}{hint && <span>{hint}</span>}</div>}
        </div>
    );
}
