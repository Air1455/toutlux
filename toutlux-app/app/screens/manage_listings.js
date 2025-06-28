// screens/manage_listings.js
import React, { useState } from 'react';
import {
    View,
    StyleSheet,
    FlatList,
    RefreshControl,
    Alert,
} from 'react-native';
import {
    useTheme,
    ActivityIndicator,
    FAB,
    Portal,
    Dialog,
    Button,
    Searchbar,
    SegmentedButtons,
} from 'react-native-paper';
import { useTranslation } from 'react-i18next';
import { useRouter } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';

import { SafeScreen } from '@components/layout/SafeScreen';
import { useGetUserHousesQuery, useDeleteHouseMutation } from '@/redux/api/houseApi';
import ListingCard from '@components/listing/ListingCard';
import Text from '@/components/typography/Text';
import { SPACING, BORDER_RADIUS } from '@/constants/spacing';
import {LoadingScreen} from "@components/Loading";

export default function ManageListingsScreen() {
    const { colors } = useTheme();
    const { t } = useTranslation();
    const router = useRouter();

    const [searchQuery, setSearchQuery] = useState('');
    const [filter, setFilter] = useState('all'); // all, active, inactive
    const [selectedHouse, setSelectedHouse] = useState(null);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);

    const { data: houses = [], isLoading, refetch } = useGetUserHousesQuery();
    const [deleteHouse, { isLoading: isDeleting }] = useDeleteHouseMutation();

    const handleCreateListing = () => {
        router.push('/screens/create_listing');
    };

    const handleEditListing = (house) => {
        router.push(`/screens/edit_listing/${house.id}`);
    };

    const handleViewListing = (house) => {
        router.push(`/screens/house_details/${house.id}`);
    };

    const handleDeleteListing = (house) => {
        setSelectedHouse(house);
        setShowDeleteDialog(true);
    };

    const confirmDelete = async () => {
        if (!selectedHouse) return;

        try {
            await deleteHouse(selectedHouse.id).unwrap();
            Alert.alert(
                t('common.success'),
                t('listings.deleteSuccess')
            );
        } catch (error) {
            Alert.alert(
                t('common.error'),
                t('listings.deleteError')
            );
        } finally {
            setShowDeleteDialog(false);
            setSelectedHouse(null);
        }
    };

    const filteredHouses = houses.filter(house => {
        // Filtre par statut
        if (filter === 'active' && house.status !== 'active') return false;
        if (filter === 'inactive' && house.status === 'active') return false;

        // Filtre par recherche
        if (searchQuery) {
            const query = searchQuery.toLowerCase();
            return (
                house.shortDescription?.toLowerCase().includes(query) ||
                house.city?.toLowerCase().includes(query) ||
                house.type?.toLowerCase().includes(query)
            );
        }

        return true;
    });

    const renderListing = ({ item }) => (
        <ListingCard
            house={item}
            onEdit={() => handleEditListing(item)}
            onDelete={() => handleDeleteListing(item)}
            onView={() => handleViewListing(item)}
            showActions={true}
        />
    );

    const renderHeader = () => (
        <View style={styles.header}>
            <Text variant="pageTitle" color="textPrimary">
                {t('listings.manageTitle')}
            </Text>

            <Searchbar
                placeholder={t('listings.searchPlaceholder')}
                onChangeText={setSearchQuery}
                value={searchQuery}
                style={styles.searchBar}
            />

            <SegmentedButtons
                value={filter}
                onValueChange={setFilter}
                buttons={[
                    { value: 'all', label: t('common.all') },
                    { value: 'active', label: t('listings.active') },
                    { value: 'inactive', label: t('listings.inactive') },
                ]}
                style={styles.filterButtons}
            />

            <View style={styles.stats}>
                <Text variant="bodyMedium" color="textSecondary">
                    {t('listings.totalListings', { count: filteredHouses.length })}
                </Text>
            </View>
        </View>
    );

    if (isLoading) {
        return <LoadingScreen />
    }

    return (
        <LinearGradient colors={[colors.background, colors.surface]} style={styles.container}>
            <FlatList
                data={filteredHouses}
                renderItem={renderListing}
                keyExtractor={(item) => item.id.toString()}
                ListHeaderComponent={renderHeader}
                ListEmptyComponent={
                    <View style={styles.emptyState}>
                        <Text variant="heroTitle" style={styles.emptyIcon}>🏠</Text>
                        <Text variant="pageTitle" color="textPrimary" style={styles.emptyTitle}>
                            {t('listings.noListings')}
                        </Text>
                        <Text variant="bodyLarge" color="textSecondary" style={styles.emptySubtext}>
                            {t('listings.createFirstToManage')}
                        </Text>
                    </View>
                }
                refreshControl={
                    <RefreshControl
                        refreshing={false}
                        onRefresh={refetch}
                        colors={[colors.primary]}
                    />
                }
                contentContainerStyle={[
                    styles.listContent,
                    filteredHouses.length === 0 && styles.emptyListContent
                ]}
            />

            <FAB
                icon="plus"
                style={[styles.fab, { backgroundColor: colors.primary }]}
                onPress={handleCreateListing}
                color={colors.onPrimary}
            />

            <Portal>
                <Dialog
                    visible={showDeleteDialog}
                    onDismiss={() => setShowDeleteDialog(false)}
                >
                    <Dialog.Title>{t('listings.deleteTitle')}</Dialog.Title>
                    <Dialog.Content>
                        <Text variant="bodyMedium" color="textPrimary">
                            {t('listings.deleteConfirmation', {
                                title: selectedHouse?.shortDescription
                            })}
                        </Text>
                    </Dialog.Content>
                    <Dialog.Actions>
                        <Button onPress={() => setShowDeleteDialog(false)}>
                            {t('common.cancel')}
                        </Button>
                        <Button
                            onPress={confirmDelete}
                            loading={isDeleting}
                            textColor={colors.error}
                        >
                            {t('common.delete')}
                        </Button>
                    </Dialog.Actions>
                </Dialog>
            </Portal>
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
    searchBar: {
        elevation: 0,
        backgroundColor: 'transparent',
    },
    filterButtons: {
        marginVertical: SPACING.sm,
    },
    stats: {
        alignItems: 'center',
    },
    listContent: {
        paddingBottom: SPACING.huge,
        flexGrow: 1,
    },
    emptyListContent: {
        justifyContent: 'center',
    },
    emptyState: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
        paddingVertical: SPACING.huge,
        paddingHorizontal: SPACING.xl,
        gap: SPACING.lg,
    },
    emptyIcon: {
        fontSize: 64,
        marginBottom: SPACING.lg,
    },
    emptyTitle: {
        textAlign: 'center',
    },
    emptySubtext: {
        textAlign: 'center',
        lineHeight: 20,
    },
    loadingText: {
        marginTop: SPACING.lg,
    },
    fab: {
        position: 'absolute',
        margin: SPACING.lg,
        right: 0,
        bottom: SPACING.lg,
        borderRadius: BORDER_RADIUS.lg,
    },
});