import React, { useState, useCallback, useEffect, useMemo } from 'react';
import {
    View,
    StyleSheet,
    FlatList,
    RefreshControl,
    TouchableOpacity,
    Alert,
    Platform
} from 'react-native';
import {
    useTheme,
    ActivityIndicator,
    Card,
    Badge,
    IconButton,
    Chip,
    Button,
    SegmentedButtons,
    FAB,
    Portal,
    Dialog,
    Divider,
    Searchbar
} from 'react-native-paper';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { useTranslation } from 'react-i18next';
import { useRouter } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { format, formatDistanceToNow } from 'date-fns';
import { fr } from 'date-fns/locale';

import { SafeScreen } from "@components/layout/SafeScreen";
import Text from '@/components/typography/Text';
import { LoadingScreen } from "@components/Loading";
import { useNotifications } from '@/hooks/useNotifications';
import {
    useGetNotificationsQuery,
    useMarkNotificationAsReadMutation,
    useMarkAllNotificationsAsReadMutation,
    useDeleteNotificationMutation,
    useDeleteReadNotificationsMutation
} from '@/redux/api/notificationApi';
import { SPACING, ELEVATION, BORDER_RADIUS } from '@/constants/spacing';

// Hook personnalisé pour la logique des notifications
const useNotificationLogic = () => {
    const [filter, setFilter] = useState('all');
    const [page, setPage] = useState(1);
    const [searchQuery, setSearchQuery] = useState('');
    const [showActions, setShowActions] = useState(false);
    const [refreshing, setRefreshing] = useState(false);

    // Utilisation du hook personnalisé pour les notifications
    const {
        totalUnreadCount,
        markNotificationAsRead: markAsReadFromHook,
        refetchNotifications
    } = useNotifications();

    // Query RTK pour les notifications avec params dynamiques
    const queryParams = useMemo(() => ({
        page,
        filter,
        limit: 20,
        ...(searchQuery && { search: searchQuery })
    }), [page, filter, searchQuery]);

    const {
        data,
        isLoading,
        isFetching,
        error,
        refetch
    } = useGetNotificationsQuery(queryParams, {
        refetchOnMountOrArgChange: true,
        refetchOnFocus: true,
    });

    // Mutations
    const [markAsRead] = useMarkNotificationAsReadMutation();
    const [markAllAsRead] = useMarkAllNotificationsAsReadMutation();
    const [deleteNotification] = useDeleteNotificationMutation();
    const [deleteReadNotifications] = useDeleteReadNotificationsMutation();

    // Données dérivées
    const notifications = data?.notifications || [];
    const unreadCount = data?.unreadCount || 0;
    const pagination = data?.pagination || {};

    useEffect(() => {
        console.log('🔍 Debug notifications:', {
            isLoading,
            isFetching,
            error,
            data,
            notifications: data?.notifications,
            count: data?.notifications?.length
        });
    }, [data, isLoading, isFetching, error]);

    // Force refresh avec invalidation des caches
    const onRefresh = useCallback(async () => {
        setRefreshing(true);
        try {
            await Promise.all([
                refetch(),
                refetchNotifications()
            ]);
        } catch (error) {
            console.error('❌ Erreur lors du refresh:', error);
        } finally {
            setRefreshing(false);
        }
    }, [refetch, refetchNotifications]);

    // Gestion des actions
    const handleMarkAsRead = useCallback(async (notificationId) => {
        try {
            await markAsRead(notificationId).unwrap();
            // Utiliser aussi le hook pour la mise à jour globale
            markAsReadFromHook(notificationId);
        } catch (error) {
            console.error('❌ Error marking notification as read:', error);
        }
    }, [markAsRead, markAsReadFromHook]);

    const handleMarkAllAsRead = useCallback(async () => {
        try {
            await markAllAsRead().unwrap();
            await refetchNotifications();
        } catch (error) {
            console.error('❌ Error marking all as read:', error);
        }
    }, [markAllAsRead, refetchNotifications]);

    const handleDeleteAllRead = useCallback(async () => {
        try {
            await deleteReadNotifications().unwrap();
            await refetchNotifications();
        } catch (error) {
            console.error('❌ Error deleting read notifications:', error);
        }
    }, [deleteReadNotifications, refetchNotifications]);

    const handleLoadMore = useCallback(() => {
        if (page < pagination.pages && !isFetching) {
            setPage(prev => prev + 1);
        }
    }, [page, pagination.pages, isFetching]);

    // Reset page when filter changes
    useEffect(() => {
        setPage(1);
    }, [filter, searchQuery]);

    return {
        // State
        filter,
        setFilter,
        searchQuery,
        setSearchQuery,
        showActions,
        setShowActions,
        refreshing,

        // Data
        notifications,
        unreadCount,
        totalUnreadCount,
        pagination,
        isLoading,
        isFetching,

        // Actions
        onRefresh,
        handleMarkAsRead,
        handleMarkAllAsRead,
        handleDeleteAllRead,
        handleLoadMore,
        deleteNotification
    };
};

