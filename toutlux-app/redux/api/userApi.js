import { createApi } from '@reduxjs/toolkit/query/react';
import { createBaseQueryWithReauth } from './baseQuery';

export const userApi = createApi({
    reducerPath: 'userApi',
    baseQuery: createBaseQueryWithReauth(),
    tagTypes: ['User'],
    endpoints: (builder) => ({
        getUserById: builder.query({
            query: (id) => `users/${id}`,
            transformResponse: (response) => {
                return response;
            },
            providesTags: (result, error, id) => [{ type: 'User', id }],
            keepUnusedDataFor: 300, // 5 minutes
            refetchOnMountOrArgChange: true,
        }),
        updateProfileStep: builder.mutation({
            query: ({ step, data }) => {
                // ✅ Normalisation des clés
                const normalizedData = {};

                for (const [key, value] of Object.entries(data)) {
                    const camelKey = key.replace(/_([a-z])/g, (g) => g[1].toUpperCase());
                    normalizedData[camelKey] = value;
                }

                // ✅ Gestion spécifique phoneNumberIndicatif
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

        uploadFile: builder.mutation({
            query: ({ file, type }) => {
                const formData = new FormData();

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
                if (response.url && !response.url.startsWith('http')) {
                    response.fullUrl = response.url.startsWith('/')
                        ? response.url
                        : `/${response.url}`;
                }
                return response;
            },
            invalidatesTags: [{ type: 'User', id: 'CURRENT' }],
        }),

        resendEmailVerification: builder.mutation({
            query: () => ({
                url: 'auth/resend-email-verification',
                method: 'POST',
            }),
        }),

        resendPhoneVerification: builder.mutation({
            query: () => ({
                url: 'auth/resend-phone-verification',
                method: 'POST',
            }),
        }),

        getProfileCompletion: builder.query({
            query: () => 'profile/completion',
            providesTags: [{ type: 'User', id: 'COMPLETION' }],
        }),

        // ✅ AJOUT: Changement de mot de passe
        changePassword: builder.mutation({
            query: ({ currentPassword, newPassword }) => ({
                url: 'profile/change-password',
                method: 'POST',
                body: { currentPassword, newPassword },
            }),
        }),

        // ✅ AJOUT: Vérification de la force du mot de passe
        checkPasswordStrength: builder.mutation({
            query: ({ password }) => ({
                url: 'profile/password-strength',
                method: 'POST',
                body: { password },
            }),
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

export const {
    useUpdateProfileStepMutation,
    useAcceptTermsMutation,
    useUploadFileMutation,
    useResendEmailVerificationMutation,
    useResendPhoneVerificationMutation,
    useGetUserByIdQuery,
    useGetProfileCompletionQuery,
    useChangePasswordMutation, // ✅ AJOUT
    useCheckPasswordStrengthMutation, // ✅ AJOUT
    useUpdatePhoneNumberMutation,
} = userApi;