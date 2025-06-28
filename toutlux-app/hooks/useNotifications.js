// hooks/useNotifications.js
import { useEffect, useCallback, useRef } from 'react';
import { useSelector } from 'react-redux';
import { AppState } from 'react-native';
import { useGetNotificationsQuery, useMarkNotificationAsReadMutation } from '@/redux/api/notificationApi';
import { useGetUnreadConversationsQuery } from '@/redux/api/contactApi';
import {useCurrentUser} from "@/hooks/useIsCurrentUser";

/**
 * Hook principal pour la gestion des notifications in-app uniquement
 */
export const useNotifications = () => {
    const { user, isAuthenticated } = useCurrentUser();
    const appState = useRef(AppState.currentState);

    // Queries pour les notifications
    const {
        data: notificationsData,
        isLoading: isLoadingNotifications,
        refetch: refetchNotifications
    } = useGetNotificationsQuery(
        { page: 1, limit: 20, filter: 'all' },
        {
            skip: !isAuthenticated,
            pollingInterval: 60000, // Polling toutes les minutes
            refetchOnMountOrArgChange: true,
            refetchOnFocus: true,
        }
    );

    const {
        data: unreadConversations,
        isLoading: isLoadingConversations
    } = useGetUnreadConversationsQuery(
        undefined,
        {
            skip: !isAuthenticated,
            pollingInterval: 30000, // Polling toutes les 30s
            refetchOnMountOrArgChange: true,
        }
    );

    const [markAsRead] = useMarkNotificationAsReadMutation();

    // Gestion des changements d'état de l'app
    useEffect(() => {
        const handleAppStateChange = (nextAppState) => {
            if (
                appState.current.match(/inactive|background/) &&
                nextAppState === 'active' &&
                isAuthenticated
            ) {
                console.log('📱 App revient au premier plan - refresh notifications');
                refetchNotifications();
            }
            appState.current = nextAppState;
        };

        const subscription = AppState.addEventListener('change', handleAppStateChange);
        return () => subscription?.remove();
    }, [isAuthenticated, refetchNotifications]);

    // Marquer une notification comme lue
    const markNotificationAsRead = useCallback(async (notificationId) => {
        try {
            await markAsRead(notificationId).unwrap();
            console.log('✅ Notification marquée comme lue:', notificationId);
        } catch (error) {
            console.error('❌ Erreur marquage notification comme lue:', error);
        }
    }, [markAsRead]);

    // Obtenir le nombre total de notifications non lues
    const getTotalUnreadCount = useCallback(() => {
        const notificationCount = notificationsData?.unreadCount || 0;
        const conversationCount = unreadConversations?.totalUnread || 0;
        return notificationCount + conversationCount;
    }, [notificationsData?.unreadCount, unreadConversations?.totalUnread]);

    // Log des changements de compteur pour debug
    useEffect(() => {
        const totalUnread = getTotalUnreadCount();
        if (totalUnread > 0) {
            console.log(`📊 Total notifications non lues: ${totalUnread}`);
        }
    }, [getTotalUnreadCount]);

    return {
        // Données
        notifications: notificationsData?.notifications || [],
        unreadNotificationCount: notificationsData?.unreadCount || 0,
        unreadConversationCount: unreadConversations?.totalUnread || 0,
        totalUnreadCount: getTotalUnreadCount(),

        // États
        isLoading: isLoadingNotifications || isLoadingConversations,

        // Actions
        markNotificationAsRead,
        refetchNotifications,

        // Métadonnées
        lastRefresh: notificationsData ? new Date() : null,
        hasError: false, // Vous pouvez ajouter la gestion d'erreur si nécessaire
    };
};

/**
 * Hook pour les actions sur les notifications (gestion des taps, navigation)
 */
export const useNotificationActions = () => {
    // Ce hook gérera la navigation quand une notification est tappée

    const handleNotificationPress = useCallback((notification) => {
        console.log('🎯 Notification tappée:', notification);

        // TODO: Intégrer avec votre système de navigation
        const actionUrl = notification.actionUrl || notification.effectiveActionUrl;

        if (actionUrl) {
            console.log('🔗 Navigation vers:', actionUrl);
            // router.push(actionUrl);
        }

        // Logique de navigation selon le type
        switch (notification.type) {
            case 'contact_received':
            case 'message_received':
                console.log('📱 Navigation vers conversation:', notification.data?.contact_id);
                // navigation.navigate('Conversations', { id: notification.data?.contact_id });
                break;

            case 'contact_reminder':
                console.log('📱 Navigation vers conversations non lues');
                // navigation.navigate('Conversations', { filter: 'unread' });
                break;

            case 'documents_approved':
            case 'documents_rejected':
                console.log('📱 Navigation vers documents');
                // navigation.navigate('Profile', { screen: 'Documents' });
                break;

            case 'profile_reminder':
                console.log('📱 Navigation vers profil');
                // navigation.navigate('Profile', { screen: 'Complete' });
                break;

            default:
                console.log('📱 Navigation par défaut vers notifications');
                // navigation.navigate('Notifications');
                break;
        }
    }, []);

    return {
        handleNotificationPress,
    };
};

