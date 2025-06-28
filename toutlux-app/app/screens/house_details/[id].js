import { DetailRow } from "@/components/house-details/DetailRow";
import CustomButton from "@/components/CustomButton";
import {useGetHouseQuery} from "@/redux/api/houseApi";
import { useLocalSearchParams, useRouter } from 'expo-router';
import React, { useState, useMemo, useCallback } from 'react';
import { useTranslation } from 'react-i18next';
import {
    Image,
    Pressable,
    ScrollView,
    StyleSheet,
    View,
    ActivityIndicator
} from 'react-native';
import ImageViewing from 'react-native-image-viewing';
import { useTheme, Button } from "react-native-paper";
import { MaterialIcons } from '@expo/vector-icons';
import { useHeaderOptions } from "@/hooks/useHeaderOptions";
import { useCurrentUser, normalizeUserId } from '@/hooks/useIsCurrentUser';
import Text from '@/components/typography/Text';
import { SPACING, BORDER_RADIUS, ELEVATION } from '@/constants/spacing';
import {LoadingScreen} from "@components/Loading";

const PropertyLabels = React.memo(({ house, t }) => {
    // Fonction helper pour vérifier si une valeur doit être affichée (pas 0, null ou undefined)
    const shouldShow = (value) => value !== null && value !== undefined && value > 0;

    return (
        <View style={styles.labels}>
            {shouldShow(house.bedrooms) && (
                <CustomButton
                    content={`${house.bedrooms} ${t(`houseDetails.${house.bedrooms === 1 ? 'bedroom' : 'bedrooms'}`)}`}
                    iconName="bed"
                    variant="blue"
                />
            )}
            {shouldShow(house.bathrooms) && (
                <CustomButton
                    content={`${house.bathrooms} ${t(`houseDetails.${house.bathrooms === 1 ? 'bathroom' : 'bathrooms'}`)}`}
                    iconName="bathtub"
                    variant="blue"
                />
            )}
            {shouldShow(house.garages) && (
                <CustomButton
                    content={`${house.garages} ${t(`houseDetails.${house.garages === 1 ? 'garage' : 'garages'}`)}`}
                    iconName="garage"
                    variant="blue"
                />
            )}
            {shouldShow(house.swimmingPools) && (
                <CustomButton
                    content={`${house.swimmingPools} ${t(`houseDetails.${house.swimmingPools === 1 ? 'swimmingPool' : 'swimmingPools'}`)}`}
                    iconName="pool"
                    variant="blue"
                />
            )}
        </View>
    );
});

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

