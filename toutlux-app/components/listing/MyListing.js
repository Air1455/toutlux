// components/listing/MyListing.js
import React, { useState } from 'react';
import { View, StyleSheet, ScrollView, Alert } from "react-native";
import { useTheme, FAB, Portal, Dialog, Button } from "react-native-paper";
import { useTranslation } from "react-i18next";
import { useRouter } from 'expo-router';

import { useCompareUser, useListingPermissions } from '@/hooks/useIsCurrentUser';
import { useDeleteHouseMutation } from '@/redux/api/houseApi';
import { ProfileMenuItem } from "@components/profile/ProfileMenuItem";
import ListingCard from './ListingCard';
import Text from '@/components/typography/Text';
import { SPACING, BORDER_RADIUS, ELEVATION } from '@/constants/spacing';

const MyListing = ({ user }) => {
    const { colors } = useTheme();
    const { t } = useTranslation();
    const router = useRouter();

    const { isCurrentUser, isLoading } = useCompareUser(user);
    const { canView, canEdit, canDelete } = useListingPermissions(user);

    const [deleteHouse] = useDeleteHouseMutation();
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [selectedHouse, setSelectedHouse] = useState(null);
    const [deleting, setDeleting] = useState(false);

    if (!user || !user.firstName) {
        return null;
    }

    if (isLoading) {
        return (
            <View style={styles.container}>
                <Text variant="bodyMedium" color="textSecondary" style={styles.loadingText}>
                    {t('common.loading')}
                </Text>
            </View>
        );
    }

    if (!canView) {
        return (
            <View style={styles.container}>
                <Text variant="cardTitle" color="textPrimary" style={styles.containerHeader}>
                    {t("profile.listingHeader", { name: user.firstName })}
                </Text>
                <View style={styles.emptyState}>
                    <Text variant="bodyMedium" color="textSecondary" style={styles.noAccessText}>
                        {t('profile.cannotViewListings')}
                    </Text>
                </View>
            </View>
        );
    }

    const handleCreateListing = () => {
        router.push('/screens/create_listing');
    };

    const handleEditListing = (house) => {
        if (!canEdit) {
            Alert.alert(
                t('common.error'),
                t('listings.cannotEdit')
            );
            return;
        }
        router.push(`/screens/edit_listing/${house.id}`);
    };

    const handleDeleteListing = (house) => {
        if (!canDelete) {
            Alert.alert(
                t('common.error'),
                t('listings.cannotDelete')
            );
            return;
        }
        setSelectedHouse(house);
        setShowDeleteDialog(true);
    };

    const confirmDelete = async () => {
        if (!selectedHouse || !canDelete) return;

        try {
            setDeleting(true);
            await deleteHouse(selectedHouse.id).unwrap();

            Alert.alert(
                t('common.success'),
                t('listings.deleteSuccess'),
                [{ text: 'OK' }]
            );
        } catch (error) {
            console.error('Error deleting listing:', error);
            Alert.alert(
                t('common.error'),
                t('listings.deleteError')
            );
        } finally {
            setDeleting(false);
            setShowDeleteDialog(false);
            setSelectedHouse(null);
        }
    };

    const handleManageListings = () => {
        router.push('/screens/manage_listings');
    };

    const userHouses = user.houses || [];
    const hasListings = userHouses.length > 0;

    return (
        <View style={styles.container}>
            <View style={styles.header}>
                <Text variant="cardTitle" color="textPrimary" style={styles.containerHeader}>
                    {isCurrentUser ? t("profile.myListings") : t("profile.userListings", { name: user.firstName })}
                </Text>

                {hasListings && isCurrentUser && (
                    <ProfileMenuItem
                        icon="cog"
                        title={t('listings.manageAll')}
                        onPress={handleManageListings}
                    />
                )}
            </View>

            <ScrollView
                style={styles.listingsContainer}
                showsVerticalScrollIndicator={false}
            >
                {hasListings ? (
                    <>
                        {userHouses.slice(0, 3).map((house) => (
                            <ListingCard
                                key={house.id}
                                house={house}
                                onEdit={canEdit ? () => handleEditListing(house) : undefined}
                                onDelete={canDelete ? () => handleDeleteListing(house) : undefined}
                                onView={() => router.push(`/screens/house_details/${house.id}`)}
                                showActions={isCurrentUser}
                            />
                        ))}

                        {userHouses.length > 3 && (
                            <View style={styles.moreListingsContainer}>
                                <Text variant="bodyMedium" color="textSecondary" style={styles.moreListingsText}>
                                    {t('listings.moreListings', { count: userHouses.length - 3 })}
                                </Text>
                                {isCurrentUser && (
                                    <Button
                                        mode="outlined"
                                        onPress={handleManageListings}
                                        style={[styles.viewAllButton, { borderRadius: BORDER_RADIUS.md }]}
                                    >
                                        {t('listings.viewAll')}
                                    </Button>
                                )}
                            </View>
                        )}
                    </>
                ) : (
                    <View style={styles.emptyState}>
                        <Text variant="bodyLarge" color="textSecondary" style={styles.noListingText}>
                            {isCurrentUser ? t('profile.noListing') : t('profile.userNoListings', { name: user.firstName })}
                        </Text>
                        {isCurrentUser && (
                            <Text variant="bodyMedium" color="textHint" style={styles.emptyStateSubtext}>
                                {t('listings.createFirstListing')}
                            </Text>
                        )}
                    </View>
                )}
            </ScrollView>

            {isCurrentUser && canEdit && (
                <FAB
                    icon="plus"
                    style={[
                        styles.fab,
                        {
                            backgroundColor: colors.primary,
                            borderRadius: BORDER_RADIUS.lg
                        }
                    ]}
                    onPress={handleCreateListing}
                    label={hasListings ? undefined : t('listings.createFirst')}
                    color={colors.onPrimary}
                />
            )}

            <Portal>
                <Dialog
                    visible={showDeleteDialog}
                    onDismiss={() => setShowDeleteDialog(false)}
                    style={{ borderRadius: BORDER_RADIUS.lg }}
                >
                    <Dialog.Title>
                        <Text variant="cardTitle" color="textPrimary">
                            {t('listings.deleteTitle')}
                        </Text>
                    </Dialog.Title>
                    <Dialog.Content>
                        <Text variant="bodyMedium" color="textPrimary">
                            {t('listings.deleteConfirmation', { title: selectedHouse?.shortDescription })}
                        </Text>
                    </Dialog.Content>
                    <Dialog.Actions>
                        <Button
                            onPress={() => setShowDeleteDialog(false)}
                            style={{ borderRadius: BORDER_RADIUS.md }}
                        >
                            {t('common.cancel')}
                        </Button>
                        <Button
                            onPress={confirmDelete}
                            loading={deleting}
                            disabled={deleting}
                            textColor={colors.error}
                            style={{ borderRadius: BORDER_RADIUS.md }}
                        >
                            {t('common.delete')}
                        </Button>
                    </Dialog.Actions>
                </Dialog>
            </Portal>
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        marginVertical: SPACING.lg,
        flex: 1,
    },
    header: {
        marginBottom: SPACING.lg,
        gap: SPACING.md,
    },
    containerHeader: {
        // Typography géré par le composant Text
    },
    listingsContainer: {
        flex: 1,
    },
    emptyState: {
        paddingVertical: SPACING.huge,
        alignItems: 'center',
        gap: SPACING.sm,
    },
    noListingText: {
        textAlign: 'center',
    },
    noAccessText: {
        textAlign: 'center',
        paddingHorizontal: SPACING.lg,
    },
    emptyStateSubtext: {
        textAlign: 'center',
        paddingHorizontal: SPACING.lg,
    },
    loadingText: {
        textAlign: 'center',
        padding: SPACING.lg,
    },
    moreListingsContainer: {
        alignItems: 'center',
        paddingVertical: SPACING.lg,
        gap: SPACING.md,
    },
    moreListingsText: {
        // Typography géré par le composant Text
    },
    viewAllButton: {
        paddingHorizontal: SPACING.lg,
    },
    fab: {
        position: 'absolute',
        margin: SPACING.lg,
        right: 0,
        bottom: 0,
        elevation: ELEVATION.high,
    },
});

export default MyListing;