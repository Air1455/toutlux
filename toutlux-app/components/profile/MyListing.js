import React from 'react';
import { View, StyleSheet } from "react-native";
import { useTheme } from "react-native-paper";
import { useTranslation } from "react-i18next";

import Text from '@/components/typography/Text';
import HouseCard from "@components/home/HouseCard";
import { useCurrentUser } from '@/hooks/useIsCurrentUser';
import { SPACING } from '@/constants/spacing';

export default function MyListing({ user }) {
    const { colors } = useTheme();
    const { t } = useTranslation();
    const { isCurrentUser, isLoading, hasAccess } = useCurrentUser(user);

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

    if (!isCurrentUser) {
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

    // Si c'est l'utilisateur connecté
    return (
        <View style={styles.container}>
            <Text variant="cardTitle" color="textPrimary" style={styles.containerHeader}>
                {t("profile.myListings")} {/* Plus approprié si c'est l'utilisateur connecté */}
            </Text>

            <View style={styles.listingsContainer}>
                {user.houses && user.houses.length > 0 ? (
                    user.houses.map((house, index) => (
                        <HouseCard
                            key={house.id || index}
                            house={house}
                        />
                    ))
                ) : (
                    <View style={styles.emptyState}>
                        <Text variant="bodyMedium" color="textSecondary" style={styles.noListingText}>
                            {t('profile.noListing')}
                        </Text>
                    </View>
                )}
            </View>
        </View>
    );
}

const styles = StyleSheet.create({
    container: {
        marginVertical: SPACING.lg
    },
    containerHeader: {
        marginBottom: SPACING.sm,
    },
    listingsContainer: {
        gap: SPACING.md,
    },
    emptyState: {
        paddingVertical: SPACING.lg,
        alignItems: 'center',
    },
    noListingText: {
        textAlign: 'center',
    },
    noAccessText: {
        textAlign: 'center',
        fontStyle: 'italic',
    },
    loadingText: {
        textAlign: 'center',
        padding: SPACING.lg,
    },
});