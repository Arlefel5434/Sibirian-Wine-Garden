export const FAVORITES_UPDATED = 'favorites:updated';

export function announceFavoritesUpdated(itemsCount) {
    window.dispatchEvent(new CustomEvent(FAVORITES_UPDATED, { detail: { itemsCount } }));
}
