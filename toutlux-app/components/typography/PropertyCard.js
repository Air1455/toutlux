import React from 'react';
import { View, StyleSheet } from 'react-native';
import { Appbar, useTheme } from 'react-native-paper';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import Text from './Text';

const Header = ({
                    title,
                    subtitle,
                    showBack = false,
                    onBack,
                    actions = [],
                    style,
                    ...props
                }) => {
    const { colors } = useTheme();
    const insets = useSafeAreaInsets();

    return (
        <Appbar.Header
            style={[
                {
                    backgroundColor: colors.surface,
                    elevation: 2,
                    shadowColor: colors.shadow,
                    paddingTop: insets.top,
                },
                style
            ]}
            {...props}
        >
            {showBack && (
                <Appbar.BackAction
                    onPress={onBack}
                    iconColor={colors.textPrimary}
                />
            )}

            <View style={styles.titleContainer}>
                {title && (
                    <Text variant="headerTitle" numberOfLines={1}>
                        {title}
                    </Text>
                )}
                {subtitle && (
                    <Text variant="caption" color="textSecondary" numberOfLines={1}>
                        {subtitle}
                    </Text>
                )}
            </View>

            {actions.map((action, index) => (
                <Appbar.Action
                    key={index}
                    icon={action.icon}
                    onPress={action.onPress}
                    iconColor={colors.textPrimary}
                    disabled={action.disabled}
                />
            ))}
        </Appbar.Header>
    );import React from 'react';
    import { View, StyleSheet, TouchableOpacity } from 'react-native';
    import { Surface, useTheme, IconButton } from 'react-native-paper';
    import { Image } from 'expo-image';
    import Text from './Text';

    const PropertyCard = ({
                              property,
                              onPress,
                              onFavorite,
                              isFavorite = false,
                              style,
                              ...props
                          }) => {
        const { colors } = useTheme();

        const {
            id,
            title,
            address,
            price,
            priceUnit = '€/mois',
            surface,
            bedrooms,
            bathrooms,
            images = [],
            agencyName,
            type
        } = property;

        const mainImage = images[0] || require('@/assets/images/placeholder-house.jpg');

        return (
            <TouchableOpacity onPress={onPress} activeOpacity={0.8}>
                <Surface
                    style={[
                        styles.card,
                        { backgroundColor: colors.surface },
                        style
                    ]}
                    elevation={3}
                    {...props}
                >
                    {/* Image principale */}
                    <View style={styles.imageContainer}>
                        <Image
                            source={typeof mainImage === 'string' ? { uri: mainImage } : mainImage}
                            style={styles.image}
                            contentFit="cover"
                            transition={200}
                        />

                        {/* Badge type de propriété */}
                        {type && (
                            <View style={[styles.typeBadge, { backgroundColor: colors.primaryContainer }]}>
                                <Text variant="labelSmall" color="onPrimaryContainer">
                                    {type}
                                </Text>
                            </View>
                        )}

                        {/* Bouton favoris */}
                        <View style={styles.favoriteButton}>
                            <IconButton
                                icon={isFavorite ? 'heart' : 'heart-outline'}
                                iconColor={isFavorite ? colors.error : colors.surface}
                                size={20}
                                onPress={onFavorite}
                                style={{ backgroundColor: 'rgba(0,0,0,0.3)' }}
                            />
                        </View>
                    </View>

                    {/* Contenu */}
                    <View style={styles.content}>
                        {/* Prix */}
                        <View style={styles.priceContainer}>
                            <Text variant="priceCard" color="textPrice">
                                {price}
                            </Text>
                            <Text variant="labelMedium" color="textSecondary" style={styles.priceUnit}>
                                {priceUnit}
                            </Text>
                        </View>

                        {/* Titre */}
                        <Text variant="cardTitle" numberOfLines={1} style={styles.title}>
                            {title}
                        </Text>

                        {/* Adresse */}
                        <Text variant="bodySmall" color="textSecondary" numberOfLines={1} style={styles.address}>
                            📍 {address}
                        </Text>

                        {/* Caractéristiques */}
                        <View style={styles.features}>
                            {surface && (
                                <View style={styles.feature}>
                                    <Text variant="propertySpecs" color="textSecondary">
                                        📐 {surface}m²
                                    </Text>
                                </View>
                            )}
                            {bedrooms && (
                                <View style={styles.feature}>
                                    <Text variant="propertySpecs" color="textSecondary">
                                        🛏️ {bedrooms} ch.
                                    </Text>
                                </View>
                            )}
                            {bathrooms && (
                                <View style={styles.feature}>
                                    <Text variant="propertySpecs" color="textSecondary">
                                        🚿 {bathrooms} sdb.
                                    </Text>
                                </View>
                            )}
                        </View>

                        {/* Agence */}
                        {agencyName && (
                            <View style={[styles.agencyContainer, { borderTopColor: colors.outline }]}>
                                <Text variant="agencyName" color="primary">
                                    {agencyName}
                                </Text>
                            </View>
                        )}
                    </View>
                </Surface>
            </TouchableOpacity>
        );
    };

    const styles = StyleSheet.create({
        card: {
            borderRadius: 16,
            marginVertical: 8,
            marginHorizontal: 16,
            overflow: 'hidden',
        },
        imageContainer: {
            position: 'relative',
            height: 200,
        },
        image: {
            width: '100%',
            height: '100%',
        },
        typeBadge: {
            position: 'absolute',
            top: 12,
            left: 12,
            paddingHorizontal: 8,
            paddingVertical: 4,
            borderRadius: 12,
        },
        favoriteButton: {
            position: 'absolute',
            top: 8,
            right: 8,
        },
        content: {
            padding: 16,
        },
        priceContainer: {
            flexDirection: 'row',
            alignItems: 'baseline',
            marginBottom: 8,
        },
        priceUnit: {
            marginLeft: 4,
        },
        title: {
            marginBottom: 4,
        },
        address: {
            marginBottom: 12,
        },
        features: {
            flexDirection: 'row',
            flexWrap: 'wrap',
            marginBottom: 12,
        },
        feature: {
            marginRight: 16,
            marginBottom: 4,
        },
        agencyContainer: {
            paddingTop: 12,
            borderTopWidth: 1,
        },
    });

    export default PropertyCard;
};

const styles = StyleSheet.create({
    titleContainer: {
        flex: 1,
        marginLeft: 8,
        justifyContent: 'center',
    },
});

export default Header;