const HouseDetails = () => {
    const { id } = useLocalSearchParams();
    const { data: house = [], isLoading } = useGetHouseQuery(id);
    const { colors } = useTheme();
    const { t } = useTranslation();
    const router = useRouter();

    const [visible, setVisible] = useState(false);
    const [imageIndex, setImageIndex] = useState(0);

    const { user: currentUser, userId: currentUserId, isAuthenticated } = useCurrentUser();

    // Assurer que headerTitle est toujours une string
    const headerTitle = useMemo(() => {
        const title = house?.shortDescription || t('common.loading');
        return String(title);
    }, [house, t]);

    useHeaderOptions(headerTitle, [house, t]);

    const houseOwnerId = useMemo(() => normalizeUserId(house?.user), [house]);
    const isOwnListing = currentUserId && houseOwnerId && currentUserId === houseOwnerId;

    // ✅ CORRECTION: Logique pour afficher les boutons
    const shouldShowContactButton = useMemo(() => {
        if (!house?.user || isOwnListing) {
            return false;
        }
        return isAuthenticated;
    }, [house?.user, isOwnListing, isAuthenticated]);

    const shouldShowLoginPrompt = useMemo(() => {
        return house?.user && !isAuthenticated && !isOwnListing;
    }, [house?.user, isAuthenticated, isOwnListing]);

    console.log('🔍 Button visibility debug:', {
        hasHouse: !!house,
        hasHouseUser: !!house?.user,
        isAuthenticated,
        isOwnListing,
        currentUserId,
        houseOwnerId,
        shouldShowContactButton,
        shouldShowLoginPrompt
    });

    const galleryImages = useMemo(() =>
            house ? [
                { uri: house.firstImage },
                ...(house.otherImages || []).map(img => ({ uri: img })),
            ] : [],
        [house]
    );

    const handleImagePress = useCallback((index) => {
        setVisible(true);
        setImageIndex(index);
    }, []);

    // Styles dynamiques basés sur le theme
    const dynamicStyles = useMemo(() => StyleSheet.create({
        loginPromptContainer: {
            backgroundColor: colors.surfaceVariant,
            margin: SPACING.lg,
            borderRadius: BORDER_RADIUS.lg,
            padding: SPACING.xl,
            alignItems: 'center',
            marginTop: SPACING.xl,
            elevation: ELEVATION.medium,
        },
        loginButton: {
            backgroundColor: colors.primary,
            borderRadius: BORDER_RADIUS.md,
            flex: 1,
        },
        ownerInfoContainer: {
            backgroundColor: colors.surfaceVariant,
            margin: SPACING.lg,
            borderRadius: BORDER_RADIUS.lg,
            padding: SPACING.xl,
            alignItems: 'center',
            marginTop: SPACING.xl,
            elevation: ELEVATION.medium,
        },
    }), [colors]);

    if (isLoading) {
        return <LoadingScreen />
    }

    if (!house) {
        return (
            <View style={[styles.centered, { backgroundColor: colors.background }]}>
                <Text variant="bodyLarge" color="error" style={styles.errorText}>
                    {t('errors.houseNotFound')}
                </Text>
            </View>
        );
    }

    // ✅ NOUVEAU: Fonction pour rendre la section de contact/connexion
    const renderContactSection = () => {
        // Si c'est ma propre annonce, afficher un message informatif ou rien
        if (isOwnListing) {
            return (
                <View style={dynamicStyles.ownerInfoContainer}>
                    <View style={styles.loginPromptContent}>
                        <MaterialIcons
                            name="home"
                            size={48}
                            color={colors.primary}
                            style={styles.loginIcon}
                        />
                        <Text variant="cardTitle" color="textSecondary" style={styles.loginPromptTitle}>
                            {t('houseDetails.yourListing')}
                        </Text>
                        <Text variant="bodyMedium" color="textSecondary" style={styles.loginPromptDescription}>
                            {t('houseDetails.yourListingDescription')}
                        </Text>
                    </View>
                </View>
            );
        }

        // Si connecté et ce n'est pas ma maison, bouton de contact
        if (shouldShowContactButton) {
            return (
                <View style={styles.contactSellerContainer}>
                    <Button
                        mode="contained"
                        onPress={() => {
                            const sellerId = normalizeUserId(house.user);
                            if (sellerId) {
                                router.push(`/screens/seller_profile/${sellerId}`);
                            }
                        }}
                        style={[styles.contactButton, { backgroundColor: colors.primary }]}
                        contentStyle={styles.contactButtonContent}
                        labelStyle={styles.contactButtonLabel}
                        icon="account-eye"
                    >
                        {t('seller.viewProfile')}
                    </Button>
                </View>
            );
        }

        // Si pas connecté, prompt de connexion
        if (shouldShowLoginPrompt) {
            return (
                <View style={dynamicStyles.loginPromptContainer}>
                    <View style={styles.loginPromptContent}>
                        <MaterialIcons
                            name="account-circle"
                            size={48}
                            color={colors.primary}
                            style={styles.loginIcon}
                        />
                        <Text variant="cardTitle" color="textSecondary" style={styles.loginPromptTitle}>
                            {t('login.loginRequired')}
                        </Text>
                        <Text variant="bodyMedium" color="textSecondary" style={styles.loginPromptDescription}>
                            {t('login.loginToContactSeller')}
                        </Text>
                        <View style={styles.loginButtonsContainer}>
                            <Button
                                mode="contained"
                                onPress={() => router.push('/screens/login')}
                                style={dynamicStyles.loginButton}
                                contentStyle={styles.loginButtonContent}
                                labelStyle={styles.loginButtonLabel}
                                icon="login"
                            >
                                {t('login.submit')}
                            </Button>
                        </View>
                    </View>
                </View>
            );
        }

        // Si aucune condition n'est remplie, ne rien afficher
        return null;
    };

    return (
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
                        <Text variant="labelSmall" style={styles.badgeText}>
                            {house.isForRent ? t('houseDetails.forRent') : t('houseDetails.forSale')}
                        </Text>
                    </View>
                </View>

                <View style={[styles.contentContainer, { backgroundColor: colors.surface }]}>
                    <View style={[styles.bloc, { borderBottomColor: colors.outline, paddingTop: 0 }]}>
                        <PropertyLabels house={house} t={t} />
                    </View>

                    <View style={[styles.bloc, { borderBottomColor: colors.outline }]}>
                        <Text variant="sectionTitle" color="textPrimary" style={styles.subtitle}>
                            {t('houseDetails.fullDescription')}
                        </Text>
                        <Text variant="bodyMedium" color="textPrimary" style={styles.description}>
                            {house.longDescription || t('houseDetails.noDescription')}
                        </Text>
                    </View>

                    <View style={[styles.bloc, { borderBottomColor: colors.outline }]}>
                        <Text variant="sectionTitle" color="textPrimary" style={styles.subtitle}>
                            {t('houseDetails.otherDetails')}
                        </Text>

                        <DetailRow
                            icon="home"
                            label={t('houseDetails.type')}
                            value={String(house.type || '')}
                        />
                        <DetailRow
                            icon="map-marker-alt"
                            label={t('houseDetails.address')}
                            value={`${house.address || ''}, ${house.city || ''}, ${house.country || ''}`}
                        />
                        <DetailRow
                            icon="ruler"
                            label={t('houseDetails.surface')}
                            value={String(house.surface || '')}
                        />
                        <DetailRow
                            icon="calendar"
                            label={t('houseDetails.yearOfConstruction')}
                            value={String(house.yearOfConstruction || '')}
                        />
                        <DetailRow
                            icon="tag"
                            label={t('houseDetails.price')}
                            value={{
                                amount: house.price,
                                currency: house.currency,
                                isForRent: house.isForRent
                            }}
                            type="price"
                        />
                    </View>

                    {house.otherImages && house.otherImages.length > 0 && (
                        <View style={[styles.bloc, { borderBottomColor: colors.outline }]}>
                            <Text variant="sectionTitle" color="textPrimary" style={styles.subtitle}>
                                {t('houseDetails.otherImages')}
                            </Text>
                            <OtherImages
                                images={house.otherImages}
                                onImagePress={handleImagePress}
                            />
                        </View>
                    )}

                    {/* ✅ CORRECTION: Section de contact conditionnelle */}
                    {renderContactSection()}
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
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    scrollView: {
        flex: 1,
    },
    contentContainer: {
        paddingHorizontal: SPACING.lg,
        paddingVertical: SPACING.xl,
        borderTopLeftRadius: BORDER_RADIUS.xl,
        borderTopRightRadius: BORDER_RADIUS.xl,
        marginTop: -SPACING.xl,
    },
    badge: {
        position: 'absolute',
        top: SPACING.lg,
        left: SPACING.lg,
        paddingVertical: SPACING.sm,
        paddingHorizontal: SPACING.lg,
        borderRadius: 9999,
        elevation: ELEVATION.high,
    },
    badgeText: {
        color: '#fff',
        textTransform: 'uppercase',
    },
    mainImage: {
        width: '100%',
        height: 250,
        backgroundColor: '#f0f0f0',
    },
    labels: {
        flexDirection: 'row',
        gap: SPACING.sm,
        flexWrap: 'wrap',
    },
    bloc: {
        borderBottomWidth: 2,
        paddingBottom: SPACING.xxxl,
        paddingTop: SPACING.xl,
    },
    imageWrapper: {
        position: 'relative',
    },
    otherImagesContainer: {
        flexDirection: 'row',
        paddingHorizontal: SPACING.md,
        marginTop: SPACING.md,
    },
    otherImagePressable: {
        marginRight: SPACING.md,
    },
    otherImage: {
        width: 100,
        height: 100,
        borderRadius: BORDER_RADIUS.md,
        backgroundColor: '#f0f0f0',
    },
    subtitle: {
        paddingBottom: SPACING.lg,
    },
    description: {
        lineHeight: 22,
    },
    centered: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
    },
    loadingText: {
        marginTop: SPACING.md,
    },
    errorText: {
        textAlign: 'center',
    },
    contactSellerContainer: {
        padding: SPACING.lg,
        backgroundColor: 'transparent',
    },
    contactButton: {
        borderRadius: BORDER_RADIUS.md,
    },
    contactButtonContent: {
        height: 48,
    },
    contactButtonLabel: {
        fontSize: 14,
    },
    // Styles pour le prompt de connexion
    loginPromptContent: {
        alignItems: 'center',
        maxWidth: 280,
    },
    loginIcon: {
        marginBottom: SPACING.lg,
        opacity: 0.8,
    },
    loginPromptTitle: {
        textAlign: 'center',
        marginBottom: SPACING.sm,
    },
    loginPromptDescription: {
        textAlign: 'center',
        lineHeight: 20,
        marginBottom: SPACING.xl,
        opacity: 0.8,
    },
    loginButtonsContainer: {
        flexDirection: 'row',
        gap: SPACING.md,
        width: '100%',
    },
    loginButtonContent: {
        height: 44,
        paddingHorizontal: SPACING.lg,
    },
    loginButtonLabel: {
        fontSize: 14,
    },
});

export default HouseDetails;