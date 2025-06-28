import { createApi } from '@reduxjs/toolkit/query/react';
import { createBaseQueryWithReauth } from './baseQuery';

export const notificationApi = createApi({
    reducerPath: 'notificationApi',
    baseQuery: createBaseQueryWithReauth(),
    tagTypes: ['Notification', 'NotificationStats'],
    endpoints: (builder) => ({
        // Obtenir la liste des notifications
        getNotifications: builder.query({
            query: ({ page = 1, limit = 20, filter = 'all', type = null }) => {
                let url = `notifications?page=${page}&limit=${limit}&filter=${filter}`;
                if (type) {
                    url += `&type=${type}`;
                }
                return url;
            },
            providesTags: (result) =>
                result?.notifications
                    ? [
                        ...result.notifications.map(({ id }) => ({ type: 'Notification', id })),
                        { type: 'Notification', id: 'LIST' },
                        { type: 'NotificationStats' }
                    ]
                    : [{ type: 'Notification', id: 'LIST' }],
            // Merger les pages pour la pagination infinie
            serializeQueryArgs: ({ endpointName, queryArgs }) => {
                const { filter, type } = queryArgs;
                return `${endpointName}-${filter}-${type || 'all'}`;
            },
            merge: (currentCache, newItems, { arg }) => {
                if (arg.page === 1) {
                    return newItems;
                }
                return {
                    ...newItems,
                    notifications: [...currentCache.notifications, ...newItems.notifications]
                };
            },
            forceRefetch({ currentArg, previousArg }) {
                return currentArg?.page !== previousArg?.page;
            },
        }),

        // Obtenir une notification spécifique
        getNotification: builder.query({
            query: (id) => `notifications/${id}`,
            providesTags: (result, error, id) => [{ type: 'Notification', id }],
        }),

        // Marquer une notification comme lue
        markNotificationAsRead: builder.mutation({
            query: (id) => ({
                url: `notifications/${id}/read`,
                method: 'POST',
            }),
            invalidatesTags: (result, error, id) => [
                { type: 'Notification', id },
                { type: 'NotificationStats' }
            ],
            // Mise à jour optimiste
            async onQueryStarted(id, { dispatch, queryFulfilled }) {
                const patchResult = dispatch(
                    notificationApi.util.updateQueryData('getNotifications', { page: 1, filter: 'all' }, (draft) => {
                        const notification = draft.notifications.find(n => n.id === id);
                        if (notification) {
                            notification.isRead = true;
                            notification.readAt = new Date().toISOString();
                            if (draft.unreadCount > 0) {
                                draft.unreadCount--;
                            }
                        }
                    })
                );
                try {
                    await queryFulfilled;
                } catch {
                    patchResult.undo();
                }
            },
        }),

        // Marquer toutes les notifications comme lues
        markAllNotificationsAsRead: builder.mutation({
            query: () => ({
                url: 'notifications/mark-all-read',
                method: 'POST',
            }),
            invalidatesTags: [
                { type: 'Notification', id: 'LIST' },
                { type: 'NotificationStats' }
            ],
        }),

        // Supprimer une notification
        deleteNotification: builder.mutation({
            query: (id) => ({
                url: `notifications/${id}`,
                method: 'DELETE',
            }),
            invalidatesTags: (result, error, id) => [
                { type: 'Notification', id },
                { type: 'NotificationStats' }
            ],
            // Mise à jour optimiste
            async onQueryStarted(id, { dispatch, queryFulfilled }) {
                const patchResult = dispatch(
                    notificationApi.util.updateQueryData('getNotifications', { page: 1, filter: 'all' }, (draft) => {
                        const index = draft.notifications.findIndex(n => n.id === id);
                        if (index !== -1) {
                            const notification = draft.notifications[index];
                            draft.notifications.splice(index, 1);
                            if (!notification.isRead && draft.unreadCount > 0) {
                                draft.unreadCount--;
                            }
                            if (draft.pagination.total > 0) {
                                draft.pagination.total--;
                            }
                        }
                    })
                );
                try {
                    await queryFulfilled;
                } catch {
                    patchResult.undo();
                }
            },
        }),

        // Supprimer toutes les notifications lues
        deleteReadNotifications: builder.mutation({
            query: () => ({
                url: 'notifications/delete-read',
                method: 'POST',
            }),
            invalidatesTags: [
                { type: 'Notification', id: 'LIST' },
                { type: 'NotificationStats' }
            ],
        }),

        // Obtenir les statistiques
        getNotificationStats: builder.query({
            query: () => 'notifications/stats',
            providesTags: ['NotificationStats'],
        }),

        // Resend email verification (lié aux notifications)
        resendEmailVerification: builder.mutation({
            query: () => ({
                url: 'email/resend-confirmation',
                method: 'POST',
            }),
            invalidatesTags: [{ type: 'User', id: 'CURRENT' }],
        }),
    }),
});

export const {
    useGetNotificationsQuery,
    useGetNotificationQuery,
    useMarkNotificationAsReadMutation,
    useMarkAllNotificationsAsReadMutation,
    useDeleteNotificationMutation,
    useDeleteReadNotificationsMutation,
    useGetNotificationStatsQuery,
    useResendEmailVerificationMutation,
} = notificationApi;