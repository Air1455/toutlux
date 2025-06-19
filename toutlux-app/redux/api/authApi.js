import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import { setAuth, updateTokens, logout } from '@/redux/authSlice';

// ✅ Base query avec auto-refresh
export const baseQueryWithReauth = async (args, api, extraOptions) => {
    if (args.meta?.isLoginRequest) {
        return await baseQuery(args, api, extraOptions);
    }

    let result = await baseQuery(args, api, extraOptions);

    const publicEndpoints = ['register', 'check-user', 'login_check', 'auth/google', 'token/refresh'];
    const isPublicEndpoint = publicEndpoints.some(ep => args.url?.includes(ep));

    if (result.error?.status === 401 && !isPublicEndpoint) {
        console.log('🔄 Token expired, attempting refresh...');

        const refreshToken = api.getState().auth.refreshToken;

        if (refreshToken) {
            console.log('🔑 Refresh token found, refreshing...');

            // Tenter refresh
            const refreshResult = await baseQuery(
                {
                    url: 'token/refresh',
                    method: 'POST',
                    body: { refresh_token: refreshToken },
                },
                api,
                extraOptions
            );

            if (refreshResult.data) {
                console.log('✅ Token refreshed successfully');

                // Mettre à jour les tokens
                api.dispatch(updateTokens(refreshResult.data));

                // Retry la requête originale avec le nouveau token
                result = await baseQuery(args, api, extraOptions);
            } else {
                console.log('❌ Refresh failed, logging out');

                // Refresh échoué, déconnecter
                api.dispatch(logout());

                // Optionnel: Rediriger vers login
                // NavigationService.navigate('Login');
            }
        } else {
            console.log('❌ No refresh token, logging out');

            // Pas de refresh token, déconnecter
            api.dispatch(logout());
        }
    }

    return result;
};

const baseQuery = fetchBaseQuery({
    baseUrl: `${process.env.EXPO_PUBLIC_API_URL}/api/`,
    prepareHeaders: (headers, { getState, endpoint }) => {
        const token = getState().auth?.token;

        // Liste des endpoints qui ne nécessitent pas de token
        const publicEndpoints = ['register', 'check-user', 'login_check', 'auth/google', 'token/refresh'];
        const isPublicEndpoint = publicEndpoints.some(ep => endpoint?.includes(ep));

        // Par défaut, on met Content-Type JSON sauf pour upload
        if (endpoint !== 'uploadFile') {
            headers.set('Content-Type', 'application/json');
        }

        // On n'ajoute le token que pour les endpoints privés
        if (!isPublicEndpoint && token) {
            headers.set('Authorization', `Bearer ${token}`);
        }

        return headers;
    },
});

export const authApi = createApi({
    reducerPath: 'authApi',
    baseQuery: baseQueryWithReauth,
    tagTypes: ['Auth', 'User'],
    endpoints: (builder) => ({
        login: builder.mutation({
            query: (credentials) => ({
                url: 'login_check',
                method: 'POST',
                body: credentials,
                meta: { isLoginRequest: true }
            }),
            transformResponse: (response) => {
                console.log('🔑 Login response:', response);
                return {
                    token: response.token,
                    refresh_token: response.refresh_token, // ✅ AJOUT
                    user: response.user,
                    message: 'Login successful'
                };
            },
            invalidatesTags: ['Auth', 'User'],
        }),

        register: builder.mutation({
            query: (userData) => ({
                url: 'register',
                method: 'POST',
                body: userData,
            }),
            transformResponse: (response) => {
                return {
                    token: response.token || null,
                    refresh_token: response.refresh_token || null,
                    user: response.user,
                    message: response.message || 'Registration successful',
                    needsLogin: !response.token
                };
            },
            invalidatesTags: ['Auth'],
        }),

        googleAuth: builder.mutation({
            query: (googleData) => ({
                url: 'auth/google',
                method: 'POST',
                body: googleData,
            }),
            transformResponse: (response) => {
                return {
                    token: response.token,
                    refresh_token: response.refresh_token, // ✅ AJOUT
                    user: response.user,
                    message: 'Google authentication successful'
                };
            },
            invalidatesTags: ['Auth', 'User'],
        }),

        // ✅ NOUVEAU: Endpoint refresh token
        refreshToken: builder.mutation({
            query: (refreshData) => ({
                url: 'token/refresh',
                method: 'POST',
                body: refreshData,
            }),
            transformResponse: (response) => {
                console.log('🔄 Refresh response:', response);
                return {
                    token: response.token,
                    refresh_token: response.refresh_token,
                    user: response.user,
                    message: 'Token refreshed successfully'
                };
            },
        }),

        getMe: builder.query({
            query: () => 'me',
            providesTags: [{ type: 'User', id: 'CURRENT' }],
        }),

        updateProfile: builder.mutation({
            query: (data) => ({
                url: 'me',
                method: 'PATCH',
                body: data,
            }),
            invalidatesTags: [{ type: 'User', id: 'CURRENT' }],
        }),

        // ✅ MODIFIÉ: Logout avec refresh token
        logout: builder.mutation({
            query: (_, { getState }) => {
                const refreshToken = getState().auth.refreshToken;
                return {
                    url: 'logout',
                    method: 'POST',
                    body: refreshToken ? { refresh_token: refreshToken } : {},
                };
            },
            invalidatesTags: ['Auth', 'User'],
        }),
    }),
});

export const {
    useLoginMutation,
    useRegisterMutation,
    useGoogleAuthMutation,
    useRefreshTokenMutation, // ✅ NOUVEAU
    useGetMeQuery,
    useUpdateProfileMutation,
    useLogoutMutation,
} = authApi;