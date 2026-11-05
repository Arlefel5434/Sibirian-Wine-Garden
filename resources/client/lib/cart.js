export const CART_UPDATED = 'cart:updated';

export function announceCartUpdated(itemsCount) {
    window.dispatchEvent(new CustomEvent(CART_UPDATED, { detail: { itemsCount } }));
}
