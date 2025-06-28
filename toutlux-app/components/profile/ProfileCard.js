import React, { useState } from 'react';
import { View, StyleSheet, Image } from 'react-native';
import { Divider, useTheme, ActivityIndicator } from 'react-native-paper';
import { useTranslation } from "react-i18next";

import Text from '@/components/typography/Text';
import { useDocumentUpload } from '@/hooks/useDocumentUpload';
import { StatCard } from './StatCard';
import { SPACING, BORDER_RADIUS, ELEVATION } from '@/constants/spacing';
import {LinearGradient} from "expo-linear-gradient";

export const ProfileCard = ({ user }) => {
    const { colors } = useTheme();
    const { t } = useTranslation();
    const [imageLoading, setImageLoading] = useState(true);
    const [imageError, setImageError] = useState(false);

    const { getImageUrl } = useDocumentUpload();

    const getAvatarInitial = () => {
        if (user?.firstName) {
            return user.firstName.charAt(0).toUpperCase();
        }
        return 'U';
    };

    const getProfileImageUri = () => {
        const profilePicture = user?.profilePicture;
        return profilePicture ? getImageUrl(profilePicture) : null;
    };

    // Score de confiance sur 5 avec logique détaillée
    const getTrustScore = () => {
        if (!user) return { score: 0, maxScore: 5, color: colors.error };

        const validationStatus = user?.validationStatus || {};
        const missingFields = user?.missingFields || [];
        let score = 0;

        // POINT 1: Informations personnelles complètes (1 point)
        const hasPersonalInfo = !missingFields.some(field =>
            ['firstName', 'lastName', 'phoneNumber', 'phoneNumberIndicatif', 'profilePicture'].includes(field)
        );
        if (hasPersonalInfo) score += 1;

        // POINT 2: Email et Téléphone vérifié (1 point)
        // TODO: Remplacer || par && quand téléphone sera vérifié
        const isEmailVerified = validationStatus.email?.isVerified || false;
        const isPhoneVerified = validationStatus.phone?.isVerified || false;
        if (isPhoneVerified || isEmailVerified) score += 1;

        // POINT 3: Documents d'identité vérifiés (1 point)
        const isIdentityVerified = validationStatus.identity?.isVerified || false;
        if (isIdentityVerified) score += 1;

        // POINT 4: Documents financiers vérifiés (1 point)
        const isFinancialVerified = validationStatus.financialDocs?.isVerified || false;
        if (isFinancialVerified) score += 1;

        // POINT 5: Termes acceptés (1 point)
        const termsAccepted = user?.termsAccepted === true;
        const privacyAccepted = user?.privacyAccepted === true;
        if (termsAccepted && privacyAccepted) score += 1;

        const maxScore = 5;

        // Couleurs basées sur le score
        let color = colors.error;
        if (score === 5) color = '#51cf66';        // Vert foncé - 5/5
        else if (score === 4) color = '#69db7c';   // Vert clair - 4/5
        else if (score === 3) color = '#ffd43b';   // Jaune - 3/5
        else if (score === 2) color = '#ffa94d';   // Orange - 2/5
        else if (score === 1) color = '#ff8787';   // Orange-rouge - 1/5

        return { score, maxScore, color };
    };

    const handleImageError = (error) => {
        console.error('❌ Image loading error:', error.nativeEvent.error);
        setImageError(true);
        setImageLoading(false);
    };

    const handleImageLoad = () => {
        setImageLoading(false);
    };

    const trustScore = getTrustScore();
    const profileImageUri = getProfileImageUri();

    const renderAvatar = () => {
        if (profileImageUri && !imageError) {
            return (
                <View>
                    <Image
                        source={{ uri: profileImageUri }}
                        style={styles.avatar}
                        onError={handleImageError}
                        onLoad={handleImageLoad}
                        onLoadStart={() => setImageLoading(true)}
                    />
                    {imageLoading && (
                        <View style={[
                            styles.avatarPlaceholder,
                            styles.loadingOverlay,
                            { backgroundColor: colors.surface }
                        ]}>
                            <ActivityIndicator size="small" color={colors.primary} />
                        </View>
                    )}
                </View>
            );
        }

        return (
            <View style={[styles.avatarPlaceholder, { backgroundColor: colors.primary }]}>
                <Text variant="heroTitle" style={styles.avatarText}>
                    {getAvatarInitial()}
                </Text>
            </View>
        );
    };

    return (
        <LinearGradient
            colors={[colors.surfaceVariant, colors.surface, colors.surfaceVariant]} // Dégradé bleu doux
            start={{ x: 1, y: 1 }}
            end={{ x: 1, y: 0 }}
            style={[styles.userSection, { borderRadius: BORDER_RADIUS.xl }]}
        >
            <View style={[styles.avatarContainer, { borderRightColor: colors.outline }]}>
                {renderAvatar()}
                <Text
                    variant="cardTitle"
                    color="textPrimary"
                    style={styles.userName}
                    numberOfLines={1}
                >
                    {user?.firstName || user?.email?.split('@')[0] || 'User'}
                </Text>
            </View>

            <View style={styles.statsContainer}>
                <StatCard
                    value={user?.profileViews?.toString() || '0'}
                    label={t('profile.metrics.visits')}
                />
                <Divider style={{
                    height: 2,
                    backgroundColor: colors.outline,
                    marginHorizontal: SPACING.sm
                }} />
                <StatCard
                    value={`${trustScore.score}/${trustScore.maxScore}`}
                    label={t('profile.metrics.trust')}
                    valueColor={trustScore.color}
                />
            </View>
        </LinearGradient>
    );
};

const styles = StyleSheet.create({
    userSection: {
        flexDirection: "row",
        marginBottom: SPACING.xl,
        elevation: ELEVATION.high,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 8,
        paddingVertical: SPACING.sm,
        paddingHorizontal: SPACING.lg,
        minHeight: 120,
    },
    avatarContainer: {
        flex: 0.7,
        justifyContent: "center",
        alignItems: "center",
        borderRightWidth: 2,
        paddingRight: SPACING.lg,
    },
    avatar: {
        width: 80,
        height: 80,
        borderRadius: 40,
        marginBottom: SPACING.md,
        backgroundColor: '#f0f0f0',
    },
    avatarPlaceholder: {
        width: 80,
        height: 80,
        borderRadius: 40,
        justifyContent: 'center',
        alignItems: 'center',
        marginBottom: SPACING.md,
    },
    loadingOverlay: {
        position: 'absolute',
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        marginBottom: SPACING.md,
        opacity: 0.9,
    },
    avatarText: {
        color: '#fff',
    },
    userName: {
        textAlign: "center",
        maxWidth: '100%',
    },
    statsContainer: {
        flex: 1,
        paddingLeft: SPACING.lg,
        justifyContent: 'space-around',
    },
});