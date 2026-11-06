import React, { useState } from 'react';

function EyeIcon({ open }) {
    return open ? (
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18M10.6 10.6a2 2 0 002.8 2.8M9.9 4.2A10.6 10.6 0 0112 4c5.5 0 9 5 9 5a16.8 16.8 0 01-2.5 2.8M6.2 6.2C4.2 7.5 3 9 3 9s3.5 5 9 5a10.4 10.4 0 004-.8" /></svg>
    ) : (
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12s3.5-5 9-5 9 5 9 5-3.5 5-9 5-9-5-9-5z" /><circle cx="12" cy="12" r="2.5" /></svg>
    );
}

export function PasswordField({ id, label, labelAction, error, visible: controlledVisible, onVisibilityChange, ...inputProps }) {
    const [localVisible, setLocalVisible] = useState(false);
    const visible = controlledVisible ?? localVisible;

    function toggleVisibility() {
        const nextVisible = !visible;

        if (onVisibilityChange) {
            onVisibilityChange(nextVisible);
        } else {
            setLocalVisible(nextVisible);
        }
    }

    return (
        <div className="auth-field">
            <div className="label-row"><label htmlFor={id}>{label}</label>{labelAction}</div>
            <div className="password-field">
                <input id={id} name={id} type={visible ? 'text' : 'password'} aria-invalid={Boolean(error)} {...inputProps} />
                <button type="button" onClick={toggleVisibility} aria-label={visible ? 'Hide passwords' : 'Show passwords'}>
                    <EyeIcon open={visible} />
                </button>
            </div>
            {error && <span className="field-error" role="alert">{error}</span>}
        </div>
    );
}
