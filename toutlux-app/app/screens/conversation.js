import React, { useState } from 'react';
import {
    View,
    StyleSheet,
    FlatList,
    RefreshControl,
    TouchableOpacity,
} from 'react-native';
import {
    useTheme,
    Searchbar,
    SegmentedButtons,
    Badge,
    Avatar,
    ActivityIndicator,
} from 'react-native-paper';
import { useTranslation } from 'react-i18next';
import { useRouter } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { formatDistanceToNow } from 'date-fns';
import { fr } from 'date-fns/locale';

import { SafeScreen } from '@components/layout/SafeScreen';
import { useGetMyConversationsQuery } from '@/redux/api/contactApi';
import Text from '@/components/typography/Text';
import { SPACING, BORDER_RADIUS, ELEVATION } from '@/constants/spacing';
import {LoadingScreen} from "@components/Loading";

const FILTER_OPTIONS = [
    { label: 'conversations.filters.all', value: 'all' },
    { label: 'conversations.filters.unread', value: 'unread' },
    { label: 'conversations.filters.archived', value: 'archived' },
];

const ConversationItem = ({ conversation, onPress }) => {
    const { colors } = useTheme();
    const { t } = useTranslation();

    const getMessageTypeIcon = (type) => {
        const iconMap = {
            'visit_request': 'home-search',
            'info_request': 'information',
            'price_negotiation': 'handshake',
            'other': 'message-text',
        };
        return iconMap[type] || 'message-text';
    };

    const getMessageTypeColor = (type) => {
        const colorMap = {
            'visit_request': colors.primary,
            'info_request': colors.tertiary,
            'price_negotiation': '#ff9800',
            'other': colors.textSecondary,
        };
        return colorMap[type] || colors.textSecondary;
    };

    const otherParticipant = conversation.sender.id !== conversation.currentUserId
        ? conversation.sender
        : conversation.recipient;

    return (
        <TouchableOpacity
            style={[
                styles.conversationItem,
                {
                    backgroundColor: conversation.isRead
                        ? colors.surface
                        : colors.secondaryContainer + '20',
                    borderColor: colors.outline
                }
            ]}
            onPress={onPress}
            activeOpacity={0.7}
        >
            <View style={styles.avatarContainer}>
                <Avatar.Text
                    size={48}
                    label={otherParticipant.firstName?.charAt(0) || 'U'}
                    style={{ backgroundColor: colors.primary }}
                />
                {!conversation.isRead && (
                    <Badge
                        style={[styles.unreadBadge, { backgroundColor: colors.primary }]}
                        size={12}
                    />
                )}
            </View>

            <View style={styles.conversationContent}>
                <View style={styles.conversationHeader}>
                    <Text
                        variant="cardTitle"
                        color="textPrimary"
                        style={[
                            styles.participantName,
                            { fontWeight: conversation.isRead ? 'normal' : 'bold' }
                        ]}
                        numberOfLines={1}
                    >
                        {otherParticipant.firstName} {otherParticipant.lastName}
                    </Text>
                    <Text variant="labelSmall" color="textSecondary" style={styles.timestamp}>
                        {formatDistanceToNow(new Date(conversation.lastMessageAt), {
                            addSuffix: true,
                            locale: fr
                        })}
                    </Text>
                </View>

                <View style={styles.propertyInfo}>
                    <MaterialCommunityIcons
                        name="home"
                        size={14}
                        color={colors.textSecondary}
                    />
                    <Text
                        variant="labelMedium"
                        color="textSecondary"
                        style={styles.propertyText}
                        numberOfLines={1}
                    >
                        {conversation.house.shortDescription}
                    </Text>
                </View>

                <View style={styles.messagePreview}>
                    <View style={styles.messageTypeContainer}>
                        <MaterialCommunityIcons
                            name={getMessageTypeIcon(conversation.messageType)}
                            size={16}
                            color={getMessageTypeColor(conversation.messageType)}
                        />
                        <Text variant="labelSmall" style={[styles.messageType, { color: getMessageTypeColor(conversation.messageType) }]}>
                            {t(`contact.types.${conversation.messageType}`)}
                        </Text>
                    </View>
                </View>

                <Text
                    variant="bodyMedium"
                    color="textSecondary"
                    style={[
                        styles.lastMessage,
                        { fontWeight: conversation.isRead ? 'normal' : '500' }
                    ]}
                    numberOfLines={2}
                >
                    {conversation.lastMessage || conversation.subject}
                </Text>
            </View>

            <View style={styles.conversationActions}>
                <MaterialCommunityIcons
                    name="chevron-right"
                    size={20}
                    color={colors.textSecondary}
                />
                {conversation.messagesCount > 1 && (
                    <Badge
                        style={[styles.messageCountBadge, { backgroundColor: colors.surfaceVariant }]}
                        size={18}
                    >
                        {conversation.messagesCount}
                    </Badge>
                )}
            </View>
        </TouchableOpacity>
    );
};

