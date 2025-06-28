import { createApi } from '@reduxjs/toolkit/query/react';
import { createBaseQueryWithReauth } from './baseQuery';

export const contactApi = createApi({
    reducerPath: 'contactApi',
    baseQuery: createBaseQueryWithReauth(),
    tagTypes: ['Contact', 'ContactThread', 'ContactStats', 'ContactTemplate'],
    endpoints: (builder) => ({
        // ==========================================
        // CRÉATION DE CONTACT
        // ==========================================

        /**
         * Contacter un vendeur avec notifications automatiques
         */
        contactSeller: builder.mutation({
            query: ({ houseId, sellerId, messageType, subject, message, phoneNumber }) => ({
                url: 'contacts',
                method: 'POST',
                body: JSON.stringify({
                    house: `/api/houses/${houseId}`,
                    recipient: `/api/users/${sellerId}`,
                    sender: `/api/users/me`,
                    messageType,
                    subject,
                    message,
                    senderPhone: phoneNumber || null,
                }),
            }),
            transformResponse: (response) => {
                console.log('✅ Contact message sent successfully:', response);
                return response;
            },
            transformErrorResponse: (response) => {
                console.error('❌ Error sending contact message:', response);
                return {
                    status: response.status,
                    data: response.data,
                    message: response.data?.['hydra:description'] || 'Erreur lors de l\'envoi du message'
                };
            },
            invalidatesTags: [
                { type: 'Contact', id: 'LIST' },
                { type: 'ContactStats' }
            ],
            // Mise à jour optimiste pour une UX immédiate
            async onQueryStarted(arg, { dispatch, queryFulfilled, getState }) {
                const currentUser = getState().auth?.user;

                // Optimistic update - ajouter immédiatement à la liste
                const patchResult = dispatch(
                    contactApi.util.updateQueryData('getMyConversations', { page: 1 }, (draft) => {
                        if (draft.conversations && currentUser) {
                            const optimisticContact = {
                                id: `temp-${Date.now()}`,
                                subject: arg.subject,
                                message: arg.message,
                                messageType: arg.messageType,
                                sender: currentUser,
                                recipient: { id: arg.sellerId },
                                house: { id: arg.houseId },
                                isRead: true, // Marquer comme lu pour l'expéditeur
                                status: 'active',
                                priority: 'normal',
                                createdAt: new Date().toISOString(),
                                lastMessageAt: new Date().toISOString(),
                                messagesCount: 1,
                                senderPhone: arg.phoneNumber
                            };

                            draft.conversations.unshift(optimisticContact);
                            draft.totalItems = (draft.totalItems || 0) + 1;
                        }
                    })
                );

                // Mettre à jour les stats optimistiquement
                const statsResult = dispatch(
                    contactApi.util.updateQueryData('getContactStats', undefined, (draft) => {
                        draft.totalConversations = (draft.totalConversations || 0) + 1;
                        draft.newContactsToday = (draft.newContactsToday || 0) + 1;
                    })
                );

                try {
                    const { data: newContact } = await queryFulfilled;

                    // Remplacer le contact temporaire par le vrai
                    dispatch(
                        contactApi.util.updateQueryData('getMyConversations', { page: 1 }, (draft) => {
                            if (draft.conversations) {
                                const tempIndex = draft.conversations.findIndex(c => c.id === `temp-${Date.now()}`);
                                if (tempIndex !== -1) {
                                    draft.conversations[tempIndex] = newContact;
                                }
                            }
                        })
                    );
                } catch (error) {
                    // Rollback en cas d'erreur
                    patchResult.undo();
                    statsResult.undo();
                }
            },
        }),

        // ==========================================
        // GESTION DES CONVERSATIONS
        // ==========================================

        /**
         * Obtenir mes conversations avec filtres et pagination
         */
        getMyConversations: builder.query({
            query: (params = {}) => {
                const {
                    page = 1,
                    limit = 20,
                    status = 'all',
                    type = 'all',
                    unreadOnly = false,
                    search = '',
                    sortBy = 'lastMessageAt',
                    sortOrder = 'desc'
                } = params;

                const searchParams = new URLSearchParams();

                searchParams.append('page', page);
                searchParams.append('limit', limit);

                if (status !== 'all') searchParams.append('status', status);
                if (type !== 'all') searchParams.append('messageType', type);
                if (unreadOnly) searchParams.append('unreadOnly', 'true');
                if (search) searchParams.append('search', search);
                if (sortBy) searchParams.append('sortBy', sortBy);
                if (sortOrder) searchParams.append('sortOrder', sortOrder);

                return `contacts/conversations?${searchParams.toString()}`;
            },
            transformResponse: (response) => {
                if (response['hydra:member']) {
                    return {
                        conversations: response['hydra:member'],
                        totalItems: response['hydra:totalItems'] || 0,
                        pagination: {
                            currentPage: response['hydra:view']?.['@id']?.match(/page=(\d+)/)?.[1] || 1,
                            totalItems: response['hydra:totalItems'] || 0,
                            itemsPerPage: response['hydra:view']?.['hydra:last']?.match(/limit=(\d+)/)?.[1] || 20,
                            totalPages: Math.ceil((response['hydra:totalItems'] || 0) / 20)
                        },
                        filters: response.filters || {}
                    };
                }
                return {
                    conversations: [],
                    totalItems: 0,
                    pagination: { currentPage: 1, totalItems: 0, itemsPerPage: 20, totalPages: 0 }
                };
            },
            providesTags: (result) =>
                result?.conversations
                    ? [
                        ...result.conversations.map(({ id }) => ({ type: 'Contact', id })),
                        { type: 'Contact', id: 'LIST' }
                    ]
                    : [{ type: 'Contact', id: 'LIST' }],
            // Configuration pour pagination infinie
            serializeQueryArgs: ({ endpointName, queryArgs }) => {
                const { status, type, unreadOnly, search, sortBy, sortOrder } = queryArgs;
                return `${endpointName}-${status || 'all'}-${type || 'all'}-${unreadOnly || false}-${search || ''}-${sortBy || 'lastMessageAt'}-${sortOrder || 'desc'}`;
            },
            merge: (currentCache, newItems, { arg }) => {
                if (arg.page === 1) {
                    return newItems;
                }
                return {
                    ...newItems,
                    conversations: [...(currentCache.conversations || []), ...newItems.conversations],
                    pagination: newItems.pagination
                };
            },
            forceRefetch({ currentArg, previousArg }) {
                return currentArg?.page !== previousArg?.page ||
                    currentArg?.status !== previousArg?.status ||
                    currentArg?.type !== previousArg?.type ||
                    currentArg?.search !== previousArg?.search;
            },
            // Refresh automatique toutes les 2 minutes pour les nouvelles conversations
            pollingInterval: 2 * 60 * 1000,
        }),

        /**
         * Obtenir une conversation spécifique avec messages
         */
        getConversation: builder.query({
            query: (conversationId) => `contacts/conversations/${conversationId}`,
            transformResponse: (response) => ({
                conversation: response,
                messages: response.messages || [],
                participants: [response.sender, response.recipient],
                house: response.house,
                totalMessages: response.messagesCount || 0
            }),
            providesTags: (result, error, id) => [
                { type: 'ContactThread', id },
                { type: 'Contact', id }
            ],
            // Marquer automatiquement comme lu après visualisation
            async onQueryStarted(conversationId, { dispatch, queryFulfilled }) {
                try {
                    const { data } = await queryFulfilled;

                    // Si la conversation n'est pas lue, la marquer comme lue après 2 secondes
                    if (!data.conversation.isRead) {
                        setTimeout(() => {
                            dispatch(contactApi.endpoints.markConversationAsRead.initiate(conversationId));
                        }, 2000);
                    }
                } catch (error) {
                    console.error('Error in getConversation onQueryStarted:', error);
                }
            },
        }),

        /**
         * Obtenir les conversations non lues (pour badge notifications)
         */
        getUnreadConversations: builder.query({
            query: (params = {}) => {
                const { limit = 50 } = params;
                return `contacts/conversations?unreadOnly=true&limit=${limit}&sortBy=lastMessageAt&sortOrder=desc`;
            },
            transformResponse: (response) => {
                if (response['hydra:member']) {
                    return {
                        conversations: response['hydra:member'],
                        totalUnread: response['hydra:totalItems'] || 0,
                        latestUnread: response['hydra:member']?.[0] || null
                    };
                }
                return { conversations: [], totalUnread: 0, latestUnread: null };
            },
            providesTags: [{ type: 'Contact', id: 'UNREAD' }],
            // Refresh fréquent pour notifications temps réel
            pollingInterval: 30 * 1000, // 30 secondes
        }),

        // ==========================================
        // ACTIONS SUR LES CONVERSATIONS
        // ==========================================

        /**
         * Répondre dans une conversation
         */
        replyToConversation: builder.mutation({
            query: ({ conversationId, message, attachments = [], messageType = 'user_message' }) => ({
                url: `contacts/conversations/${conversationId}/reply`,
                method: 'POST',
                body: {
                    message,
                    attachments,
                    type: messageType
                },
            }),
            transformResponse: (response) => {
                console.log('✅ Reply sent successfully:', response);
                return response;
            },
            invalidatesTags: (result, error, { conversationId }) => [
                { type: 'ContactThread', id: conversationId },
                { type: 'Contact', id: conversationId },
                { type: 'Contact', id: 'LIST' },
                { type: 'Contact', id: 'UNREAD' },
                { type: 'ContactStats' }
            ],
            // Mise à jour optimiste du message
            async onQueryStarted({ conversationId, message, attachments = [] }, { dispatch, queryFulfilled, getState }) {
                const currentUser = getState().auth?.user;

                if (!currentUser) return;

                // Ajouter le message optimistiquement
                const patchResult = dispatch(
                    contactApi.util.updateQueryData('getConversation', conversationId, (draft) => {
                        const newMessage = {
                            id: `temp-${Date.now()}`,
                            message,
                            sender: currentUser,
                            createdAt: new Date().toISOString(),
                            updatedAt: new Date().toISOString(),
                            type: 'user_message',
                            isRead: false,
                            attachments: attachments,
                            isTemporary: true
                        };

                        draft.messages.push(newMessage);
                        draft.totalMessages = (draft.totalMessages || 0) + 1;

                        // Mettre à jour la conversation
                        if (draft.conversation) {
                            draft.conversation.lastMessageAt = new Date().toISOString();
                            draft.conversation.messagesCount = (draft.conversation.messagesCount || 0) + 1;
                        }
                    })
                );

                // Mettre à jour la liste des conversations
                const listPatchResult = dispatch(
                    contactApi.util.updateQueryData('getMyConversations', {}, (draft) => {
                        if (draft.conversations) {
                            const conversation = draft.conversations.find(c => c.id === conversationId);
                            if (conversation) {
                                conversation.lastMessageAt = new Date().toISOString();
                                conversation.messagesCount = (conversation.messagesCount || 0) + 1;
                                conversation.lastMessage = message.substring(0, 100);

                                // Remonter la conversation en haut de la liste
                                const index = draft.conversations.indexOf(conversation);
                                if (index > 0) {
                                    draft.conversations.splice(index, 1);
                                    draft.conversations.unshift(conversation);
                                }
                            }
                        }
                    })
                );

                try {
                    const { data: realMessage } = await queryFulfilled;

                    // Remplacer le message temporaire par le vrai
                    dispatch(
                        contactApi.util.updateQueryData('getConversation', conversationId, (draft) => {
                            const tempIndex = draft.messages.findIndex(m => m.isTemporary);
                            if (tempIndex !== -1) {
                                draft.messages[tempIndex] = realMessage;
                            }
                        })
                    );
                } catch (error) {
                    // Rollback en cas d'erreur
                    patchResult.undo();
                    listPatchResult.undo();
                }
            },
        }),

        /**
         * Marquer une conversation comme lue
         */
        markConversationAsRead: builder.mutation({
            query: (conversationId) => ({
                url: `contacts/conversations/${conversationId}/mark-read`,
                method: 'PATCH',
            }),
            invalidatesTags: (result, error, conversationId) => [
                { type: 'ContactThread', id: conversationId },
                { type: 'Contact', id: conversationId },
                { type: 'Contact', id: 'LIST' },
                { type: 'Contact', id: 'UNREAD' },
                { type: 'ContactStats' }
            ],
            // Mise à jour immédiate sans attendre la réponse
            async onQueryStarted(conversationId, { dispatch, queryFulfilled }) {
                const now = new Date().toISOString();

                // Mettre à jour la conversation détaillée
                const conversationPatch = dispatch(
                    contactApi.util.updateQueryData('getConversation', conversationId, (draft) => {
                        if (draft.conversation) {
                            draft.conversation.isRead = true;
                            draft.conversation.readAt = now;
                        }
                    })
                );

                // Mettre à jour la liste des conversations
                const listPatch = dispatch(
                    contactApi.util.updateQueryData('getMyConversations', {}, (draft) => {
                        if (draft.conversations) {
                            const conversation = draft.conversations.find(c => c.id === conversationId);
                            if (conversation) {
                                conversation.isRead = true;
                                conversation.readAt = now;
                            }
                        }
                    })
                );

                // Mettre à jour les conversations non lues
                const unreadPatch = dispatch(
                    contactApi.util.updateQueryData('getUnreadConversations', {}, (draft) => {
                        if (draft.conversations) {
                            const index = draft.conversations.findIndex(c => c.id === conversationId);
                            if (index !== -1) {
                                draft.conversations.splice(index, 1);
                                draft.totalUnread = Math.max(0, (draft.totalUnread || 1) - 1);
                            }
                        }
                    })
                );

                try {
                    await queryFulfilled;
                } catch {
                    conversationPatch.undo();
                    listPatch.undo();
                    unreadPatch.undo();
                }
            },
        }),

        /**
         * Marquer toutes les conversations comme lues
         */
        markAllConversationsAsRead: builder.mutation({
            query: () => ({
                url: 'contacts/mark-all-read',
                method: 'POST',
            }),
            invalidatesTags: [
                { type: 'Contact', id: 'LIST' },
                { type: 'Contact', id: 'UNREAD' },
                { type: 'ContactStats' }
            ],
            // Mise à jour optimiste massive
            async onQueryStarted(arg, { dispatch, queryFulfilled }) {
                const now = new Date().toISOString();

                // Mettre à jour toutes les conversations
                const listPatch = dispatch(
                    contactApi.util.updateQueryData('getMyConversations', {}, (draft) => {
                        if (draft.conversations) {
                            draft.conversations.forEach(conversation => {
                                conversation.isRead = true;
                                conversation.readAt = now;
                            });
                        }
                    })
                );

                // Vider la liste des conversations non lues
                const unreadPatch = dispatch(
                    contactApi.util.updateQueryData('getUnreadConversations', {}, (draft) => {
                        draft.conversations = [];
                        draft.totalUnread = 0;
                        draft.latestUnread = null;
                    })
                );

                try {
                    await queryFulfilled;
                } catch {
                    listPatch.undo();
                    unreadPatch.undo();
                }
            },
        }),

        /**
         * Archiver une conversation
         */
        archiveConversation: builder.mutation({
            query: (conversationId) => ({
                url: `contacts/conversations/${conversationId}/archive`,
                method: 'PATCH',
            }),
            invalidatesTags: (result, error, conversationId) => [
                { type: 'Contact', id: conversationId },
                { type: 'Contact', id: 'LIST' },
                { type: 'ContactStats' }
            ],
            // Retirer immédiatement de la liste active
            async onQueryStarted(conversationId, { dispatch, queryFulfilled }) {
                const patchResult = dispatch(
                    contactApi.util.updateQueryData('getMyConversations', {}, (draft) => {
                        if (draft.conversations) {
                            const index = draft.conversations.findIndex(c => c.id === conversationId);
                            if (index !== -1) {
                                draft.conversations.splice(index, 1);
                                draft.totalItems = Math.max(0, (draft.totalItems || 1) - 1);
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

        /**
         * Supprimer une conversation
         */
        deleteConversation: builder.mutation({
            query: (conversationId) => ({
                url: `contacts/conversations/${conversationId}`,
                method: 'DELETE',
            }),
            invalidatesTags: (result, error, conversationId) => [
                { type: 'Contact', id: conversationId },
                { type: 'ContactThread', id: conversationId },
                { type: 'Contact', id: 'LIST' },
                { type: 'Contact', id: 'UNREAD' },
                { type: 'ContactStats' }
            ],
            // Suppression immédiate de toutes les listes
            async onQueryStarted(conversationId, { dispatch, queryFulfilled }) {
                const listPatch = dispatch(
                    contactApi.util.updateQueryData('getMyConversations', {}, (draft) => {
                        if (draft.conversations) {
                            const index = draft.conversations.findIndex(c => c.id === conversationId);
                            if (index !== -1) {
                                draft.conversations.splice(index, 1);
                                draft.totalItems = Math.max(0, (draft.totalItems || 1) - 1);
                            }
                        }
                    })
                );

                const unreadPatch = dispatch(
                    contactApi.util.updateQueryData('getUnreadConversations', {}, (draft) => {
                        if (draft.conversations) {
                            const index = draft.conversations.findIndex(c => c.id === conversationId);
                            if (index !== -1) {
                                draft.conversations.splice(index, 1);
                                draft.totalUnread = Math.max(0, (draft.totalUnread || 1) - 1);
                            }
                        }
                    })
                );

                try {
                    await queryFulfilled;
                } catch {
                    listPatch.undo();
                    unreadPatch.undo();
                }
            },
        }),

        // ==========================================
        // MODÉRATION ET SIGNALEMENT
        // ==========================================

        /**
         * Bloquer un utilisateur
         */
        blockUser: builder.mutation({
            query: ({ conversationId, userId, reason }) => ({
                url: `contacts/conversations/${conversationId}/block`,
                method: 'POST',
                body: {
                    blockedUser: `/api/users/${userId}`,
                    reason,
                },
            }),
            invalidatesTags: (result, error, { conversationId }) => [
                { type: 'Contact', id: conversationId },
                { type: 'Contact', id: 'LIST' }
            ],
        }),

        /**
         * Signaler une conversation ou un message
         */
        reportConversation: builder.mutation({
            query: ({ conversationId, messageId, reason, description }) => ({
                url: `contacts/conversations/${conversationId}/report`,
                method: 'POST',
                body: {
                    messageId: messageId || null,
                    reason,
                    description,
                },
            }),
            transformResponse: (response) => {
                console.log('✅ Report submitted successfully');
                return response;
            },
        }),

        // ==========================================
        // RECHERCHE ET FILTRES
        // ==========================================

        /**
         * Rechercher dans les conversations
         */
        searchConversations: builder.query({
            query: ({ query, filters = {}, page = 1, limit = 20 }) => {
                const searchParams = new URLSearchParams();

                if (query) searchParams.append('search', query);
                searchParams.append('page', page);
                searchParams.append('limit', limit);

                // Filtres avancés
                if (filters.messageType) searchParams.append('messageType', filters.messageType);
                if (filters.status) searchParams.append('status', filters.status);
                if (filters.dateFrom) searchParams.append('dateFrom', filters.dateFrom);
                if (filters.dateTo) searchParams.append('dateTo', filters.dateTo);
                if (filters.propertyId) searchParams.append('property', filters.propertyId);
                if (filters.userId) searchParams.append('user', filters.userId);
                if (filters.priority) searchParams.append('priority', filters.priority);

                return `contacts/search?${searchParams.toString()}`;
            },
            transformResponse: (response) => {
                if (response['hydra:member']) {
                    return {
                        results: response['hydra:member'],
                        totalItems: response['hydra:totalItems'] || 0,
                        facets: response.facets || {},
                        suggestions: response.suggestions || [],
                        pagination: {
                            currentPage: response['hydra:view']?.['@id']?.match(/page=(\d+)/)?.[1] || 1,
                            totalItems: response['hydra:totalItems'] || 0,
                        }
                    };
                }
                return {
                    results: [],
                    totalItems: 0,
                    facets: {},
                    suggestions: [],
                    pagination: { currentPage: 1, totalItems: 0 }
                };
            },
            providesTags: [{ type: 'Contact', id: 'SEARCH' }],
            // Sérialiser les arguments pour éviter les requêtes en double
            serializeQueryArgs: ({ endpointName, queryArgs }) => {
                return `${endpointName}-${JSON.stringify(queryArgs)}`;
            },
        }),

        // ==========================================
        // STATISTIQUES ET ANALYTICS
        // ==========================================

        /**
         * Obtenir les statistiques détaillées
         */
        getContactStats: builder.query({
            query: (params = {}) => {
                const { period = 'month' } = params;
                return `contacts/stats?period=${period}`;
            },
            transformResponse: (response) => ({
                totalConversations: response.totalConversations || 0,
                unreadConversations: response.unreadConversations || 0,
                newContactsToday: response.newContactsToday || 0,
                newContactsThisWeek: response.newContactsThisWeek || 0,
                newContactsThisMonth: response.newContactsThisMonth || 0,
                responseRate: response.responseRate || 0,
                averageResponseTime: response.averageResponseTime || 0,
                topMessageTypes: response.topMessageTypes || [],
                activityByDay: response.activityByDay || [],
                responseRateByType: response.responseRateByType || {},
                peakHours: response.peakHours || [],
            }),
            providesTags: [{ type: 'ContactStats' }],
            // Refresh automatique des stats toutes les 5 minutes
            pollingInterval: 5 * 60 * 1000,
        }),

        /**
         * Obtenir les métriques de performance
         */
        getPerformanceMetrics: builder.query({
            query: (params = {}) => {
                const { period = 'week', userId } = params;
                const searchParams = new URLSearchParams();
                searchParams.append('period', period);
                if (userId) searchParams.append('userId', userId);

                return `contacts/metrics?${searchParams.toString()}`;
            },
            transformResponse: (response) => ({
                responseRate: response.responseRate || 0,
                averageResponseTime: response.averageResponseTime || 0,
                conversionsRate: response.conversionsRate || 0,
                satisfactionScore: response.satisfactionScore || 0,
                trends: response.trends || {},
                comparison: response.comparison || {},
            }),
            providesTags: [{ type: 'ContactStats', id: 'METRICS' }],
        }),

        // ==========================================
        // TEMPLATES ET RÉPONSES RAPIDES
        // ==========================================

        /**
         * Obtenir les templates de réponse
         */
        getResponseTemplates: builder.query({
            query: (params = {}) => {
                const { type = 'all', active = true } = params;
                const searchParams = new URLSearchParams();
                if (type !== 'all') searchParams.append('type', type);
                if (active !== null) searchParams.append('active', active);

                return `contacts/templates?${searchParams.toString()}`;
            },
            transformResponse: (response) => {
                const templates = response['hydra:member'] || [];
                return {
                    templates,
                    byType: templates.reduce((acc, template) => {
                        if (!acc[template.messageType]) {
                            acc[template.messageType] = [];
                        }
                        acc[template.messageType].push(template);
                        return acc;
                    }, {}),
                    quick: templates.filter(t => t.isQuick),
                    default: templates.filter(t => t.isDefault)
                };
            },
            providesTags: [{ type: 'ContactTemplate', id: 'LIST' }],
            // Cache long pour les templates (changent rarement)
            keepUnusedDataFor: 10 * 60, // 10 minutes
        }),

        /**
         * Créer un template de réponse personnalisé
         */
        createResponseTemplate: builder.mutation({
            query: ({ name, content, messageType, isDefault = false, isQuick = false }) => ({
                url: 'contacts/templates',
                method: 'POST',
                body: {
                    name,
                    content,
                    messageType,
                    isDefault,
                    isQuick
                },
            }),
            invalidatesTags: [{ type: 'ContactTemplate', id: 'LIST' }],
        }),

        /**
         * Supprimer un template
         */
        deleteResponseTemplate: builder.mutation({
            query: (templateId) => ({
                url: `contacts/templates/${templateId}`,
                method: 'DELETE',
            }),
            invalidatesTags: [{ type: 'ContactTemplate', id: 'LIST' }],
        }),

        // ==========================================
        // EXPORT ET SAUVEGARDE
        // ==========================================

        /**
         * Exporter les conversations
         */
        exportConversations: builder.mutation({
            query: ({ format = 'csv', filters = {}, includeMessages = false }) => ({
                url: 'contacts/export',
                method: 'POST',
                body: {
                    format,
                    filters,
                    includeMessages
                },
            }),
            transformResponse: (response) => ({
                downloadUrl: response.downloadUrl,
                expiresAt: response.expiresAt,
                fileSize: response.fileSize,
                recordCount: response.recordCount
            }),
        }),

        /**
         * Obtenir l'historique des exports
         */
        getExportHistory: builder.query({
            query: () => 'contacts/exports/history',
            transformResponse: (response) => response['hydra:member'] || [],
        }),

        // ==========================================
        // PRÉFÉRENCES ET PARAMÈTRES
        // ==========================================

        /**
         * Mettre à jour les préférences de notification
         */
        updateNotificationSettings: builder.mutation({
            query: (settings) => ({
                url: 'contacts/notification-settings',
                method: 'PATCH',
                body: settings,
            }),
            // Mettre à jour le cache des préférences utilisateur
            async onQueryStarted(settings, { dispatch, queryFulfilled }) {
                try {
                    await queryFulfilled;
                    // Vous pouvez mettre à jour un cache des préférences utilisateur ici
                } catch (error) {
                    console.error('Failed to update notification settings:', error);
                }
            },
        }),

        /**
         * Obtenir les préférences de notification actuelles
         */
        getNotificationSettings: builder.query({
            query: () => 'contacts/notification-settings',
            transformResponse: (response) => ({
                emailNotifications: response.emailNotifications ?? true,
                pushNotifications: response.pushNotifications ?? true,
                weeklyDigest: response.weeklyDigest ?? true,
                reminders: response.reminders ?? true,
                newMessages: response.newMessages ?? true,
                quietHours: {
                    enabled: response.quietHours?.enabled ?? false,
                    start: response.quietHours?.start ?? '22:00',
                    end: response.quietHours?.end ?? '08:00'
                },
                ...response
            }),
            keepUnusedDataFor: 5 * 60, // 5 minutes de cache
        }),

        // ==========================================
        // ACTIONS EN LOT (BULK ACTIONS)
        // ==========================================

        /**
         * Actions en lot sur plusieurs conversations
         */
        bulkActionsConversations: builder.mutation({
            query: ({ conversationIds, action, options = {} }) => ({
                url: 'contacts/bulk-actions',
                method: 'POST',
                body: {
                    conversationIds,
                    action, // 'mark_read', 'archive', 'delete', 'mark_spam'
                    options
                },
            }),
            transformResponse: (response) => ({
                success: response.success || false,
                processed: response.processed || 0,
                failed: response.failed || 0,
                errors: response.errors || []
            }),
            invalidatesTags: [
                { type: 'Contact', id: 'LIST' },
                { type: 'Contact', id: 'UNREAD' },
                { type: 'ContactStats' }
            ],
            // Mise à jour optimiste selon l'action
            async onQueryStarted({ conversationIds, action }, { dispatch, queryFulfilled }) {
                let patchResults = [];

                // Mise à jour optimiste selon le type d'action
                switch (action) {
                    case 'mark_read':
                        const readPatch = dispatch(
                            contactApi.util.updateQueryData('getMyConversations', {}, (draft) => {
                                if (draft.conversations) {
                                    draft.conversations.forEach(conv => {
                                        if (conversationIds.includes(conv.id)) {
                                            conv.isRead = true;
                                            conv.readAt = new Date().toISOString();
                                        }
                                    });
                                }
                            })
                        );
                        patchResults.push(readPatch);
                        break;

                    case 'archive':
                    case 'delete':
                        const removePatch = dispatch(
                            contactApi.util.updateQueryData('getMyConversations', {}, (draft) => {
                                if (draft.conversations) {
                                    draft.conversations = draft.conversations.filter(
                                        conv => !conversationIds.includes(conv.id)
                                    );
                                    draft.totalItems = Math.max(0, (draft.totalItems || 0) - conversationIds.length);
                                }
                            })
                        );
                        patchResults.push(removePatch);
                        break;
                }

                try {
                    await queryFulfilled;
                } catch (error) {
                    // Rollback toutes les modifications en cas d'erreur
                    patchResults.forEach(patch => patch.undo());
                }
            },
        }),

        // ==========================================
        // FONCTIONNALITÉS AVANCÉES
        // ==========================================

        /**
         * Obtenir les suggestions de réponse automatique (IA)
         */
        getResponseSuggestions: builder.query({
            query: ({ conversationId, messageContext }) => ({
                url: `contacts/conversations/${conversationId}/suggestions`,
                method: 'POST',
                body: { messageContext }
            }),
            transformResponse: (response) => ({
                suggestions: response.suggestions || [],
                confidence: response.confidence || 0,
                templates: response.relatedTemplates || []
            }),
            // Ne pas mettre en cache les suggestions (contextuelles)
            keepUnusedDataFor: 0,
        }),

        /**
         * Analyser le sentiment d'une conversation
         */
        analyzeSentiment: builder.query({
            query: (conversationId) => `contacts/conversations/${conversationId}/sentiment`,
            transformResponse: (response) => ({
                overallSentiment: response.sentiment || 'neutral',
                score: response.score || 0,
                messagesSentiment: response.messages || [],
                trends: response.trends || [],
                recommendations: response.recommendations || []
            }),
            keepUnusedDataFor: 10 * 60, // Cache 10 minutes
        }),

        /**
         * Obtenir des insights et recommandations
         */
        getConversationInsights: builder.query({
            query: (params = {}) => {
                const { period = 'month', limit = 10 } = params;
                return `contacts/insights?period=${period}&limit=${limit}`;
            },
            transformResponse: (response) => ({
                insights: response.insights || [],
                recommendations: response.recommendations || [],
                trends: response.trends || {},
                opportunities: response.opportunities || [],
                alerts: response.alerts || []
            }),
            providesTags: [{ type: 'ContactStats', id: 'INSIGHTS' }],
            // Refresh quotidien des insights
            pollingInterval: 24 * 60 * 60 * 1000, // 24 heures
        }),

        /**
         * Marquer une conversation comme prioritaire
         */
        setPriority: builder.mutation({
            query: ({ conversationId, priority }) => ({
                url: `contacts/conversations/${conversationId}/priority`,
                method: 'PATCH',
                body: { priority }
            }),
            invalidatesTags: (result, error, { conversationId }) => [
                { type: 'Contact', id: conversationId },
                { type: 'ContactThread', id: conversationId }
            ],
            // Mise à jour immédiate de la priorité
            async onQueryStarted({ conversationId, priority }, { dispatch, queryFulfilled }) {
                const patchResult = dispatch(
                    contactApi.util.updateQueryData('getMyConversations', {}, (draft) => {
                        if (draft.conversations) {
                            const conversation = draft.conversations.find(c => c.id === conversationId);
                            if (conversation) {
                                conversation.priority = priority;
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

        /**
         * Programmer un rappel pour une conversation
         */
        scheduleReminder: builder.mutation({
            query: ({ conversationId, reminderDate, message }) => ({
                url: `contacts/conversations/${conversationId}/remind`,
                method: 'POST',
                body: {
                    reminderDate,
                    message
                }
            }),
            transformResponse: (response) => ({
                reminderId: response.id,
                scheduledAt: response.scheduledAt,
                message: response.message
            }),
        }),

        /**
         * Obtenir les rappels programmés
         */
        getScheduledReminders: builder.query({
            query: () => 'contacts/reminders',
            transformResponse: (response) => response['hydra:member'] || [],
            providesTags: [{ type: 'Contact', id: 'REMINDERS' }],
        }),

        /**
         * Annuler un rappel programmé
         */
        cancelReminder: builder.mutation({
            query: (reminderId) => ({
                url: `contacts/reminders/${reminderId}`,
                method: 'DELETE'
            }),
            invalidatesTags: [{ type: 'Contact', id: 'REMINDERS' }],
        }),

        // ==========================================
        // INTÉGRATIONS ET WEBHOOKS
        // ==========================================

        /**
         * Configurer les webhooks pour les événements
         */
        configureWebhook: builder.mutation({
            query: ({ url, events, secret }) => ({
                url: 'contacts/webhooks',
                method: 'POST',
                body: {
                    url,
                    events, // ['new_contact', 'new_message', 'contact_read']
                    secret
                }
            }),
        }),

        /**
         * Tester la connectivité d'un webhook
         */
        testWebhook: builder.mutation({
            query: (webhookId) => ({
                url: `contacts/webhooks/${webhookId}/test`,
                method: 'POST'
            }),
            transformResponse: (response) => ({
                success: response.success,
                responseTime: response.responseTime,
                statusCode: response.statusCode,
                error: response.error
            }),
        }),

        // ==========================================
        // FONCTIONS UTILITAIRES
        // ==========================================

        /**
         * Vérifier la disponibilité d'un utilisateur pour être contacté
         */
        checkContactAvailability: builder.query({
            query: ({ userId, messageType }) => {
                const params = new URLSearchParams();
                if (messageType) params.append('messageType', messageType);
                return `contacts/availability/${userId}?${params.toString()}`;
            },
            transformResponse: (response) => ({
                available: response.available || false,
                reason: response.reason || null,
                nextAvailableAt: response.nextAvailableAt || null,
                limits: response.limits || {},
                suggestions: response.suggestions || []
            }),
            // Cache court car la disponibilité peut changer rapidement
            keepUnusedDataFor: 30, // 30 secondes
        }),

        /**
         * Prévisualiser un message avant envoi
         */
        previewMessage: builder.mutation({
            query: ({ recipientId, messageType, subject, message }) => ({
                url: 'contacts/preview',
                method: 'POST',
                body: {
                    recipientId,
                    messageType,
                    subject,
                    message
                }
            }),
            transformResponse: (response) => ({
                preview: response.preview,
                warnings: response.warnings || [],
                suggestions: response.suggestions || [],
                estimatedDeliveryTime: response.estimatedDeliveryTime
            }),
        }),

        /**
         * Obtenir l'historique des interactions avec un utilisateur
         */
        getUserInteractionHistory: builder.query({
            query: ({ userId, limit = 10 }) =>
                `contacts/history/${userId}?limit=${limit}`,
            transformResponse: (response) => ({
                interactions: response['hydra:member'] || [],
                totalInteractions: response['hydra:totalItems'] || 0,
                firstInteraction: response.firstInteraction,
                lastInteraction: response.lastInteraction,
                averageResponseTime: response.averageResponseTime,
                responseRate: response.responseRate
            }),
            keepUnusedDataFor: 5 * 60, // 5 minutes
        }),
    }),
});

// ==========================================
// EXPORT DES HOOKS
// ==========================================

export const {
    // Création et envoi
    useContactSellerMutation,
    useReplyToConversationMutation,
    usePreviewMessageMutation,

    // Lecture et consultation
    useGetMyConversationsQuery,
    useGetConversationQuery,
    useGetUnreadConversationsQuery,
    useSearchConversationsQuery,

    // Actions sur conversations
    useMarkConversationAsReadMutation,
    useMarkAllConversationsAsReadMutation,
    useArchiveConversationMutation,
    useDeleteConversationMutation,
    useSetPriorityMutation,

    // Modération
    useBlockUserMutation,
    useReportConversationMutation,
    useBulkActionsConversationsMutation,

    // Statistiques et analytics
    useGetContactStatsQuery,
    useGetPerformanceMetricsQuery,
    useGetConversationInsightsQuery,
    useAnalyzeSentimentQuery,

    // Templates et réponses
    useGetResponseTemplatesQuery,
    useCreateResponseTemplateMutation,
    useDeleteResponseTemplateMutation,
    useGetResponseSuggestionsQuery,

    // Paramètres et préférences
    useGetNotificationSettingsQuery,
    useUpdateNotificationSettingsMutation,

    // Rappels et planification
    useScheduleReminderMutation,
    useGetScheduledRemindersQuery,
    useCancelReminderMutation,

    // Export et sauvegarde
    useExportConversationsMutation,
    useGetExportHistoryQuery,

    // Fonctionnalités avancées
    useCheckContactAvailabilityQuery,
    useGetUserInteractionHistoryQuery,

    // Intégrations
    useConfigureWebhookMutation,
    useTestWebhookMutation,
} = contactApi;

// ==========================================
// HELPERS ET UTILITAIRES
// ==========================================

/**
 * Helper pour obtenir le statut d'une conversation
 */
export const getConversationStatus = (conversation) => {
    if (!conversation) return 'unknown';

    if (conversation.status === 'blocked') return 'blocked';
    if (conversation.status === 'archived') return 'archived';
    if (conversation.status === 'spam') return 'spam';
    if (!conversation.isRead) return 'unread';
    if (conversation.priority === 'urgent') return 'urgent';
    if (conversation.priority === 'high') return 'high';

    return 'active';
};

/**
 * Helper pour formater le temps de réponse
 */
export const formatResponseTime = (minutes) => {
    if (!minutes) return 'Non défini';

    if (minutes < 60) return `${Math.round(minutes)} min`;
    if (minutes < 1440) return `${Math.round(minutes / 60)} h`;

    return `${Math.round(minutes / 1440)} jour${minutes >= 2880 ? 's' : ''}`;
};

/**
 * Helper pour obtenir la couleur selon le type de message
 */
export const getMessageTypeColor = (messageType) => {
    const colors = {
        'visit_request': '#4CAF50',
        'info_request': '#2196F3',
        'price_negotiation': '#FF9800',
        'other': '#9C27B0'
    };

    return colors[messageType] || '#6C757D';
};

/**
 * Helper pour obtenir l'icône selon le type de message
 */
export const getMessageTypeIcon = (messageType) => {
    const icons = {
        'visit_request': 'home-search',
        'info_request': 'information-outline',
        'price_negotiation': 'handshake-outline',
        'other': 'message-text-outline'
    };

    return icons[messageType] || 'message-outline';
};

/**
 * Sélecteurs réutilisables
 */
export const selectUnreadCount = (state) => {
    return contactApi.endpoints.getUnreadConversations.select()(state)?.data?.totalUnread || 0;
};

export const selectTotalConversations = (state) => {
    return contactApi.endpoints.getContactStats.select()(state)?.data?.totalConversations || 0;
};

export const selectResponseRate = (state) => {
    return contactApi.endpoints.getContactStats.select()(state)?.data?.responseRate || 0;
};

/**
 * Configuration par défaut pour les requêtes
 */
export const defaultQueryConfig = {
    refetchOnMountOrArgChange: true,
    refetchOnReconnect: true,
    refetchOnFocus: false, // Éviter trop de requêtes
};

/**
 * Configuration pour les mutations
 */
export const defaultMutationConfig = {
    fixedCacheKey: 'shared-mutation',
};

export default contactApi;