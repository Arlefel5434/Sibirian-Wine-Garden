export const WALLET_UPDATED = 'wallet:updated';

export function announceWalletUpdated(wallet) {
    window.dispatchEvent(new CustomEvent(WALLET_UPDATED, { detail: wallet }));
}
