import React from 'react';
import { View, Text, Image, StyleSheet } from 'react-native';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { useRouter } from "expo-router";
import { useTranslation } from "react-i18next";
import CustomButton from "@components/CustomButton";

const HouseDetails = React.memo(({ bedrooms, bathrooms, city }) => (
    <View style={styles.detailsRow}>
        <MaterialCommunityIcons name="bed" size={20} color="#555" />
        <Text style={styles.detailText}>{bedrooms}</Text>

        <MaterialCommunityIcons name="shower" size={20} color="#555" style={styles.iconMargin} />
        <Text style={styles.detailText}>{bathrooms}</Text>

        <MaterialCommunityIcons name="map-marker" size={20} color="#555" style={styles.iconMargin} />
        <Text style={styles.detailText}>{city}</Text>
    </View>
));

const HouseCard = React.memo(({ house, onMap = false }) => {
    const router = useRouter();
    const { t } = useTranslation();

    const handlePress = React.useCallback(() => {
        router.push(`/screens/house_details/${house.id}`);
    }, [house.id, router]);

    const formattedPrice = React.useMemo(() => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
        }).format(house.price);
    }, [house.price]);

    return (
        <View style={styles.card}>
            <Image
                source={{ uri: house.firstImage }}
                style={[styles.image, { height: onMap ? 120 : 168 }]}
                resizeMode="cover"
            />
            <View style={styles.info}>
                <Text numberOfLines={2} style={styles.title}>
                    {house.shortDescription}
                </Text>
                <HouseDetails
                    bedrooms={house.bedrooms}
                    bathrooms={house.bathrooms}
                    city={house.city}
                />
                <View style={styles.footer}>
                    <Text style={styles.price}>{formattedPrice}</Text>
                    <CustomButton
                        variant="yellow"
                        content={t('view')}
                        onPress={handlePress}
                    />
                </View>
            </View>
        </View>
    );
});

const styles = StyleSheet.create({
    card: {
        borderRadius: 16,
        overflow: 'hidden',
        backgroundColor: '#fff',
        marginBottom: 16,
        elevation: 3,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 2,
    },
    image: {
        width: '100%',
        backgroundColor: '#f0f0f0',
    },
    info: {
        padding: 12,
        gap: 12,
    },
    title: {
        color: '#030303',
        fontSize: 16,
        fontFamily: 'Roboto',
        fontWeight: '500',
        lineHeight: 19,
    },
    detailsRow: {
        flexDirection: 'row',
        alignItems: 'center',
        flexWrap: 'wrap',
    },
    detailText: {
        marginHorizontal: 6,
        fontSize: 14,
        color: '#555',
    },
    iconMargin: {
        marginLeft: 12,
    },
    footer: {
        marginBottom: 4,
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'flex-end',
    },
    price: {
        fontSize: 16,
        fontWeight: '600',
        color: '#030303',
    },
});

export default HouseCard;