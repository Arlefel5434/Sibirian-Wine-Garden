let currentAccessToken = null;
let refreshPromise = null;

export function setAccessToken(token) {
    currentAccessToken = token;
}

export function clearAccessToken() {
    currentAccessToken = null;
}

export async function refreshSession() {
    if (!refreshPromise) {
        refreshPromise = fetch('/api/auth/refresh', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
        })
            .then(async (response) => {
                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    clearAccessToken();
                    throw new Error(payload.message || 'Your session has expired. Please sign in again.');
                }

                setAccessToken(payload.access_token);

                return payload;
            })
            .finally(() => {
                refreshPromise = null;
            });
    }

    return refreshPromise;
}

export async function apiRequest(path, options = {}, retry = true) {
    const response = await fetch(path, {
        ...options,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            ...(currentAccessToken ? { Authorization: `Bearer ${currentAccessToken}` } : {}),
            ...options.headers,
        },
    });
    const payload = response.status === 204
        ? null
        : await response.json().catch(() => ({}));

    if (response.status === 401 && retry && !path.endsWith('/login') && !path.endsWith('/refresh')) {
        await refreshSession();

        return apiRequest(path, options, false);
    }

    if (!response.ok) {
        const error = new Error(payload?.message || 'Something went wrong. Please try again.');
        error.status = response.status;
        error.errors = payload?.errors || {};

        throw error;
    }

    return payload;
}
