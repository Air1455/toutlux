import { DetailRow } from "@/components/house-details/DetailRow";
import CustomButton from "@/components/CustomButton";
import { useGetHousesQuery } from "@/redux/api/houseApi";
import { useLocalSearchParams, useNavigation } from 'expo-router';
import React, { useLayoutEffect, useState, useMemo, useCallback } from 'react';
import { useTranslation } from 'react-i18next';
import {
    Image,
    Pressable,
    ScrollView,
    StyleSheet,
    Text,
    View,
    ActivityIndicator
} from 'react-native';
import ImageViewing from 'react-native-image-viewing';
import { useTheme } from "react-native-paper";
import {useHeaderOptions} from "@/hooks/useHeaderOptions";
// Supprimé l'import SafeScreen - pas nécessaire pour les écrans avec header

const PropertyLabels = React.memo(({ house, t }) => (
    <View style={styles.labels}>
        {house.bedrooms && (
            <CustomButton
                content={`${house.bedrooms} ${t(`homeDetails.${house.bedrooms === 1 ? 'bedroom' : 'bedrooms'}`)}`}
                iconName="bed"
                variant="blue"
            />
        )}
        {house.bathrooms && (
            <CustomButton
                content={`${house.bathrooms} ${t(`homeDetails.${house.bathrooms === 1 ? 'bathroom' : 'bathrooms'}`)}`}
                iconName="bath"
                variant="blue"
            />
        )}
        {house.garages && (
            <CustomButton
                content={`${house.garages} ${t(`homeDetails.${house.garages === 1 ? 'garage' : 'garages'}`)}`}
                iconName="car"
                variant="blue"
            />
        )}
        {house.swimmingPools && (
            <CustomButton
                content={`${house.swimmingPools} ${t(`homeDetails.${house.swimmingPools === 1 ? 'swimmingPool' : 'swimmingPools'}`)}`}
                iconName="swimming-pool"
                variant="blue"
            />
        )}
    </View>
));

const OtherImages = React.memo(({ images, onImagePress }) => (
    <ScrollView
        horizontal
        style={styles.otherImagesContainer}
        showsHorizontalScrollIndicator={false}
    >
        {images?.map((img, index) => (
            <Pressable
                key={index}
                onPress={() => onImagePress(index + 1)}
                style={styles.otherImagePressable}
            >
                <Image
                    source={{ uri: img }}
                    style={styles.otherImage}
                    resizeMode="cover"
                />
            </Pressable>
        ))}
    </ScrollView>
));