export default function ConversationsScreen() {
    const { colors } = useTheme();
    const { t } = useTranslation();
    const router = useRouter();

    const [searchQuery, setSearchQuery] = useState('');
    const [filter, setFilter] = useState('all');

    const {
        data,
        isLoading,
        isFetching,
        refetch
    } = useGetMyConversationsQuery({
        page: 1,
        status: filter === 'all' ? undefined : filter,
    });

    const conversations = data?.conversations || [];
    const totalItems = data?.totalItems || 0;

    const filteredConversations = conversations.filter(conversation => {
        if (!searchQuery.trim()) return true;

        const query = searchQuery.toLowerCase();
        const otherParticipant = conversation.sender.id !== conversation.currentUserId
            ? conversation.sender
            : conversation.recipient;

        return (
            otherParticipant.firstName?.toLowerCase().includes(query) ||
            otherParticipant.lastName?.toLowerCase().includes(query) ||
            conversation.subject?.toLowerCase().includes(query) ||
            conversation.house?.shortDescription?.toLowerCase().includes(query)
        );
    });

    const handleConversationPress = (conversation) => {
        router.push(`/screens/conversation/${conversation.id}`);
    };

    const renderConversationItem = ({ item }) => (
        <ConversationItem
            conversation={item}
            onPress={() => handleConversationPress(item)}
        />
    );

    const renderEmptyState = () => (
        <View style={styles.emptyState}>
            <Text variant="heroTitle" style={styles.emptyIcon}>
                💬
            </Text>
            <Text variant="pageTitle" color="textPrimary" style={styles.emptyTitle}>
                {filter === 'unread'
                    ? t('conversations.noUnreadMessages')
                    : filter === 'archived'
                        ? t('conversations.noArchivedMessages')
                        : t('conversations.noConversations')
                }
            </Text>
            <Text variant="bodyLarge" color="textSecondary" style={styles.emptySubtext}>
                {filter === 'all'
                    ? t('conversations.startByBrowsingListings')
                    : t('conversations.checkBackLater')
                }
            </Text>
        </View>
    );

    const renderHeader = () => (
        <View style={styles.header}>
            <Text variant="pageTitle" color="textPrimary">
                {t('conversations.title')}
            </Text>

            <View style={styles.statsContainer}>
                <Text variant="bodyMedium" color="textSecondary">
                    {t('conversations.totalConversations', { count: totalItems })}
                </Text>
                {conversations.filter(c => !c.isRead).length > 0 && (
                    <Badge style={{ backgroundColor: colors.primary }}>
                        {conversations.filter(c => !c.isRead).length}
                    </Badge>
                )}
            </View>

            <Searchbar
                placeholder={t('conversations.searchPlaceholder')}
                onChangeText={setSearchQuery}
                value={searchQuery}
                style={styles.searchBar}
                inputStyle={styles.searchInput}
            />

            <SegmentedButtons
                value={filter}
                onValueChange={setFilter}
                buttons={FILTER_OPTIONS.map(option => ({
                    value: option.value,
                    label: t(option.label),
                }))}
                style={styles.filterButtons}
            />

            {filteredConversations.length !== conversations.length && (
                <Text variant="labelMedium" color="textSecondary" style={styles.filterResults}>
                    {t('conversations.filteredResults', {
                        count: filteredConversations.length,
                        total: conversations.length
                    })}
                </Text>
            )}
        </View>
    );

    if (isLoading) {
        return (
            <LoadingScreen />
        );
    }

    return (
        <LinearGradient colors={[colors.background, colors.surface]} style={styles.container}>
            <FlatList
                data={filteredConversations}
                renderItem={renderConversationItem}
                keyExtractor={(item) => item.id.toString()}
                ListHeaderComponent={renderHeader}
                ListEmptyComponent={renderEmptyState}
                refreshControl={
                    <RefreshControl
                        refreshing={isFetching}
                        onRefresh={refetch}
                        colors={[colors.primary]}
                        tintColor={colors.primary}
                    />
                }
                contentContainerStyle={[
                    styles.listContent,
                    filteredConversations.length === 0 && styles.emptyListContent
                ]}
                showsVerticalScrollIndicator={false}
                ItemSeparatorComponent={() => <View style={styles.separator} />}
            />
        </LinearGradient>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    centered: {
        justifyContent: 'center',
        alignItems: 'center',
    },
    header: {
        padding: SPACING.xl,
        gap: SPACING.lg,
    },
    statsContainer: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
    },
    searchBar: {
        elevation: ELEVATION.medium,
        borderRadius: BORDER_RADIUS.md,
    },
    searchInput: {
        fontSize: 16,
    },
    filterButtons: {
        marginVertical: SPACING.sm,
    },
    filterResults: {
        textAlign: 'center',
    },
    listContent: {
        paddingBottom: SPACING.xl,
        flexGrow: 1,
    },
    emptyListContent: {
        justifyContent: 'center',
    },
    conversationItem: {
        flexDirection: 'row',
        padding: SPACING.lg,
        marginHorizontal: SPACING.xl,
        marginVertical: SPACING.xs,
        borderRadius: BORDER_RADIUS.lg,
        borderWidth: 1,
        elevation: ELEVATION.low,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 1 },
        shadowOpacity: 0.1,
        shadowRadius: 2,
    },
    avatarContainer: {
        position: 'relative',
        marginRight: SPACING.md,
    },
    unreadBadge: {
        position: 'absolute',
        top: -2,
        right: -2,
    },
    conversationContent: {
        flex: 1,
        gap: SPACING.sm,
    },
    conversationHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
    },
    participantName: {
        flex: 1,
    },
    timestamp: {
        marginLeft: SPACING.sm,
    },
    propertyInfo: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.sm,
    },
    propertyText: {
        flex: 1,
    },
    messagePreview: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
    },
    messageTypeContainer: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.sm,
    },
    messageType: {
        textTransform: 'uppercase',
    },
    lastMessage: {
        lineHeight: 18,
    },
    conversationActions: {
        justifyContent: 'center',
        alignItems: 'center',
        gap: SPACING.sm,
        paddingLeft: SPACING.sm,
    },
    messageCountBadge: {
        alignSelf: 'center',
    },
    separator: {
        height: 1,
        backgroundColor: 'transparent',
    },
    emptyState: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
        paddingHorizontal: SPACING.xxl,
        paddingVertical: SPACING.huge,
        gap: SPACING.lg,
    },
    emptyIcon: {
        fontSize: 64,
        marginBottom: SPACING.lg,
        textAlign: 'center',
        lineHeight: 80,
    },
    emptyTitle: {
        textAlign: 'center',
        marginBottom: SPACING.sm,
    },
    emptySubtext: {
        textAlign: 'center',
        lineHeight: 20,
    },
    loadingText: {
        marginTop: SPACING.lg,
        textAlign: 'center',
    },
});