/**
 * Hook pour les préférences de notification (sans push)
 */
export const useNotificationPreferences = () => {
    const preferences = useSelector(state => state.user?.notificationSettings || {});

    const defaultPreferences = {
        inAppNotifications: true,
        emailNotifications: true,
        newMessages: true,
        reminders: true,
        weeklyDigest: true,
        sound: false, // Pas de son sans push notifications
        vibration: false, // Pas de vibration sans push notifications
        quiet_hours_enabled: false,
        quiet_hours_start: '22:00',
        quiet_hours_end: '08:00',
    };

    const getPreference = useCallback((key) => {
        return preferences[key] ?? defaultPreferences[key];
    }, [preferences]);

    const isQuietHours = useCallback(() => {
        if (!getPreference('quiet_hours_enabled')) return false;

        const now = new Date();
        const currentTime = now.getHours() * 100 + now.getMinutes();

        const startTime = timeStringToMinutes(getPreference('quiet_hours_start'));
        const endTime = timeStringToMinutes(getPreference('quiet_hours_end'));

        if (startTime <= endTime) {
            return currentTime >= startTime && currentTime <= endTime;
        } else {
            // Période qui traverse minuit
            return currentTime >= startTime || currentTime <= endTime;
        }
    }, [getPreference]);

    const shouldShowNotification = useCallback((notificationType) => {
        if (isQuietHours()) return false;

        switch (notificationType) {
            case 'message_received':
            case 'contact_received':
                return getPreference('newMessages');
            case 'contact_reminder':
                return getPreference('reminders');
            default:
                return getPreference('inAppNotifications');
        }
    }, [getPreference, isQuietHours]);

    return {
        preferences: { ...defaultPreferences, ...preferences },
        getPreference,
        isQuietHours,
        shouldShowNotification,
    };
};

/**
 * Hook pour les statistiques et insights des notifications
 */
export const useNotificationStats = () => {
    const { notifications, totalUnreadCount } = useNotifications();

    const stats = useCallback(() => {
        if (!notifications || notifications.length === 0) {
            return {
                total: 0,
                unread: 0,
                byType: {},
                byPriority: {},
                todayCount: 0,
                weekCount: 0,
            };
        }

        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);

        const byType = {};
        const byPriority = {};
        let todayCount = 0;
        let weekCount = 0;

        notifications.forEach(notification => {
            const createdAt = new Date(notification.createdAt);

            // Compteurs par type
            byType[notification.type] = (byType[notification.type] || 0) + 1;

            // Compteurs par priorité
            byPriority[notification.priority] = (byPriority[notification.priority] || 0) + 1;

            // Compteurs temporels
            if (createdAt >= today) todayCount++;
            if (createdAt >= weekAgo) weekCount++;
        });

        return {
            total: notifications.length,
            unread: totalUnreadCount,
            byType,
            byPriority,
            todayCount,
            weekCount,
        };
    }, [notifications, totalUnreadCount]);

    return stats();
};

/**
 * Hook pour le debug et les tests (développement uniquement)
 */
export const useNotificationDebug = () => {
    const { notifications, totalUnreadCount, refetchNotifications } = useNotifications();

    const logNotificationState = useCallback(() => {
        console.log('📊 État des notifications:', {
            total: notifications.length,
            unread: totalUnreadCount,
            notifications: notifications.slice(0, 3), // Afficher seulement les 3 premières
        });
    }, [notifications, totalUnreadCount]);

    const forceRefresh = useCallback(async () => {
        console.log('🔄 Force refresh des notifications...');
        try {
            await refetchNotifications();
            console.log('✅ Refresh terminé');
        } catch (error) {
            console.error('❌ Erreur lors du refresh:', error);
        }
    }, [refetchNotifications]);

    const simulateNotificationTap = useCallback((type = 'test') => {
        const mockNotification = {
            id: Date.now(),
            type,
            title: 'Notification de test',
            message: 'Ceci est une notification de test',
            data: { contact_id: 123 },
            actionUrl: '/test',
            createdAt: new Date().toISOString(),
        };

        console.log('🧪 Simulation tap notification:', mockNotification);
        // handleNotificationPress(mockNotification);
    }, []);

    return {
        logNotificationState,
        forceRefresh,
        simulateNotificationTap,

        // Getters pour debug
        get notificationCount() { return notifications.length; },
        get unreadCount() { return totalUnreadCount; },
        get latestNotification() { return notifications[0]; },
    };
};

// Utilitaires
const timeStringToMinutes = (timeString) => {
    const [hours, minutes] = timeString.split(':').map(Number);
    return hours * 100 + minutes;
};