// Composant NotificationItem optimisé
const NotificationItem = React.memo(({ notification, onRead, onDelete }) => {
    const { colors } = useTheme();
    const { t } = useTranslation();
    const router = useRouter();

    const getPriorityColor = (priority) => {
        switch (priority) {
            case 'error': return colors.error;
            case 'warning': return colors.tertiary;
            case 'success': return colors.primary;
            case 'info': return colors.secondary;
            default: return colors.onSurfaceVariant;
        }
    };

    const getIcon = () => {
        const iconMap = {
            'email_confirmation': 'email-check-outline',
            'welcome': 'hand-wave-outline',
            'documents_submitted': 'file-document-check-outline',
            'documents_approved': 'check-decagram-outline',
            'documents_rejected': 'close-octagon-outline',
            'profile_reminder': 'account-alert-outline',
            'message_received': 'message-text-outline',
            'contact_received': 'message-reply-text-outline',
            'contact_reminder': 'alarm-check',
            'listing_approved': 'home-check-outline',
            'listing_rejected': 'home-remove-outline',
            'admin_message': 'shield-account-outline',
            'system': 'cog-outline'
        };
        return iconMap[notification.type] || 'bell-outline';
    };

    const handlePress = useCallback(() => {
        if (!notification.isRead) {
            onRead(notification.id);
        }

        const actionUrl = notification.actionUrl || notification.effectiveActionUrl;
        if (actionUrl) {
            router.push(actionUrl);
        }
    }, [notification, onRead, router]);

    const handleDelete = useCallback(() => {
        onDelete(notification);
    }, [notification, onDelete]);

    return (
        <TouchableOpacity onPress={handlePress} activeOpacity={0.7}>
            <Card
                style={[
                    styles.notificationCard,
                    {
                        backgroundColor: notification.isRead
                            ? colors.surface
                            : colors.secondaryContainer + '20',
                        borderLeftColor: getPriorityColor(notification.priority),
                        borderLeftWidth: 4
                    }
                ]}
                elevation={notification.isRead ? ELEVATION.low : ELEVATION.medium}
            >
                <Card.Content style={styles.cardContent}>
                    <View style={styles.notificationHeader}>
                        <View style={styles.iconContainer}>
                            <MaterialCommunityIcons
                                name={getIcon()}
                                size={24}
                                color={getPriorityColor(notification.priority)}
                            />
                            {!notification.isRead && (
                                <Badge
                                    size={8}
                                    style={[
                                        styles.unreadBadge,
                                        { backgroundColor: colors.primary }
                                    ]}
                                />
                            )}
                        </View>

                        <View style={styles.notificationContent}>
                            <Text
                                variant="bodyLarge"
                                color="textPrimary"
                                style={[
                                    styles.notificationTitle,
                                    {
                                        fontWeight: notification.isRead ? '500' : '700',
                                        color: notification.isRead
                                            ? colors.onSurface
                                            : colors.onSurface
                                    }
                                ]}
                                numberOfLines={1}
                            >
                                {notification.title}
                            </Text>

                            <Text
                                variant="bodyMedium"
                                color="textSecondary"
                                numberOfLines={2}
                                style={styles.notificationMessage}
                            >
                                {notification.message}
                            </Text>

                            <View style={styles.notificationFooter}>
                                <Text variant="labelSmall" color="textHint">
                                    {formatDistanceToNow(new Date(notification.createdAt), {
                                        addSuffix: true,
                                        locale: fr
                                    })}
                                </Text>

                                {(notification.actionLabel || notification.effectiveActionLabel) && (
                                    <Chip
                                        mode="outlined"
                                        compact
                                        textStyle={styles.actionChipText}
                                        style={[
                                            styles.actionChip,
                                            { borderColor: getPriorityColor(notification.priority) }
                                        ]}
                                    >
                                        {notification.actionLabel || notification.effectiveActionLabel}
                                    </Chip>
                                )}
                            </View>
                        </View>

                        <IconButton
                            icon="delete-outline"
                            size={20}
                            onPress={handleDelete}
                            style={styles.deleteButton}
                            iconColor={colors.onSurfaceVariant}
                        />
                    </View>
                </Card.Content>
            </Card>
        </TouchableOpacity>
    );
});

