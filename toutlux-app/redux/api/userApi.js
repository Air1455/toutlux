import { createApi } from '@reduxjs/toolkit/query/react';
import { baseQueryWithReauth } from "@/redux/api/authApi";

export const userApi = createApi({
    reducerPath: 'userApi',
    baseQuery: baseQueryWithReauth,
    tagTypes: ['User'],
    endpoints: (builder) => ({
        // === ONBOARDING ===
        updateProfileStep: builder.mutation({
            query: ({ step, data }) => {
                // ✅ CORRECTION: Normaliser les clés de snake_case vers camelCase
                const normalizedData = {};

                for (const [key, value] of Object.entries(data)) {
                    // Convertir snake_case en camelCase
                    const camelKey = key.replace(/_([a-z])/g, (g) => g[1].toUpperCase());
                    normalizedData[camelKey] = value;
                }

                // ✅ CORRECTION: Gérer spécifiquement phoneNumberIndicatif
                if (data.phone_number_indicatif && !data.phoneNumberIndicatif) {
                    normalizedData.phoneNumberIndicatif = data.phone_number_indicatif;
                }

                return {
                    url: `profile/step/${step}`,
                    method: 'PATCH',
                    body: normalizedData,
                };
            },
            invalidatesTags: [{ type: 'User', id: 'CURRENT' }],
        }),

        acceptTerms: builder.mutation({
            query: (version = '1.0') => ({
                url: 'profile/accept-terms',
                method: 'POST',
                body: { version },
            }),
            invalidatesTags: [{ type: 'User', id: 'CURRENT' }],
        }),

        // === UPLOAD - CORRIGÉ ===
        uploadFile: builder.mutation({
            query: ({ file, type }) => {
                const formData = new FormData();

                // ✅ CORRECTION: Structure correcte pour React Native
                const fileObject = {
                    uri: file.uri,
                    type: file.type || 'image/jpeg',
                    name: file.fileName || file.name || `${type}_${Date.now()}.jpg`,
                };

                formData.append('file', fileObject);
                formData.append('type', type);

                return {
                    url: 'upload',
                    method: 'POST',
                    body: formData,
                };
            },
            transformResponse: (response) => {
                // ✅ AJOUT: Vérifier et nettoyer l'URL retournée
                if (response.url && !response.url.startsWith('http')) {
                    // S'assurer que l'URL est relative
                    response.fullUrl = response.url.startsWith('/')
                        ? response.url
                        : `/${response.url}`;
                }
                return response;
            },
            invalidatesTags: [{ type: 'User', id: 'CURRENT' }],
        }),

        // === VÉRIFICATIONS ===
        resendEmailVerification: builder.mutation({
            query: () => ({
                url: 'auth/resend-email-verification',
                method: 'POST',
            }),
            transformErrorResponse: (response) => {
                return {
                    status: response.status,
                    message: response.data?.message || 'Failed to resend verification email',
                    code: response.data?.code || 'RESEND_FAILED'
                };
            },
        }),

        resendPhoneVerification: builder.mutation({
            query: () => ({
                url: 'auth/resend-phone-verification',
                method: 'POST',
            }),
            transformErrorResponse: (response) => {
                return {
                    status: response.status,
                    message: response.data?.message || 'Failed to resend verification SMS',
                    code: response.data?.code || 'RESEND_FAILED'
                };
            },
        }),

        // === AJOUTS UTILES ===
        getProfileCompletion: builder.query({
            query: () => 'profile/completion',
            providesTags: [{ type: 'User', id: 'COMPLETION' }],
        }),

        updatePhoneNumber: builder.mutation({
            query: ({ phoneNumber, phoneNumberIndicatif }) => ({
                url: 'profile/phone',
                method: 'PATCH',
                body: { phoneNumber, phoneNumberIndicatif },
            }),
            invalidatesTags: [{ type: 'User', id: 'CURRENT' }],
        }),
    }),
});

// ✅ AJOUT: Helpers pour gérer les erreurs
export const isUploadError = (error) => {
    return error?.data?.code === 'UPLOAD_FAILED' || error?.status === 413;
};

export const getUploadErrorMessage = (error) => {
    if (error?.status === 413) return 'File too large (max 10MB)';
    if (error?.data?.message) return error.data.message;
    return 'Upload failed';
};

export const {
    useUpdateProfileStepMutation,
    useAcceptTermsMutation,
    useUploadFileMutation,
    useResendEmailVerificationMutation,
    useResendPhoneVerificationMutation,
    useGetProfileCompletionQuery,
    useUpdatePhoneNumberMutation,
} = userApi;