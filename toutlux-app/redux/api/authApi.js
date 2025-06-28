import { createApi } from '@reduxjs/toolkit/query/react';
import { createBaseQueryWithReauth } from './baseQuery';

export const authApi = createApi({
    reducerPath: 'authApi',
    baseQuery: createBaseQueryWithReauth(),
    tagTypes: ['Auth', 'User'],
    endpoints: (builder) => ({
        login: builder.mutation({
            query: (credentials) => ({
                url: 'login_check',
                method: 'POST',
                body: credentials,
            }),
            transformResponse: (response) => {
                return {
                    token: response.token,
                    refresh_token: response.refresh_token,
                    user: response.user,
                    message: response.message || 'Login successful'
                };
            },
            // ✅ AMÉLIORATION: Transform des erreurs pour une meilleure gestion
            transformErrorResponse: (response) => {
                console.log('❌ Login error response:', response);

                const errorData = response.data || {};

                // Retourner une structure d'erreur enrichie
                return {
                    status: response.status,
                    data: {
                        ...errorData,
                        isUserNotFound: errorData.code === 'USER_NOT_FOUND',
                        isInvalidPassword: errorData.code === 'INVALID_PASSWORD',
                        isAccountSuspended: errorData.code === 'ACCOUNT_SUSPENDED',
                        canCreateAccount: errorData.suggestion === 'create_account',
                        email: errorData.email
                    }
                };
            },
            invalidatesTags: ['Auth', 'User'],
        }),

        register: builder.mutation({
            query: (userData) => ({
                url: 'register',
                method: 'POST',
                body: { ...userData, auto_login: true },
            }),
            transformResponse: (response) => {
                return {
                    token: response.token || null,
                    refresh_token: response.refresh_token || null,
                    user: response.user,
                    message: response.message || 'Registration successful',
                    needsLogin: !response.token,
                    emailNotifications: response.email_notifications
                };
            },
            transformErrorResponse: (response) => {
                console.log('❌ Register error response:', response);

                const errorData = response.data || {};

                return {
                    status: response.status,
                    data: {
                        ...errorData,
                        isUserExists: errorData.code === 'USER_EXISTS' || response.status === 409,
                        isValidationError: response.status === 400,
                        validationDetails: errorData.details
                    }
                };
            },
            invalidatesTags: ['Auth'],
        }),

        googleAuth: builder.mutation({
            query: (googleData) => ({
                url: 'auth/google',
                method: 'POST',
                body: googleData, // Maintenant inclut { id_token, auto_register }
            }),
            transformResponse: (response) => {

                // ✅ NOUVEAU: Gérer la réponse requires_registration
                if (response.requires_registration) {
                    return response; // Retourner tel quel pour le frontend
                }

                // Réponse normale avec tokens
                return {
                    token: response.token,
                    refresh_token: response.refresh_token,
                    user: response.user,
                    message: response.message || 'Google authentication successful'
                };
            },
            transformErrorResponse: (response) => {
                console.log('❌ Google auth error response:', response);

                const errorData = response.data || {};

                return {
                    status: response.status,
                    data: {
                        ...errorData,
                        isAuthError: true
                    }
                };
            },
            invalidatesTags: (result) => {
                // Ne pas invalider les tags si c'est juste requires_registration
                if (result?.requires_registration) {
                    return [];
                }
                return ['Auth', 'User'];
            },
        }),

        refreshToken: builder.mutation({
            query: (refreshData) => ({
                url: 'token/refresh',
                method: 'POST',
                body: refreshData,
            }),
            transformResponse: (response) => {
                return {
                    token: response.token,
                    refresh_token: response.refresh_token,
                    user: response.user,
                    message: response.message || 'Token refreshed successfully'
                };
            },
        }),

        getMe: builder.query({
            query: () => 'users/me',
            providesTags: [{ type: 'User', id: 'CURRENT' }],
            retry: (failureCount, error) => {
                // Retry seulement pour les erreurs réseau, pas les 401
                return error.status !== 401 && failureCount < 2;
            },
        }),

        updateProfile: builder.mutation({
            query: (data) => ({
                url: 'users/me',
                method: 'PATCH',
                body: data,
            }),
            invalidatesTags: [{ type: 'User', id: 'CURRENT' }],
        }),

        logout: builder.mutation({
            query: (_, { getState }) => {
                const refreshToken = getState().auth.refreshToken;
                console.log('👋 Logging out with refresh token:', !!refreshToken);
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
    useRefreshTokenMutation,
    useGetMeQuery,
    useUpdateProfileMutation,
    useLogoutMutation,
} = authApi;