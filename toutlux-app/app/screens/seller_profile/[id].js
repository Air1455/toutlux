import React, { useState, useEffect } from 'react';
import {
    View,
    ScrollView,
    StyleSheet,
    RefreshControl,
    Alert,
    TouchableOpacity,
} from 'react-native';
import {
    useTheme,
    ActivityIndicator,
    Button,
    Portal,
    List,
    Divider,
    Chip,
    Surface,
} from 'react-native-paper';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { LinearGradient } from 'expo-linear-gradient';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import Animated, {
    useSharedValue,
    useAnimatedStyle,
    withTiming,
    withSpring,
    interpolate,
    runOnJS,
    useAnimatedRef,
    withDelay,
} from 'react-native-reanimated';

import { useGetUserByIdQuery } from '@/redux/api/userApi';
import { useGetUserHousesByIdQuery } from '@/redux/api/houseApi';
import { useCurrentUser } from '@/hooks/useIsCurrentUser';
import { ProfileCard } from '@/components/profile/ProfileCard';
import HouseCard from '@components/home/HouseCard';
import Text from '@/components/typography/Text';
import { SPACING, BORDER_RADIUS, ELEVATION } from '@/constants/spacing';
import { useHeaderOptions } from '@/hooks/useHeaderOptions';
import {LoadingScreen} from "@components/Loading";

