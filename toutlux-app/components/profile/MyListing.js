import React from 'react';
import { Text, View, StyleSheet } from "react-native";
import { useTheme } from "react-native-paper";
import HouseCard from "@components/home/HouseCard";
import { useTranslation } from "react-i18next";
import { useIsCurrentUser } from '@/hooks/useIsCurrentUser';

export default function MyListing({ user }) {
    const { colors } = useTheme();
    const { t } = useTranslation();

    // Utiliser le hook personnalisé
    const { isCurrentUser, isLoading, hasAccess } = useIsCurrentUser(user);

    // Si pas d'utilisateur
    if (!user || !user.firstName) {
        return null;
    }

    // Pendant le chargement
    if (isLoading) {
        return (
            <View style={styles.container}>
                <Text style={[styles.loadingText, { color: colors.onSurfaceVariant }]}>
                    {t('loading')}
                </Text>
            </View>
        );
    }

    // Si ce n'est pas l'utilisateur connecté
    if (!isCurrentUser) {
        return (
            <View style={styles.container}>
                <Text style={[styles.containerHeader, { color: colors.text }]}>
                    {t("profile.listingHeader", { name: user.firstName })}
                </Text>
                <View style={styles.emptyState}>
                    <Text style={[styles.noAccessText, { color: colors.onSurfaceVariant }]}>
                        {t('profile.cannotViewListings')}
                    </Text>
                </View>
            </View>
        );
    }

    // Si c'est l'utilisateur connecté
    return (
        <View style={styles.container}>
            <Text style={[styles.containerHeader, { color: colors.text }]}>
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
                        <Text style={[styles.noListingText, { color: colors.onSurfaceVariant }]}>
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
        marginVertical: 20
    },
    containerHeader: {
        fontSize: 18,
        fontWeight: 'bold',
        fontFamily: 'Prompt_800ExtraBold',
        marginBottom: 10
    },
    listingsContainer: {
        gap: 12,
    },
    emptyState: {
        paddingVertical: 20,
        alignItems: 'center',
    },
    noListingText: {
        fontSize: 14,
        fontFamily: 'Prompt_400Regular',
        textAlign: 'center',
    },
    noAccessText: {
        fontSize: 14,
        fontFamily: 'Prompt_400Regular',
        textAlign: 'center',
        fontStyle: 'italic',
    },
    loadingText: {
        fontSize: 14,
        fontFamily: 'Prompt_400Regular',
        textAlign: 'center',
        padding: 20,
    },
});