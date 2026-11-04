import React from 'react';

export function CartWineIcon({ className = '' }) {
    return <svg className={className} viewBox="0 0 40 40" aria-hidden="true">
        <path className="cart-basket-handle" d="M9.3 15.2C11 9.8 14.9 7 20 7s9 2.8 10.7 8.2" />
        <path className="cart-basket-body" d="M5.2 14.2h29.6l-2.7 17.6c-.3 1.8-1.9 3.1-3.7 3.1H11.6c-1.8 0-3.4-1.3-3.7-3.1L5.2 14.2Z" />
        <path className="cart-basket-weave" d="M11.1 19.2h17.8M10.3 24.2h19.4M9.5 29.2h21M14.3 15.4l1.4 18M25.7 15.4l-1.4 18" />
    </svg>;
}