export default function SellerProfileScreen() {
    const { colors } = useTheme();
    const { t } = useTranslation();
    const router = useRouter();
    const { id } = useLocalSearchParams();

    const [refreshing, setRefreshing] = useState(false);
    const [showHouseSelector, setShowHouseSelector] = useState(false);
    const [verificationsExpanded, setVerificationsExpanded] = useState(false);
    const [isInitialLoading, setIsInitialLoading] = useState(true);

    // Reanimated shared values
    const rotateValue = useSharedValue(0);
    const fadeValue = useSharedValue(0);
    const heightValue = useSharedValue(0);
    const scrollRef = useAnimatedRef();

    // Animations d'entrée
    const profileEntranceY = useSharedValue(50);
    const profileEntranceOpacity = useSharedValue(0);
    const statsEntranceY = useSharedValue(50);
    const statsEntranceOpacity = useSharedValue(0);
    const verificationsEntranceY = useSharedValue(50);
    const verificationsEntranceOpacity = useSharedValue(0);
    const listingsEntranceY = useSharedValue(50);
    const listingsEntranceOpacity = useSharedValue(0);

    // Animation du bouton de contact
    const contactButtonScale = useSharedValue(0.8);
    const contactButtonOpacity = useSharedValue(0);

    // Animations du dialog
    const dialogScale = useSharedValue(0.3);
    const dialogOpacity = useSharedValue(0);
    const dialogBackdropOpacity = useSharedValue(0);

    const {
        data: seller,
        isLoading: isLoadingSeller,
        error: sellerError,
        refetch: refetchSeller
    } = useGetUserByIdQuery(id, {
        skip: !id,
    });

    const {
        data: sellerHouses = [],
        isLoading: isLoadingHouses,
        refetch: refetchHouses
    } = useGetUserHousesByIdQuery(id, {
        skip: !id,
    });

    const { user: currentUser, isAuthenticated } = useCurrentUser();
    const isOwnProfile = currentUser?.id === parseInt(id);

    const isDataLoading = isLoadingSeller || isLoadingHouses;

    // Configuration du header avec thème immédiat
    useHeaderOptions(
        seller ? t('seller.profileTitleWithName', { name: seller.firstName }) : t('seller.profileTitle'),
        undefined,
        {headerTitleAlign: 'center'}
    );

    // Gestion du loading initial
    useEffect(() => {
        const minLoadingTime = setTimeout(() => {
            setIsInitialLoading(false);
        }, 500); // Délai minimum pour éviter les flashs

        return () => clearTimeout(minLoadingTime);
    }, []);

    useEffect(() => {
        if (!isAuthenticated) {
            Alert.alert(
                t('login.loginRequired'),
                t('seller.loginToViewProfile'),
                [
                    { text: t('common.cancel'), onPress: () => router.back() },
                    { text: t('login.submit'), onPress: () => router.push('/screens/login') }
                ]
            );
            return;
        }

        if (isOwnProfile && !isDataLoading && !isInitialLoading) {
            router.replace('/(tabs)/profile');
        }
    }, [isOwnProfile, isDataLoading, isInitialLoading, isAuthenticated]);

    // Animation d'entrée quand les données sont chargées
    useEffect(() => {
        if (!isDataLoading && !isInitialLoading && seller) {
            // Animation séquentielle des éléments
            profileEntranceY.value = withSpring(0, { damping: 15, stiffness: 100 });
            profileEntranceOpacity.value = withTiming(1, { duration: 400 });

            statsEntranceY.value = withDelay(100, withSpring(0, { damping: 15, stiffness: 100 }));
            statsEntranceOpacity.value = withDelay(100, withTiming(1, { duration: 400 }));

            verificationsEntranceY.value = withDelay(200, withSpring(0, { damping: 15, stiffness: 100 }));
            verificationsEntranceOpacity.value = withDelay(200, withTiming(1, { duration: 400 }));

            listingsEntranceY.value = withDelay(300, withSpring(0, { damping: 15, stiffness: 100 }));
            listingsEntranceOpacity.value = withDelay(300, withTiming(1, { duration: 400 }));

            // Animation du bouton de contact
            if (!isOwnProfile) {
                contactButtonScale.value = withDelay(400, withSpring(1, { damping: 12, stiffness: 120 }));
                contactButtonOpacity.value = withDelay(400, withTiming(1, { duration: 300 }));
            }
        }
    }, [isDataLoading, isInitialLoading, seller, isOwnProfile]);

    const onRefresh = async () => {
        setRefreshing(true);
        try {
            await Promise.all([refetchSeller(), refetchHouses()]);
        } catch (error) {
            console.error('Error refreshing:', error);
        } finally {
            setRefreshing(false);
        }
    };

    const handleContactPress = () => {
        if (!isAuthenticated) {
            Alert.alert(
                t('login.loginRequired'),
                t('login.loginToContactSeller'),
                [
                    { text: t('common.cancel'), style: 'cancel' },
                    { text: t('login.submit'), onPress: () => router.push('/screens/login') }
                ]
            );
            return;
        }

        if (sellerHouses.length === 0) {
            Alert.alert(t('common.info'), t('seller.noActiveListings'));
            return;
        }

        if (sellerHouses.length === 1) {
            router.push(`/screens/contact-seller?houseId=${sellerHouses[0].id}&sellerId=${id}`);
        } else {
            setShowHouseSelector(true);
            // Déclencher les animations d'ouverture du dialog
            animateDialogOpen();
        }
    };

    const animateDialogOpen = () => {
        // Reset des valeurs
        dialogScale.value = 0.3;
        dialogOpacity.value = 0;
        dialogBackdropOpacity.value = 0;

        // Animation du backdrop
        dialogBackdropOpacity.value = withTiming(1, { duration: 200 });

        // Animation d'entrée du dialog avec spring
        dialogScale.value = withSpring(1, {
            damping: 18,
            stiffness: 200,
        });

        dialogOpacity.value = withTiming(1, { duration: 300 });
    };

    const animateDialogClose = () => {
        // Animation de fermeture
        dialogScale.value = withSpring(0.8, {
            damping: 20,
            stiffness: 300,
        });

        dialogOpacity.value = withTiming(0, { duration: 250 });

        dialogBackdropOpacity.value = withTiming(0, { duration: 300 }, () => {
            // Fermer le dialog une fois l'animation terminée
            runOnJS(setShowHouseSelector)(false);
        });
    };

    const handleSelectHouse = (house) => {
        animateDialogClose();
        // Délai pour laisser l'animation se terminer avant la navigation
        setTimeout(() => {
            router.push(`/screens/contact_seller?houseId=${house.id}&sellerId=${id}`);
        }, 300);
    };

    const handleDialogDismiss = () => {
        animateDialogClose();
    };

    const toggleVerifications = () => {
        const isExpanding = !verificationsExpanded;

        // Update state
        runOnJS(setVerificationsExpanded)(isExpanding);

        if (isExpanding) {
            // Expanding animation
            rotateValue.value = withSpring(1, {
                damping: 15,
                stiffness: 150,
            });

            heightValue.value = withTiming(1, {
                duration: 300,
            });

            fadeValue.value = withTiming(1, {
                duration: 300,
            });
        } else {
            // Collapsing animation
            rotateValue.value = withSpring(0, {
                damping: 15,
                stiffness: 150,
            });

            fadeValue.value = withTiming(0, {
                duration: 200,
            });

            heightValue.value = withTiming(0, {
                duration: 250,
            });
        }
    };

    // Animated styles
    const rotateStyle = useAnimatedStyle(() => {
        const rotate = interpolate(rotateValue.value, [0, 1], [0, 180]);
        return {
            transform: [{ rotate: `${rotate}deg` }],
        };
    });

    const expandableContentStyle = useAnimatedStyle(() => {
        const maxHeight = interpolate(heightValue.value, [0, 1], [0, 400]);

        return {
            maxHeight,
            opacity: fadeValue.value,
            overflow: 'hidden',
        };
    });

    // Styles d'animation d'entrée
    const profileEntranceStyle = useAnimatedStyle(() => ({
        opacity: profileEntranceOpacity.value,
        transform: [{ translateY: profileEntranceY.value }],
    }));

    const statsEntranceStyle = useAnimatedStyle(() => ({
        opacity: statsEntranceOpacity.value,
        transform: [{ translateY: statsEntranceY.value }],
    }));

    const verificationsEntranceStyle = useAnimatedStyle(() => ({
        opacity: verificationsEntranceOpacity.value,
        transform: [{ translateY: verificationsEntranceY.value }],
    }));

    const listingsEntranceStyle = useAnimatedStyle(() => ({
        opacity: listingsEntranceOpacity.value,
        transform: [{ translateY: listingsEntranceY.value }],
    }));

    const contactButtonStyle = useAnimatedStyle(() => ({
        opacity: contactButtonOpacity.value,
        transform: [{ scale: contactButtonScale.value }],
    }));

    // Styles d'animation du dialog
    const dialogContainerStyle = useAnimatedStyle(() => ({
        opacity: dialogBackdropOpacity.value,
    }));

    const dialogContentStyle = useAnimatedStyle(() => ({
        opacity: dialogOpacity.value,
        transform: [
            { scale: dialogScale.value },
            {
                translateY: interpolate(
                    dialogScale.value,
                    [0.3, 1],
                    [50, 0]
                )
            }
        ],
    }));

    const formatPrice = (price, currency) => {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: currency || 'EUR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(price);
    };

    const renderStats = () => {
        if (!seller) return null;

        const verificationScore = seller.verificationScore || 0;
        let scoreColor = colors.error;
        if (verificationScore >= 75) scoreColor = '#4CAF50';
        else if (verificationScore >= 50) scoreColor = '#FF9800';
        else if (verificationScore >= 25) scoreColor = '#2196F3';

        return (
            <Animated.View style={[statsEntranceStyle]}>
                <Surface style={styles.statsCard} elevation={1}>
                    <View style={styles.statsHeader}>
                        <MaterialCommunityIcons name="chart-line" size={24} color={colors.primary} />
                        <Text variant="cardTitle" color="textPrimary" style={styles.statsTitle}>
                            {t('seller.statistics')}
                        </Text>
                    </View>

                    <View style={styles.statsGrid}>
                        <View style={styles.statItem}>
                            <Text variant="heroTitle" color="primary" style={styles.statValue}>
                                {seller.housesCount || sellerHouses.length}
                            </Text>
                            <Text variant="bodySmall" color="textSecondary" style={styles.statLabel}>
                                {t('seller.activeListings')}
                            </Text>
                        </View>

                        <View style={styles.statDivider} />

                        <View style={styles.statItem}>
                            <Text variant="heroTitle" style={[styles.statValue, { color: scoreColor }]}>
                                {verificationScore}%
                            </Text>
                            <Text variant="bodySmall" color="textSecondary" style={styles.statLabel}>
                                {t('seller.verification')}
                            </Text>
                        </View>

                        <View style={styles.statDivider} />

                        <View style={styles.statItem}>
                            <Text variant="heroTitle" color="primary" style={styles.statValue}>
                                {seller.profileViews || 0}
                            </Text>
                            <Text variant="bodySmall" color="textSecondary" style={styles.statLabel}>
                                {t('seller.profileViews')}
                            </Text>
                        </View>
                    </View>
                </Surface>
            </Animated.View>
        );
    };

    const renderVerifications = () => {
        if (!seller) return null;

        const validationStatus = seller?.validationStatus || {};
        const verifications = [
            {
                key: 'email',
                label: t('validation.email.verified'),
                verified: validationStatus.email?.isVerified || false,
                icon: 'email'
            },
            {
                key: 'phone',
                label: t('validation.phoneNumber.verified'),
                verified: validationStatus.phone?.isVerified || false,
                icon: 'phone'
            },
            {
                key: 'identity',
                label: t('validation.identityCard.verified'),
                verified: validationStatus.identity?.isVerified || false,
                icon: 'card-account-details'
            },
            {
                key: 'financial',
                label: t('validation.financial.verified'),
                verified: validationStatus.financialDocs?.isVerified || false,
                icon: 'file-document'
            },
            {
                key: 'terms',
                label: t('validation.terms.accepted'),
                verified: validationStatus.terms?.accepted || false,
                icon: 'handshake'
            },
        ];

        const completedCount = verifications.filter(v => v.verified).length;

        return (
            <Animated.View style={[verificationsEntranceStyle]}>
                <Surface style={styles.verificationsCard} elevation={1}>
                    <TouchableOpacity
                        onPress={toggleVerifications}
                        style={styles.verificationHeader}
                        activeOpacity={0.7}
                    >
                        <View style={styles.verificationHeaderContent}>
                            <MaterialCommunityIcons
                                name="shield-check-outline"
                                size={24}
                                color={colors.primary}
                            />
                            <View style={styles.verificationHeaderText}>
                                <Text variant="cardTitle" color="textPrimary">
                                    {t('seller.verifications')}
                                </Text>
                                <Text variant="bodySmall" color="textSecondary">
                                    {t('seller.verificationsSummary', {
                                        completed: completedCount,
                                        total: verifications.length
                                    })}
                                </Text>
                            </View>
                            <Chip
                                mode="outlined"
                                style={styles.verificationCount}
                                textStyle={styles.verificationCountText}
                            >
                                {completedCount}/{verifications.length}
                            </Chip>
                        </View>
                        <Animated.View style={rotateStyle}>
                            <MaterialCommunityIcons
                                name="chevron-down"
                                size={24}
                                color={colors.onSurface}
                            />
                        </Animated.View>
                    </TouchableOpacity>

                    <Animated.View style={expandableContentStyle}>
                        <Divider style={styles.verificationDivider} />
                        <View style={styles.verificationListContainer}>
                            <View style={styles.verificationList}>
                                {verifications.map((verification, index) => (
                                    <View
                                        key={verification.key}
                                        style={styles.verificationItem}
                                    >
                                        <MaterialCommunityIcons
                                            name={verification.icon}
                                            size={20}
                                            color={verification.verified ? '#4CAF50' : colors.textHint}
                                            style={styles.verificationIcon}
                                        />
                                        <Text
                                            variant="bodyMedium"
                                            style={[
                                                styles.verificationText,
                                                {
                                                    color: verification.verified
                                                        ? colors.textPrimary
                                                        : colors.textSecondary
                                                }
                                            ]}
                                        >
                                            {verification.label}
                                        </Text>
                                        <MaterialCommunityIcons
                                            name={verification.verified ? 'check-circle' : 'circle-outline'}
                                            size={20}
                                            color={verification.verified ? '#4CAF50' : colors.textHint}
                                        />
                                    </View>
                                ))}
                            </View>
                        </View>
                    </Animated.View>
                </Surface>
            </Animated.View>
        );
    };

    const renderSellerListings = () => {
        return (
            <Animated.View style={[listingsEntranceStyle]}>
                <Surface style={styles.listingsCard} elevation={1}>
                    <View style={styles.listingsHeader}>
                        <View style={styles.listingsHeaderContent}>
                            <MaterialCommunityIcons
                                name="home-group"
                                size={24}
                                color={colors.primary}
                            />
                            <View style={styles.listingsHeaderText}>
                                <Text variant="cardTitle" color="textPrimary" style={styles.listingsTitle}>
                                    {t('seller.listings', { name: seller?.firstName || t('common.seller') })}
                                </Text>
                                <Text variant="bodySmall" color="textSecondary">
                                    {t('seller.totalListings', { count: sellerHouses.length })}
                                </Text>
                            </View>
                        </View>

                        {sellerHouses.length > 0 && (
                            <View style={[styles.listingsCountBadge, { backgroundColor: colors.primary }]}>
                                <Text variant="labelMedium" style={styles.listingsCountText}>
                                    {sellerHouses.length}
                                </Text>
                            </View>
                        )}
                    </View>

                    <View style={styles.listingsContent}>
                        {sellerHouses.length > 0 ? (
                            <View style={styles.listingsContainer}>
                                {sellerHouses.map((house, index) => (
                                    <View key={house.id} style={styles.houseCardWrapper}>
                                        <HouseCard
                                            house={house}
                                            onPress={() => router.push(`/screens/house_details/${house.id}`)}
                                        />
                                    </View>
                                ))}
                            </View>
                        ) : (
                            <View style={styles.emptyState}>
                                <View style={[styles.emptyIconContainer, { backgroundColor: colors.surfaceVariant }]}>
                                    <MaterialCommunityIcons
                                        name="home-off-outline"
                                        size={32}
                                        color={colors.onSurfaceVariant}
                                    />
                                </View>
                                <Text variant="bodyLarge" color="textPrimary" style={styles.emptyTitle}>
                                    {t('seller.noListingsTitle')}
                                </Text>
                                <Text variant="bodyMedium" color="textSecondary" style={styles.emptyDescription}>
                                    {t('seller.noListingsDescription')}
                                </Text>
                            </View>
                        )}
                    </View>
                </Surface>
            </Animated.View>
        );
    };

    // Container style avec background permanent
    const containerStyle = {
        flex: 1,
        backgroundColor: colors.background, // Background appliqué immédiatement
    };

    // État de chargement avec skeleton
    if (isDataLoading || isInitialLoading) {
        return <LoadingScreen  />
    }

    if (sellerError || !seller) {
        return (
            <LinearGradient colors={[colors.background, colors.surface]} style={[containerStyle, styles.centered]}>
                <MaterialCommunityIcons
                    name="account-off"
                    size={64}
                    color={colors.textHint}
                />
                <Text variant="bodyLarge" color="error" style={styles.errorText}>
                    {t('seller.notFound')}
                </Text>
                <Button
                    mode="outlined"
                    onPress={() => router.back()}
                    style={styles.backButton}
                >
                    {t('common.goBack')}
                </Button>
            </LinearGradient>
        );
    }

    if (!isAuthenticated) {
        return (
            <LinearGradient colors={[colors.background, colors.surface]} style={[containerStyle, styles.centered]}>
                <MaterialCommunityIcons name="account-lock" size={80} color={colors.textHint} />
                <Text variant="bodyLarge" color="textSecondary" style={styles.errorText}>
                    {t('seller.loginRequired')}
                </Text>
                <Button
                    mode="contained"
                    onPress={() => router.push('/screens/login')}
                    style={styles.actionButton}
                    contentStyle={styles.actionButtonContent}
                >
                    {t('login.submit')}
                </Button>
            </LinearGradient>
        );
    }

    return (
        <LinearGradient colors={[colors.background, colors.surface]} style={containerStyle}>
            <Animated.ScrollView
                ref={scrollRef}
                showsVerticalScrollIndicator={false}
                contentContainerStyle={styles.scrollContent}
                refreshControl={
                    <RefreshControl
                        refreshing={refreshing}
                        onRefresh={onRefresh}
                        colors={[colors.primary]}
                        tintColor={colors.primary}
                    />
                }
            >
                <View style={styles.content}>
                    <Animated.View style={[profileEntranceStyle]}>
                        <ProfileCard user={seller} />
                    </Animated.View>
                    {renderStats()}
                    {renderVerifications()}
                    {renderSellerListings()}
                </View>
            </Animated.ScrollView>

            {/* Bouton de contact fixe */}
            {!isOwnProfile && (
                <Animated.View style={[
                    styles.contactButtonContainer,
                    { backgroundColor: colors.background },
                    contactButtonStyle
                ]}>
                    <Button
                        mode="contained"
                        onPress={handleContactPress}
                        style={styles.contactButton}
                        contentStyle={styles.contactButtonContent}
                        labelStyle={styles.contactButtonLabel}
                        icon={({ size, color }) => (
                            <MaterialCommunityIcons name="message-text" size={size + 4} color={color} />
                        )}
                    >
                        {t('seller.contact', { name: seller.firstName || t('common.seller') })}
                    </Button>
                </Animated.View>
            )}

            {/* Modal de sélection de bien avec animations */}
            <Portal>
                <Animated.View
                    style={[
                        StyleSheet.absoluteFillObject,
                        { backgroundColor: 'rgba(0, 0, 0, 0.5)' },
                        dialogContainerStyle
                    ]}
                    pointerEvents={showHouseSelector ? 'auto' : 'none'}
                >
                    {showHouseSelector && (
                        <View style={styles.dialogOverlay}>
                            <Animated.View style={[
                                styles.dialogContainer,
                                { backgroundColor: colors.surface },
                                dialogContentStyle
                            ]}>
                                <View style={styles.dialogHeader}>
                                    <Text variant="cardTitle" color="textPrimary" style={[styles.dialogTitle, {backgroundColor: colors.surface}]}>
                                        {t('seller.selectProperty')}
                                    </Text>
                                </View>

                                <ScrollView
                                    style={[styles.dialogScrollView, {backgroundColor: colors.surfaceVariant}]}
                                    showsVerticalScrollIndicator={false}
                                >
                                    <List.Section>
                                        {sellerHouses.map((house, index) => (
                                            <React.Fragment key={house.id}>
                                                <List.Item
                                                    title={house.shortDescription}
                                                    description={`${house.city} - ${formatPrice(house.price, house.currency)}`}
                                                    left={(props) => (
                                                        <List.Icon {...props} icon="home" />
                                                    )}
                                                    onPress={() => handleSelectHouse(house)}
                                                    style={styles.listItem}
                                                />
                                                {index < sellerHouses.length - 1 && (
                                                    <Divider style={styles.dialogDivider} />
                                                )}
                                            </React.Fragment>
                                        ))}
                                    </List.Section>
                                </ScrollView>

                                <View style={styles.dialogActions}>
                                    <Button onPress={handleDialogDismiss}>
                                        {t('common.cancel')}
                                    </Button>
                                </View>
                            </Animated.View>
                        </View>
                    )}
                </Animated.View>
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
    scrollContent: {
        paddingBottom: 100, // Espace pour le bouton fixe
    },
    content: {
        paddingHorizontal: SPACING.lg,
        gap: 16,
    },

    // Loading styles
    loadingContainer: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
        gap: SPACING.lg,
    },
    loadingText: {
        textAlign: 'center',
        marginTop: SPACING.md,
    },

    validationSection: {
        marginVertical: SPACING.xl,
    },
    sectionTitle: {
        marginBottom: SPACING.md,
    },
    validationItems: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: SPACING.sm,
    },
    validationChip: {
        backgroundColor: 'rgba(76, 175, 80, 0.1)',
    },
    validationChipText: {
        color: '#4CAF50',
        fontSize: 12,
    },
    identityChip: {
        backgroundColor: 'rgba(33, 150, 243, 0.1)',
    },
    identityChipText: {
        color: '#2196F3',
    },
    listingsSection: {
        marginVertical: SPACING.xl,
    },
    // Section des annonces redesignée
    listingsCard: {
        borderRadius: BORDER_RADIUS.lg,
        overflow: 'hidden',
    },
    listingsHeader: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        padding: SPACING.lg,
        paddingBottom: SPACING.md,
    },
    listingsHeaderContent: {
        flexDirection: 'row',
        alignItems: 'center',
        flex: 1,
    },
    listingsHeaderText: {
        marginLeft: SPACING.md,
        flex: 1,
    },
    listingsTitle: {
        fontSize: 18,
        fontWeight: '600',
        marginBottom: SPACING.xs,
    },
    listingsCountBadge: {
        width: 32,
        height: 32,
        borderRadius: 16,
        justifyContent: 'center',
        alignItems: 'center',
    },
    listingsCountText: {
        color: '#fff',
        fontWeight: 'bold',
        fontSize: 14,
    },
    listingsContent: {
        paddingHorizontal: SPACING.xs,
        paddingBottom: SPACING.lg,
    },
    listingsContainer: {
        marginTop: SPACING.sm,
        gap: SPACING.xs,
    },
    houseCardWrapper: {
        // Wrapper pour espacer uniformément les cards
    },
    emptyState: {
        alignItems: 'center',
        paddingVertical: SPACING.huge,
        gap: SPACING.lg,
    },
    emptyIconContainer: {
        width: 80,
        height: 80,
        borderRadius: 40,
        justifyContent: 'center',
        alignItems: 'center',
    },
    emptyTitle: {
        textAlign: 'center',
        fontSize: 18,
        fontWeight: '600',
    },
    emptyDescription: {
        textAlign: 'center',
        paddingHorizontal: SPACING.lg,
    },
    // Statistiques
    statsCard: {
        borderRadius: BORDER_RADIUS.lg,
        padding: SPACING.lg,
    },
    statsHeader: {
        flexDirection: 'row',
        alignItems: 'center',
        marginBottom: SPACING.lg,
    },
    statsTitle: {
        marginLeft: SPACING.md,
        fontSize: 18,
        fontWeight: '600',
    },
    statsGrid: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-around',
    },
    statItem: {
        alignItems: 'center',
        flex: 1,
    },
    statValue: {
        fontSize: 32,
        fontWeight: 'bold',
        marginBottom: SPACING.xs,
    },
    statLabel: {
        textAlign: 'center',
        lineHeight: 16,
    },
    statDivider: {
        width: 1,
        height: 50,
        backgroundColor: 'rgba(0, 0, 0, 0.1)',
        marginHorizontal: SPACING.md,
    },

    // Vérifications avec animations
    verificationsCard: {
        borderRadius: BORDER_RADIUS.lg,
        overflow: 'hidden',
    },
    verificationHeader: {
        padding: SPACING.lg,
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
    },
    verificationHeaderContent: {
        flexDirection: 'row',
        alignItems: 'center',
        flex: 1,
    },
    verificationHeaderText: {
        flex: 1,
        marginLeft: SPACING.md,
    },
    verificationCount: {
        marginHorizontal: SPACING.md,
    },
    verificationCountText: {
        fontSize: 12,
        fontWeight: 'bold',
    },
    verificationDivider: {
        marginHorizontal: SPACING.lg,
    },
    verificationListContainer: {
        overflow: 'hidden',
    },
    verificationList: {
        paddingHorizontal: SPACING.lg,
        paddingBottom: SPACING.lg,
        gap: SPACING.md,
    },
    verificationItem: {
        flexDirection: 'row',
        alignItems: 'center',
        paddingVertical: SPACING.sm,
    },
    verificationIcon: {
        width: 28,
        marginRight: SPACING.md,
    },
    verificationText: {
        flex: 1,
        fontSize: 15,
    },
    contactButtonContainer: {
        position: 'absolute',
        bottom: 0,
        left: 0,
        right: 0,
        padding: SPACING.lg,
        borderTopWidth: 1,
        borderTopColor: 'rgba(0, 0, 0, 0.1)',
        elevation: ELEVATION.high,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: -2 },
        shadowOpacity: 0.1,
        shadowRadius: 8,
    },
    contactButton: {
        borderRadius: BORDER_RADIUS.lg,
    },
    contactButtonContent: {
        height: 48,
    },
    contactButtonLabel: {
        fontSize: 16,
    },
    errorText: {
        textAlign: 'center',
        marginVertical: SPACING.lg,
    },
    backButton: {
        marginTop: SPACING.lg,
        borderRadius: BORDER_RADIUS.md,
    },
    actionButton: {
        marginTop: SPACING.lg,
        borderRadius: BORDER_RADIUS.md,
    },
    actionButtonContent: {
        height: 48,
    },
    dialog: {
        borderRadius: BORDER_RADIUS.xl,
    },
    dialogScroll: {
        maxHeight: 400,
        backgroundColor: 'transparent',
    },
    scrollAreaContainer: {
        position: 'relative',
    },
    dialogContentBackground: {
        position: 'absolute',
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
    },
    listItem: {
        paddingVertical: SPACING.md,
        backgroundColor: 'transparent',
    },

    // Nouveaux styles pour le dialog animé (style original conservé)
    dialogOverlay: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
        paddingHorizontal: SPACING.lg,
    },
    dialogContainer: {
        width: '100%',
        maxWidth: 400,
        maxHeight: '80%',
        borderRadius: BORDER_RADIUS.xl,
        overflow: 'hidden',
        elevation: ELEVATION.high,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 10 },
        shadowOpacity: 0.25,
        shadowRadius: 20,
    },
    dialogHeader: {
        padding: SPACING.xl,
        paddingBottom: SPACING.md,
    },
    dialogTitle: {
        fontSize: 18,
        fontWeight: '600',
        textAlign: 'center',
    },
    dialogScrollView: {
        maxHeight: 400,
        backgroundColor: 'transparent',
        position: 'relative',
        paddingHorizontal: SPACING.md,
    },
    dialogActions: {
        paddingVertical: SPACING.md,
        paddingHorizontal: SPACING.lg,
        alignItems: 'flex-end',
    },
    dialogDivider: {
        marginLeft: SPACING.lg,
        marginRight: SPACING.lg,
        backgroundColor: 'rgba(255,255,255,.5)'
    },
});