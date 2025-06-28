import { fetchBaseQuery } from '@reduxjs/toolkit/query/react';

// ✅ Base query unifié pour toutes les APIs
const createBaseQuery = (options = {}) => {
    return fetchBaseQuery({
        baseUrl: `${process.env.EXPO_PUBLIC_API_URL}/api/`,
        prepareHeaders: (headers, { getState, endpoint }) => {
            const { requiresAuth = true } = options;
            const token = getState().auth?.token;

            // Liste des endpoints publics
            const publicEndpoints = [
                'register',
                'login_check',
                'auth/google',
                'token/refresh',
                'check-email',
                'houses',
            ];

            const url = typeof endpoint === 'string' ? endpoint : '';
            const isPublicEndpoint = publicEndpoints.some(ep =>
                url.includes(ep)
            );

            if (endpoint === 'googleAuth') {
                headers.set('Content-Type', 'application/json');
            } else if (endpoint !== 'uploadFile') {
                headers.set('Content-Type', 'application/ld+json');
                headers.set('Accept', 'application/ld+json');
            }

            // Ajouter token seulement pour endpoints privés
            if (!isPublicEndpoint && token?.trim()) {
                headers.set('Authorization', `Bearer ${token}`);
            }

            return headers;
        },
    });
};

// ✅ Base query avec auto-refresh UNIFIÉ
export const createBaseQueryWithReauth = (options = {}) => {
    const baseQuery = createBaseQuery(options);

    return async (args, api, extraOptions) => {
        console.log('🔄 API call:', args);

        let result = await baseQuery(args, api, extraOptions);

        console.log(result)

        // Liste des endpoints publics (cohérente)
        const publicEndpoints = [
            'register',
            'login_check',
            'auth/google',
            'token/refresh',
            'check-email',
            'houses'
        ];

        const url = typeof args === 'string' ? args : args.url;
        const isPublicEndpoint = publicEndpoints.some(ep => url?.includes(ep));

        // Si erreur 401 sur endpoint privé, tenter refresh
        if (result.error?.status === 401 && !isPublicEndpoint) {
            console.log('🔄 Token expired, attempting refresh...');

            const state = api.getState();
            const refreshToken = state.auth?.refreshToken;

            if (refreshToken) {
                // Créer une requête de refresh propre
                const refreshBaseQuery = createBaseQuery({ requiresAuth: false });

                const refreshResult = await refreshBaseQuery(
                    {
                        url: 'token/refresh',
                        method: 'POST',
                        body: { refresh_token: refreshToken },
                    },
                    api,
                    extraOptions
                );

                if (refreshResult.data?.token) {
                    console.log('✅ Token refreshed successfully');

                    // Mettre à jour les tokens
                    const { updateTokens } = await import('@/redux/authSlice');
                    api.dispatch(updateTokens({
                        token: refreshResult.data.token,
                        refresh_token: refreshResult.data.refresh_token
                    }));

                    // Attendre la mise à jour du state
                    await new Promise(resolve => setTimeout(resolve, 100));

                    // Retry avec le nouveau token
                    result = await baseQuery(args, api, extraOptions);
                    console.log('✅ Retry result success:', !result.error);
                } else {
                    console.log('❌ Refresh failed, logging out');
                    const { logout } = await import('@/redux/authSlice');
                    api.dispatch(logout());
                }
            } else {
                console.log('❌ No refresh token, logging out');
                const { logout } = await import('@/redux/authSlice');
                api.dispatch(logout());
            }
        }

        return result;
    };
};