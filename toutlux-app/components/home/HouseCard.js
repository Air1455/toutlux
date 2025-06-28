import React, { useState } from 'react';
import { View, Image, StyleSheet, TouchableOpacity } from 'react-native';
import { Button, useTheme } from 'react-native-paper';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { useRouter } from "expo-router";
import { useTranslation } from "react-i18next";

import CustomButton from "@components/CustomButton";
import { useCurrentUser } from '@/hooks/useIsCurrentUser';
import { formatPrice } from '@/utils/currencyUtils';
import Text from '@/components/typography/Text';
import { SPACING, BORDER_RADIUS, ELEVATION } from '@/constants/spacing';

const HouseDetails = React.memo(({ bedrooms, bathrooms, city }) => {
    // Couleurs fixes pour fond blanc
    const iconColor = "rgb(69, 70, 79)"; // Gris foncé visible sur blanc
    const textColor = "rgb(69, 70, 79)"; // Gris foncé visible sur blanc

    return (
        <View style={styles.detailsRow}>
            <MaterialCommunityIcons name="bed" size={20} color={iconColor} />
            <Text variant="bodySmall" style={[styles.detailText, { color: textColor }]}>
                {bedrooms}
            </Text>

            <MaterialCommunityIcons
                name="shower"
                size={20}
                color={iconColor}
                style={styles.iconMargin}
            />
            <Text variant="bodySmall" style={[styles.detailText, { color: textColor }]}>
                {bathrooms}
            </Text>

            <MaterialCommunityIcons
                name="map-marker"
                size={20}
                color={iconColor}
                style={styles.iconMargin}
            />
            <Text variant="bodySmall" style={[styles.detailText, { color: textColor }]}>
                {city}
            </Text>
        </View>
    );
});

const HouseCard = React.memo(({ house, onMap = false, showContactButton = true }) => {
    const { colors } = useTheme();
    const router = useRouter();
    const { t } = useTranslation();
    const { user: currentUser, userId: currentUserId } = useCurrentUser();

    const handlePress = React.useCallback(() => {
        router.push(`/screens/house_details/${house.id}`);
    }, [house.id, router]);

    // Utilisation de l'utilitaire centralisé pour le formatage du prix
    const formattedPrice = React.useMemo(() => {
        return formatPrice(house.price, house.currency, {
            isRental: house.isForRent,
        });
    }, [house.price, house.currency, house.isForRent]);

    const getHouseUserId = () => {
        if (!house.user) return null;

        // Si c'est un objet
        if (typeof house.user === 'object' && house.user.id) {
            return house.user.id;
        }

        // Si c'est juste un ID
        if (typeof house.user === 'string' || typeof house.user === 'number') {
            return house.user;
        }

        return null;
    };

    return (
        <>
            <TouchableOpacity onPress={handlePress} activeOpacity={0.8}>
                <View style={[
                    styles.card,
                    {
                        backgroundColor: '#ffffff', // Fond blanc permanent
                        shadowColor: colors.shadow,
                    }
                ]}>
                    <View style={styles.imageContainer}>
                        <Image
                            source={{ uri: house.firstImage }}
                            style={[styles.image, { height: onMap ? 120 : 168 }]}
                            resizeMode="cover"
                        />

                        <View style={[styles.typeBadge, { backgroundColor: colors.primary }]}>
                            <Text variant="labelSmall" style={styles.typeBadgeText}>
                                {house.isForRent ? t('listings.forRent') : t('listings.forSale')}
                            </Text>
                        </View>

                        {house.isFavorite && (
                            <View style={[styles.favoriteBadge, { backgroundColor: '#ffffff' }]}>
                                <MaterialCommunityIcons
                                    name="heart"
                                    size={20}
                                    color="#ff4444"
                                />
                            </View>
                        )}
                    </View>

                    <View style={styles.info}>
                        <Text variant="cardTitle" style={[styles.title, { color: "rgb(27, 27, 31)" }]} numberOfLines={2}>
                            {house.shortDescription}
                        </Text>

                        <HouseDetails
                            bedrooms={house.bedrooms}
                            bathrooms={house.bathrooms}
                            city={house.city}
                        />

                        <View style={styles.footer}>
                            <View style={styles.priceContainer}>
                                <Text variant="priceCard" style={[styles.price, { color: "#bf8b19" }]}>
                                    {formattedPrice}
                                </Text>
                            </View>
                            <CustomButton
                                variant="yellow"
                                content={t('common.view')}
                                radius="rounded"
                                onPress={handlePress}
                                style={styles.viewButton}
                            />
                        </View>
                    </View>
                </View>
            </TouchableOpacity>
        </>
    );
});

const styles = StyleSheet.create({
    card: {
        borderRadius: BORDER_RADIUS.lg,
        overflow: 'hidden',
        marginBottom: SPACING.lg,
        elevation: ELEVATION.medium,
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 2,
        marginHorizontal: SPACING.md,
    },
    imageContainer: {
        position: 'relative',
    },
    image: {
        width: '100%',
        backgroundColor: '#f0f0f0',
    },
    typeBadge: {
        position: 'absolute',
        top: SPACING.md,
        left: SPACING.md,
        paddingVertical: SPACING.xs,
        paddingHorizontal: SPACING.sm,
        borderRadius: BORDER_RADIUS.lg,
    },
    typeBadgeText: {
        color: '#fff',
        textTransform: 'uppercase',
        fontWeight: 'bold',
    },
    favoriteBadge: {
        position: 'absolute',
        top: SPACING.md,
        right: SPACING.md,
        borderRadius: 20,
        padding: SPACING.sm,
        elevation: ELEVATION.low,
    },
    info: {
        padding: SPACING.md,
        gap: SPACING.md,
    },
    title: {
        lineHeight: 19,
    },
    detailsRow: {
        flexDirection: 'row',
        alignItems: 'center',
        flexWrap: 'wrap',
    },
    detailText: {
        marginHorizontal: SPACING.sm,
    },
    iconMargin: {
        marginLeft: SPACING.md,
    },
    footer: {
        marginBottom: SPACING.xs,
        gap: SPACING.md,
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
    },
    priceContainer: {
        flexDirection: 'row',
        alignItems: 'baseline',
        gap: SPACING.xs,
    },
    price: {
        // Style déjà défini dans le variant priceCard
    },
    viewButton: {
        flexShrink: 0,
        paddingHorizontal: SPACING.xxl,
    },
});

export default HouseCard;