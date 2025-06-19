import React from 'react';
import { View, StyleSheet, Image } from 'react-native';
import { Divider, Text, useTheme, ActivityIndicator } from 'react-native-paper';
import { useTranslation } from "react-i18next";

import { useUserPermissions } from '@/hooks/useIsCurrentUser';
import { StatCard } from './StatCard';

export const ProfileCard = ({ user }) => {
    const { colors } = useTheme();
    const { t } = useTranslation();
    const [imageLoading, setImageLoading] = React.useState(true);
    const [imageError, setImageError] = React.useState(false);

    const {
        isEmailVerified,
        isPhoneVerified,
        isIdentityVerified,
        completionPercentage
    } = useUserPermissions();

    const getAvatarInitial = () => {
        if (user?.firstName) {
            return user.firstName.charAt(0).toUpperCase();
        }
        return 'U';
    };

    // ✅ CORRECTION: Fonction corrigée pour construire l'URL de l'image
    const getProfileImageUri = () => {
        const profilePicture = user?.profilePicture;

        if (!profilePicture) return null;

        // Si c'est déjà une URL complète (Google, etc.)
        if (profilePicture.startsWith('http://') || profilePicture.startsWith('https://')) {
            return profilePicture;
        }

        // ✅ CORRECTION: Le backend retourne déjà le chemin complet comme "/uploads/profiles/image.jpg"
        // On doit juste ajouter l'URL de base, pas "/uploads" en plus
        const baseUrl = process.env.EXPO_PUBLIC_API_URL;

        // Supprimer le slash final de l'URL de base si présent
        const cleanBaseUrl = baseUrl.endsWith('/') ? baseUrl.slice(0, -1) : baseUrl;

        // Ajouter un slash au début du chemin si absent
        const cleanPath = profilePicture.startsWith('/') ? profilePicture : `/${profilePicture}`;

        return `${cleanBaseUrl}${cleanPath}`;
    };

    const getTrustScore = () => {
        if (!user) return { score: 0, color: colors.error };

        let score = 0;

        // Informations personnelles complètes
        if (user.firstName && user.lastName && user.email && user.phoneNumber) {
            score += 1;
        }

        // Photo de profil
        if (user.profilePicture && user.profilePicture !== 'yes') {
            score += 1;
        }

        // Documents d'identité
        if (user.identityCard && user.selfieWithId) {
            score += 1;
        }

        // Justificatifs financiers
        if (user.incomeProof || user.ownershipProof) {
            score += 1;
        }

        // Vérifications
        const verificationCount = [isEmailVerified, isPhoneVerified, isIdentityVerified].filter(Boolean).length;
        if (verificationCount >= 2) {
            score += 1;
        }

        // Couleurs selon le score
        let color = colors.error;
        if (score >= 5) color = '#51cf66';
        else if (score >= 4) color = '#69db7c';
        else if (score >= 3) color = '#ffd43b';
        else if (score >= 2) color = '#ffa94d';

        return { score, color };
    };

    const handleImageError = (error) => {
        console.error('❌ Image loading error:', error.nativeEvent.error);
        console.error('❌ Failed URL:', getProfileImageUri());
        setImageError(true);
        setImageLoading(false);
    };

    const handleImageLoad = () => {
        console.log('✅ Image loaded successfully');
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
                        <View style={[styles.avatarPlaceholder, styles.loadingOverlay, { backgroundColor: colors.surface }]}>
                            <ActivityIndicator size="small" color={colors.primary} />
                        </View>
                    )}
                </View>
            );
        }

        return (
            <View style={[styles.avatarPlaceholder, { backgroundColor: colors.primary }]}>
                <Text style={styles.avatarText}>{getAvatarInitial()}</Text>
            </View>
        );
    };

    return (
        <View style={[styles.userSection, { backgroundColor: colors.surface }]}>
            <View style={[styles.avatarContainer, { borderRightColor: colors.outline }]}>
                {renderAvatar()}
                <Text style={[styles.userName, { color: colors.onSurface }]} numberOfLines={1}>
                    {user?.firstName || user?.email?.split('@')[0] || 'User'}
                </Text>
            </View>

            <View style={styles.statsContainer}>
                <StatCard
                    value={user?.profileViews?.toString() || '0'}
                    label={t('profile.metrics.visits')}
                />
                <Divider style={{ height: 2, backgroundColor: colors.outline }} />
                <StatCard
                    value={`${trustScore.score}/5`}
                    label={t('profile.metrics.trust')}
                    valueColor={trustScore.color}
                />
            </View>
        </View>
    );
};

const styles = StyleSheet.create({
    userSection: {
        flexDirection: "row",
        marginBottom: 30,
        borderRadius: 20,
        elevation: 4,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 8,
        paddingVertical: 10,
        paddingHorizontal: 20,
        minHeight: 120,
    },
    avatarContainer: {
        flex: 0.7,
        justifyContent: "center",
        alignItems: "center",
        borderRightWidth: 2,
        paddingRight: 16,
    },
    avatar: {
        width: 80,
        height: 80,
        borderRadius: 40,
        marginBottom: 12,
        backgroundColor: '#f0f0f0',
    },
    avatarPlaceholder: {
        width: 80,
        height: 80,
        borderRadius: 40,
        justifyContent: 'center',
        alignItems: 'center',
        marginBottom: 12,
    },
    loadingOverlay: {
        position: 'absolute',
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        marginBottom: 12,
        opacity: 0.9,
    },
    avatarText: {
        fontSize: 32,
        fontWeight: 'bold',
        color: '#fff',
    },
    userName: {
        fontSize: 16,
        fontWeight: 'bold',
        fontFamily: 'Prompt_800ExtraBold',
        textAlign: "center",
        maxWidth: '100%',
    },
    statsContainer: {
        flex: 1,
        paddingLeft: 16,
        justifyContent: 'space-around',
    },
});