NotificationItem.displayName = 'NotificationItem';

// Composant principal InboxScreen
export default function InboxScreen() {
    const { colors } = useTheme();
    const { t } = useTranslation();

    const {
        filter,
        setFilter,
        searchQuery,
        setSearchQuery,
        showActions,
        setShowActions,
        refreshing,
        notifications,
        unreadCount,
        totalUnreadCount,
        pagination,
        isLoading,
        isFetching,
        onRefresh,
        handleMarkAsRead,
        handleMarkAllAsRead,
        handleDeleteAllRead,
        handleLoadMore,
        deleteNotification
    } = useNotificationLogic();

    // Gestion de la suppression avec confirmation
    const handleDelete = useCallback((notification) => {
        Alert.alert(
            t('notifications.deleteTitle'),
            t('notifications.deleteMessage'),
            [
                { text: t('common.cancel'), style: 'cancel' },
                {
                    text: t('common.delete'),
                    style: 'destructive',
                    onPress: async () => {
                        try {
                            await deleteNotification(notification.id).unwrap();
                        } catch (error) {
                            Alert.alert(t('common.error'), t('notifications.deleteError'));
                        }
                    }
                }
            ]
        );
    }, [deleteNotification, t]);

    // Actions du FAB avec confirmations
    const handleMarkAllAsReadAction = useCallback(() => {
        setShowActions(false);
        if (unreadCount === 0) return;

        Alert.alert(
            t('notifications.markAllAsReadTitle'),
            t('notifications.markAllAsReadMessage'),
            [
                { text: t('common.cancel'), style: 'cancel' },
                {
                    text: t('common.confirm'),
                    style: 'default',
                    onPress: async () => {
                        try {
                            await handleMarkAllAsRead();
                            Alert.alert(t('common.success'), t('notifications.allMarkedAsRead'));
                        } catch (error) {
                            Alert.alert(t('common.error'), t('notifications.markAllError'));
                        }
                    }
                }
            ]
        );
    }, [handleMarkAllAsRead, unreadCount, setShowActions, t]);

    const handleDeleteAllReadAction = useCallback(() => {
        setShowActions(false);

        Alert.alert(
            t('notifications.deleteAllReadTitle'),
            t('notifications.deleteAllReadMessage'),
            [
                { text: t('common.cancel'), style: 'cancel' },
                {
                    text: t('common.delete'),
                    style: 'destructive',
                    onPress: async () => {
                        try {
                            await handleDeleteAllRead();
                            Alert.alert(t('common.success'), t('notifications.allReadDeleted'));
                        } catch (error) {
                            Alert.alert(t('common.error'), t('notifications.deleteAllError'));
                        }
                    }
                }
            ]
        );
    }, [handleDeleteAllRead, setShowActions, t]);

    // Rendu des éléments
    const renderNotification = useCallback(({ item }) => (
        <NotificationItem
            notification={item}
            onRead={handleMarkAsRead}
            onDelete={handleDelete}
        />
    ), [handleMarkAsRead, handleDelete]);

    const renderEmptyState = useCallback(() => (
        <View style={styles.emptyContainer}>
            <View style={styles.emptyIconContainer}>
                <Text variant="heroTitle" style={styles.emptyIcon}>
                    🔔
                </Text>
            </View>
            <Text variant="pageTitle" color="textPrimary" style={styles.emptyTitle}>
                {filter === 'unread'
                    ? t('notifications.noUnread')
                    : t('notifications.empty')
                }
            </Text>
            <Text variant="bodyLarge" color="textSecondary" style={styles.emptyDescription}>
                {filter === 'unread'
                    ? t('notifications.noUnreadDescription')
                    : t('notifications.emptyDescription')
                }
            </Text>
        </View>
    ), [filter, t]);

    const renderHeader = useCallback(() => (
        <View style={styles.header}>
            <View style={styles.headerTop}>
                <Text variant="pageTitle" color="textPrimary">
                    {t('notifications.title')}
                </Text>
                {totalUnreadCount > 0 && (
                    <Badge
                        style={{ backgroundColor: colors.primary }}
                        size={24}
                    >
                        {totalUnreadCount > 99 ? '99+' : totalUnreadCount}
                    </Badge>
                )}
            </View>

            {/* Barre de recherche */}
            <Searchbar
                placeholder={t('notifications.searchPlaceholder')}
                onChangeText={setSearchQuery}
                value={searchQuery}
                style={[
                    styles.searchBar,
                    { backgroundColor: colors.surfaceVariant }
                ]}
                inputStyle={{ color: colors.onSurface }}
                iconColor={colors.onSurfaceVariant}
                placeholderTextColor={colors.onSurfaceVariant}
                elevation={0}
            />

            {/* Filtres segmentés */}
            <SegmentedButtons
                value={filter}
                onValueChange={setFilter}
                buttons={[
                    {
                        value: 'all',
                        label: t('notifications.all'),
                        style: {
                            backgroundColor: filter === 'all'
                                ? colors.primaryContainer
                                : colors.surface
                        }
                    },
                    {
                        value: 'unread',
                        label: `${t('notifications.unread')} ${unreadCount > 0 ? `(${unreadCount})` : ''}`,
                        style: {
                            backgroundColor: filter === 'unread'
                                ? colors.primaryContainer
                                : colors.surface
                        }
                    },
                    {
                        value: 'read',
                        label: t('notifications.read'),
                        style: {
                            backgroundColor: filter === 'read'
                                ? colors.primaryContainer
                                : colors.surface
                        }
                    }
                ]}
                style={styles.segmentedButtons}
            />
        </View>
    ), [
        filter,
        setFilter,
        searchQuery,
        setSearchQuery,
        totalUnreadCount,
        unreadCount,
        colors,
        t
    ]);

    const renderFooter = useCallback(() => (
        isFetching && notifications.length > 0 ? (
            <View style={styles.footerLoader}>
                <ActivityIndicator size="small" color={colors.primary} />
                <Text variant="labelMedium" color="textSecondary" style={styles.loadingText}>
                    {t('common.loading')}
                </Text>
            </View>
        ) : null
    ), [isFetching, notifications.length, colors.primary, t]);

    // Configuration du RefreshControl
    const refreshControlProps = useMemo(() => ({
        refreshing,
        onRefresh,
        colors: [colors.primary],
        tintColor: colors.primary,
        progressBackgroundColor: colors.surface,
        ...(Platform.OS === 'ios' && {
            title: t('common.pullToRefresh'),
            titleColor: colors.onSurface,
        }),
    }), [refreshing, onRefresh, colors, t]);

    // État de chargement initial
    if (isLoading && notifications.length === 0) {
        return (
            <SafeScreen>
                <LoadingScreen />
            </SafeScreen>
        );
    }

    return (
        <SafeScreen>
            <LinearGradient colors={[colors.background, colors.surface]} style={styles.container}>
                <FlatList
                    data={notifications}
                    renderItem={renderNotification}
                    keyExtractor={(item) => item.id.toString()}
                    ListHeaderComponent={renderHeader}
                    ListEmptyComponent={renderEmptyState}
                    ListFooterComponent={renderFooter}
                    contentContainerStyle={[
                        styles.listContent,
                        notifications.length === 0 && styles.emptyListContent
                    ]}
                    refreshControl={<RefreshControl {...refreshControlProps} />}
                    onEndReached={handleLoadMore}
                    onEndReachedThreshold={0.5}
                    showsVerticalScrollIndicator={false}
                    initialNumToRender={10}
                    maxToRenderPerBatch={10}
                    windowSize={5}
                    removeClippedSubviews={Platform.OS === 'android'}
                />

                {notifications.length > 0 && (
                    <FAB
                        icon="dots-vertical"
                        style={[
                            styles.fab,
                            { backgroundColor: colors.primaryContainer }
                        ]}
                        onPress={() => setShowActions(true)}
                        color={colors.onPrimaryContainer}
                        size="medium"
                    />
                )}

                <Portal>
                    <Dialog
                        visible={showActions}
                        onDismiss={() => setShowActions(false)}
                        style={styles.dialog}
                    >
                        <Dialog.Title>
                            <Text variant="headlineSmall" color="textPrimary">
                                {t('notifications.actions')}
                            </Text>
                        </Dialog.Title>
                        <Dialog.Content style={styles.dialogContent}>
                            <Button
                                mode="text"
                                onPress={handleMarkAllAsReadAction}
                                disabled={unreadCount === 0}
                                icon="check-all"
                                contentStyle={styles.dialogButton}
                                labelStyle={styles.dialogButtonLabel}
                                style={styles.dialogButtonContainer}
                            >
                                {t('notifications.markAllAsRead')}
                            </Button>
                            <Divider style={styles.dialogDivider} />
                            <Button
                                mode="text"
                                onPress={handleDeleteAllReadAction}
                                icon="delete-sweep"
                                textColor={colors.error}
                                contentStyle={styles.dialogButton}
                                labelStyle={styles.dialogButtonLabel}
                                style={styles.dialogButtonContainer}
                            >
                                {t('notifications.deleteAllRead')}
                            </Button>
                        </Dialog.Content>
                        <Dialog.Actions>
                            <Button
                                onPress={() => setShowActions(false)}
                                labelStyle={styles.dialogButtonLabel}
                            >
                                {t('common.cancel')}
                            </Button>
                        </Dialog.Actions>
                    </Dialog>
                </Portal>
            </LinearGradient>
        </SafeScreen>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    listContent: {
        flexGrow: 1,
        paddingBottom: SPACING.huge + 60, // Extra space for FAB
    },
    emptyListContent: {
        justifyContent: 'center',
    },
    header: {
        paddingHorizontal: SPACING.lg,
        paddingVertical: SPACING.lg,
        gap: SPACING.md,
        backgroundColor: 'transparent',
    },
    headerTop: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: SPACING.sm,
    },
    searchBar: {
        elevation: 0,
        borderRadius: BORDER_RADIUS.md,
        marginBottom: SPACING.sm,
    },
    segmentedButtons: {
        marginBottom: SPACING.sm,
    },
    notificationCard: {
        marginHorizontal: SPACING.lg,
        marginVertical: SPACING.xs,
        borderRadius: BORDER_RADIUS.lg,
        overflow: 'hidden',
    },
    cardContent: {
        paddingVertical: SPACING.md,
        paddingHorizontal: SPACING.md,
    },
    notificationHeader: {
        flexDirection: 'row',
        alignItems: 'flex-start',
    },
    iconContainer: {
        position: 'relative',
        marginRight: SPACING.md,
        padding: SPACING.xs,
        alignItems: 'center',
        justifyContent: 'center',
        width: 40,
        height: 40,
    },
    unreadBadge: {
        position: 'absolute',
        top: 2,
        right: 2,
    },
    notificationContent: {
        flex: 1,
        gap: SPACING.xs,
    },
    notificationTitle: {
        marginBottom: SPACING.xs,
        lineHeight: 20,
    },
    notificationMessage: {
        marginBottom: SPACING.xs,
        lineHeight: 18,
    },
    notificationFooter: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginTop: SPACING.sm,
    },
    actionChip: {
        height: 28,
        borderRadius: BORDER_RADIUS.sm,
    },
    actionChipText: {
        fontSize: 11,
        lineHeight: 14,
    },
    deleteButton: {
        margin: -SPACING.xs,
    },
    emptyContainer: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
        paddingVertical: SPACING.huge,
        paddingHorizontal: SPACING.xl,
        gap: SPACING.lg,
        minHeight: 400,
    },
    emptyIconContainer: {
        marginBottom: SPACING.md,
        alignItems: 'center',
        justifyContent: 'center',
        minHeight: 80,
        minWidth: 80,
    },
    emptyIcon: {
        fontSize: 64,
        lineHeight: 80,
        textAlign: 'center',
        includeFontPadding: false,
        textAlignVertical: 'center',
    },
    emptyTitle: {
        textAlign: 'center',
        marginBottom: SPACING.sm,
    },
    emptyDescription: {
        textAlign: 'center',
        lineHeight: 24,
        maxWidth: 300,
    },
    footerLoader: {
        paddingVertical: SPACING.lg,
        alignItems: 'center',
        gap: SPACING.sm,
    },
    loadingText: {
        textAlign: 'center',
    },
    fab: {
        position: 'absolute',
        margin: SPACING.lg,
        right: 0,
        bottom: SPACING.lg,
        borderRadius: BORDER_RADIUS.lg,
        elevation: ELEVATION.high,
    },
    dialog: {
        borderRadius: BORDER_RADIUS.lg,
    },
    dialogContent: {
        paddingHorizontal: 0,
    },
    dialogButtonContainer: {
        marginVertical: SPACING.xs,
    },
    dialogButton: {
        justifyContent: 'flex-start',
        paddingVertical: SPACING.sm,
        paddingHorizontal: SPACING.lg,
    },
    dialogButtonLabel: {
        fontSize: 14,
        lineHeight: 20,
    },
    dialogDivider: {
        marginVertical: SPACING.sm,
    },
});