export default function HouseDetails() {
    const { id } = useLocalSearchParams();
    const { data: houses = [], isLoading } = useGetHousesQuery();
    const { colors } = useTheme();
    const { t } = useTranslation();

    const [visible, setVisible] = useState(false);
    const [imageIndex, setImageIndex] = useState(0);

    const house = useMemo(() => houses.find(h => h.id === parseInt(id)), [houses, id]);

    const headerTitle = house?.shortDescription || t('loading');
    useHeaderOptions(headerTitle, [house, t]);

    const galleryImages = useMemo(() =>
            house ? [
                { uri: house.firstImage },
                ...house.otherImages.map(img => ({ uri: img })),
            ] : [],
        [house]
    );

    const handleImagePress = useCallback((index) => {
        setVisible(true);
        setImageIndex(index);
    }, []);

    if (isLoading) {
        return (
            <View style={[styles.centered, { backgroundColor: colors.background }]}>
                <ActivityIndicator size="large" color={colors.primary} />
                <Text style={[styles.loadingText, { color: colors.text }]}>
                    {t('loading')}
                </Text>
            </View>
        );
    }

    if (!house) {
        return (
            <View style={[styles.centered, { backgroundColor: colors.background }]}>
                <Text style={[styles.errorText, { color: colors.error }]}>
                    {t('errors.houseNotFound')}
                </Text>
            </View>
        );
    }

    return (
        // Utilisez background pour tout le container
        <View style={[styles.container, { backgroundColor: colors.background }]}>
            <ScrollView
                style={styles.scrollView}
                showsVerticalScrollIndicator={false}
            >
                <View style={styles.imageWrapper}>
                    <Pressable onPress={() => handleImagePress(0)}>
                        <Image
                            source={{ uri: house.firstImage }}
                            style={styles.mainImage}
                            resizeMode="cover"
                        />
                    </Pressable>
                    <View style={[styles.badge, { backgroundColor: colors.primary }]}>
                        <Text style={styles.badgeText}>
                            {house.forRent ? t('homeDetails.forRent') : t('homeDetails.forSale')}
                        </Text>
                    </View>
                </View>

                <View style={[styles.contentContainer, { backgroundColor: colors.surface }]}>
                    <View style={[styles.bloc, { borderBottomColor: colors.outline, paddingTop: 0 }]}>
                        <PropertyLabels house={house} t={t} />
                    </View>

                    <View style={[styles.bloc, { borderBottomColor: colors.outline }]}>
                        <Text style={[styles.subtitle, { color: colors.text }]}>
                            {t('homeDetails.fullDescription')}
                        </Text>
                        <Text style={[styles.description, { color: colors.text }]}>
                            {house.longDescription || t('homeDetails.noDescription')}
                        </Text>
                    </View>

                    <View style={[styles.bloc, { borderBottomColor: colors.outline }]}>
                        <Text style={[styles.subtitle, { color: colors.text }]}>
                            {t('homeDetails.otherDetails')}
                        </Text>

                        <DetailRow
                            icon="home"
                            label={t('homeDetails.type')}
                            value={house.type}
                        />
                        <DetailRow
                            icon="map-marker-alt"
                            label={t('homeDetails.address')}
                            value={`${house.address}, ${house.city}, ${house.country}`}
                        />
                        <DetailRow
                            icon="ruler"
                            label={t('homeDetails.surface')}
                            value={house.surface}
                        />
                        <DetailRow
                            icon="calendar"
                            label={t('homeDetails.yearOfConstruction')}
                            value={house.yearOfConstruction}
                        />
                        <DetailRow
                            icon="tag"
                            label={t('homeDetails.price')}
                            value={`${house.currency}${house.price.toLocaleString()}${house.forRent ? ` / ${t('month')}` : ''}`}
                        />
                    </View>

                    {house.otherImages && house.otherImages.length > 0 && (
                        <View style={styles.bloc}>
                            <Text style={[styles.subtitle, { color: colors.text }]}>
                                {t('homeDetails.otherImages')}
                            </Text>
                            <OtherImages
                                images={house.otherImages}
                                onImagePress={handleImagePress}
                            />
                        </View>
                    )}
                </View>
            </ScrollView>

            <ImageViewing
                images={galleryImages}
                imageIndex={imageIndex}
                visible={visible}
                onRequestClose={() => setVisible(false)}
                backgroundColor={colors.surface}
            />
        </View>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    scrollView: {
        flex: 1,
    },
    contentContainer: {
        paddingHorizontal: 14,
        paddingVertical: 20,
        borderTopLeftRadius: 20,
        borderTopRightRadius: 20,
        marginTop: -20, // Overlap avec l'image pour un effet moderne
    },
    badge: {
        position: 'absolute',
        top: 16,
        left: 16,
        paddingVertical: 6,
        paddingHorizontal: 14,
        borderRadius: 9999,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.2,
        shadowRadius: 3,
        elevation: 4,
    },
    badgeText: {
        color: '#fff',
        fontWeight: '600',
        fontSize: 14,
        textTransform: 'uppercase',
    },
    mainImage: {
        width: '100%',
        height: 250,
        backgroundColor: '#f0f0f0',
    },
    labels: {
        flexDirection: 'row',
        gap: 6,
        flexWrap: 'wrap',
    },
    bloc: {
        borderBottomWidth: 2,
        paddingBottom: 30,
        paddingTop: 20,
    },
    imageWrapper: {
        position: 'relative',
    },
    otherImagesContainer: {
        flexDirection: 'row',
        paddingHorizontal: 10,
        marginTop: 10,
    },
    otherImagePressable: {
        marginRight: 10,
    },
    otherImage: {
        width: 100,
        height: 100,
        borderRadius: 10,
        backgroundColor: '#f0f0f0',
    },
    subtitle: {
        fontSize: 16,
        fontWeight: '700',
        paddingBottom: 15,
        fontFamily: 'Prompt_800ExtraBold',
    },
    description: {
        fontSize: 14,
        lineHeight: 22,
        fontFamily: 'Prompt_400Regular',
    },
    centered: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
    },
    loadingText: {
        marginTop: 10,
        fontSize: 14,
        fontFamily: 'Prompt_400Regular',
    },
    errorText: {
        fontSize: 16,
        textAlign: 'center',
        fontFamily: 'Prompt_400Regular',
    },
});