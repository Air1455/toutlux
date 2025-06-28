import React from 'react';
import { View, Image, StyleSheet, TouchableOpacity } from 'react-native';
import { Card, IconButton, Chip, useTheme } from 'react-native-paper';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { useTranslation } from 'react-i18next';

import Text from '@/components/typography/Text';
import { SPACING, BORDER_RADIUS, ELEVATION } from '@/constants/spacing';

const ListingCard = ({ house, onEdit, onDelete, onView, showActions = true }) => {
    const { colors } = useTheme();
    const { t } = useTranslation();

    const formattedPrice = new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: house.currency || 'EUR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(house.price);

    const getStatusColor = () => {
        // Vous pouvez ajouter une logique de statut ici
        return colors.primary;
    };

    return (
        <Card
            style={[
                styles.card,
                {
                    backgroundColor: colors.surface,
                    borderRadius: BORDER_RADIUS.xl
                }
            ]}
            elevation={ELEVATION.medium}
        >
            <TouchableOpacity onPress={onView} activeOpacity={0.8}>
                <View style={styles.imageContainer}>
                    <Image
                        source={{ uri: house.firstImage }}
                        style={styles.image}
                        resizeMode="cover"
                    />
                    <View style={[styles.typeBadge, { backgroundColor: getStatusColor() }]}>
                        <Text variant="labelSmall" style={styles.typeBadgeText}>
                            {house.isForRent ? t('listings.forRent') : t('listings.forSale')}
                        </Text>
                    </View>

                    {showActions && (
                        <View style={styles.actionButtons}>
                            <IconButton
                                icon="pencil"
                                iconColor={colors.onPrimary}
                                containerColor={colors.primary}
                                size={20}
                                onPress={onEdit}
                                style={styles.actionButton}
                            />
                            <IconButton
                                icon="delete"
                                iconColor={colors.onError}
                                containerColor={colors.error}
                                size={20}
                                onPress={onDelete}
                                style={styles.actionButton}
                            />
                        </View>
                    )}
                </View>
            </TouchableOpacity>

            <Card.Content style={styles.content}>
                <Text
                    variant="cardTitle"
                    color="textPrimary"
                    style={styles.title}
                    numberOfLines={2}
                >
                    {house.shortDescription}
                </Text>

                <View style={styles.locationRow}>
                    <MaterialCommunityIcons
                        name="map-marker"
                        size={16}
                        color={colors.textSecondary}
                    />
                    <Text variant="bodyMedium" color="textSecondary" style={styles.location}>
                        {house.city}, {house.country}
                    </Text>
                </View>

                <View style={styles.detailsRow}>
                    {house.bedrooms && (
                        <Chip
                            mode="outlined"
                            compact
                            icon="bed"
                            style={styles.detailChip}
                            textStyle={styles.chipText}
                        >
                            {house.bedrooms}
                        </Chip>
                    )}
                    {house.bathrooms && (
                        <Chip
                            mode="outlined"
                            compact
                            icon="shower"
                            style={styles.detailChip}
                            textStyle={styles.chipText}
                        >
                            {house.bathrooms}
                        </Chip>
                    )}
                    {house.surface && (
                        <Chip
                            mode="outlined"
                            compact
                            icon="ruler-square"
                            style={styles.detailChip}
                            textStyle={styles.chipText}
                        >
                            {house.surface}
                        </Chip>
                    )}
                </View>

                <View style={styles.footer}>
                    <View style={styles.priceContainer}>
                        <Text variant="priceCard" color="primary">
                            {formattedPrice}
                        </Text>
                        {house.isForRent && (
                            <Text variant="bodyMedium" color="textSecondary" style={styles.period}>
                                / {t('common.month')}
                            </Text>
                        )}
                    </View>

                    <View style={styles.typeInfo}>
                        <MaterialCommunityIcons
                            name={house.type === 'apartment' ? 'building' : 'home'}
                            size={16}
                            color={colors.textSecondary}
                        />
                        <Text variant="labelMedium" color="textSecondary" style={styles.type}>
                            {t(`houseTypes.${house.type}`)}
                        </Text>
                    </View>
                </View>
            </Card.Content>
        </Card>
    );
};

const styles = StyleSheet.create({
    card: {
        marginBottom: SPACING.lg,
        overflow: 'hidden',
    },
    imageContainer: {
        position: 'relative',
        height: 200,
    },
    image: {
        width: '100%',
        height: '100%',
        backgroundColor: '#f0f0f0',
    },
    typeBadge: {
        position: 'absolute',
        top: SPACING.md,
        left: SPACING.md,
        paddingVertical: SPACING.xs,
        paddingHorizontal: SPACING.sm,
        borderRadius: BORDER_RADIUS.lg,
        elevation: ELEVATION.medium,
    },
    typeBadgeText: {
        color: '#fff',
        textTransform: 'uppercase',
    },
    actionButtons: {
        position: 'absolute',
        top: SPACING.md,
        right: SPACING.md,
        flexDirection: 'row',
        gap: SPACING.sm,
    },
    actionButton: {
        margin: 0,
        elevation: ELEVATION.high,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.2,
        shadowRadius: 4,
    },
    content: {
        padding: SPACING.lg,
        gap: SPACING.md,
    },
    title: {
        lineHeight: 20,
    },
    locationRow: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.xs,
    },
    location: {
        flex: 1,
    },
    detailsRow: {
        flexDirection: 'row',
        gap: SPACING.sm,
        flexWrap: 'wrap',
    },
    detailChip: {
        height: 28,
    },
    chipText: {
        fontSize: 12,
    },
    footer: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'flex-end',
    },
    priceContainer: {
        flexDirection: 'row',
        alignItems: 'baseline',
        gap: SPACING.xs,
    },
    period: {
        // Typography géré par le composant Text
    },
    typeInfo: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.xs,
    },
    type: {
        textTransform: 'capitalize',
    },
});

export default ListingCard;