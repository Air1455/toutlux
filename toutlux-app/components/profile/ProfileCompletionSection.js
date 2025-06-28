// components/profile/ProfileCompletionSection.js
import React from 'react';
import { View, StyleSheet } from 'react-native';
import { useTheme } from 'react-native-paper';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { useDispatch } from 'react-redux';

import Text from '@/components/typography/Text';
import { ProfileMenuItem } from './ProfileMenuItem';
import { authApi } from '@/redux/api/authApi';
import { SPACING, BORDER_RADIUS } from '@/constants/spacing';

export const ProfileCompletionSection = ({ user, onRefresh }) => {
    const { colors } = useTheme();
    const { t } = useTranslation();
    const router = useRouter();
    const dispatch = useDispatch();

    const missingFields = user?.missingFields || [];
    const validationStatus = user?.validationStatus || {};

    // Statut de validation détaillé
    const getStepValidationStatus = (step) => {
        switch (step) {
            case 0: // Informations personnelles + vérifications
                const hasPersonalInfo = !missingFields.some(field =>
                    ['firstName', 'lastName', 'phoneNumber', 'phoneNumberIndicatif', 'profilePicture'].includes(field)
                );
                const emailVerified = validationStatus.email?.isVerified || false;
                const phoneVerified = validationStatus.phone?.isVerified || false;

                let validationStatusValue = 'incomplete';
                if (hasPersonalInfo && emailVerified && phoneVerified) {
                    validationStatusValue = 'verified';
                } else if (hasPersonalInfo) {
                    validationStatusValue = 'pending';
                }

                return {
                    isComplete: validationStatusValue === 'verified',
                    requiresValidation: true,
                    validationStatus: validationStatusValue,
                    validationType: 'personal_and_verification'
                };

            case 1: // Documents d'identité
                const hasIdentityDocs = !missingFields.some(field =>
                    ['identityCardType', 'identityCard', 'selfieWithId'].includes(field)
                );
                const identityVerified = validationStatus.identity?.isVerified || false;

                return {
                    isComplete: identityVerified,
                    requiresValidation: true,
                    validationStatus: identityVerified ? 'verified' : (hasIdentityDocs ? 'pending' : 'incomplete'),
                    validationType: 'identity'
                };

            case 2: // Documents financiers
                const hasFinancialDocs = !missingFields.includes('financialDocs');
                const financialVerified = validationStatus.financialDocs?.isVerified || false;

                return {
                    isComplete: financialVerified,
                    requiresValidation: true,
                    validationStatus: financialVerified ? 'verified' : (hasFinancialDocs ? 'pending' : 'incomplete'),
                    validationType: 'financial'
                };

            case 3: // Termes et conditions
                const termsComplete = user?.termsAccepted === true && user?.privacyAccepted === true;
                return {
                    isComplete: termsComplete,
                    requiresValidation: false,
                    validationStatus: termsComplete ? 'complete' : 'incomplete',
                    validationType: 'terms'
                };

            default:
                return {
                    isComplete: false,
                    requiresValidation: false,
                    validationStatus: 'incomplete'
                };
        }
    };

    // Gestionnaire pour l'écran de vérifications
    const handleVerificationsPress = async () => {
        try {
            dispatch(authApi.util.invalidateTags([{ type: 'User', id: 'CURRENT' }]));

            if (onRefresh) {
                await onRefresh();
            }

            router.push('/screens/verifications_status');
        } catch (error) {
            console.error('❌ Error refreshing verifications:', error);
            router.push('/screens/verifications_status');
        }
    };

    const completionItems = [
        {
            icon: 'account-check',
            title: t('profile.completion.personalInfo.title'),
            onPress: () => router.push('/screens/on_boarding?step=0&fromCompletion=true'),
            ...getStepValidationStatus(0)
        },
        {
            icon: 'card-account-details',
            title: t('profile.completion.identityDocs.title'),
            onPress: () => router.push('/screens/on_boarding?step=1&fromCompletion=true'),
            ...getStepValidationStatus(1)
        },
        {
            icon: 'file-document-outline',
            title: t('profile.completion.financialDocs.title'),
            onPress: () => router.push('/screens/on_boarding?step=2&fromCompletion=true'),
            ...getStepValidationStatus(2)
        },
        {
            icon: 'file-check',
            title: t('profile.completion.terms.title'),
            onPress: () => router.push('/screens/on_boarding?step=3&fromCompletion=true'),
            ...getStepValidationStatus(3)
        },
    ];

    // Calcul du pourcentage basé sur les étapes complétées
    const completedCount = completionItems.filter(item => item.isComplete).length;
    const realCompletionPercentage = Math.round((completedCount / completionItems.length) * 100);

    // Fonctions d'affichage des statuts
    const getStatusIcon = (item) => {
        if (item.isComplete) {
            return 'check-circle';
        }

        if (item.requiresValidation) {
            switch (item.validationStatus) {
                case 'verified':
                    return 'check-circle';
                case 'pending':
                    return 'clock-outline';
                case 'incomplete':
                    return 'alert-circle-outline';
                default:
                    return 'alert-circle-outline';
            }
        }

        return item.validationStatus === 'complete' ? 'check-circle' : 'alert-circle-outline';
    };

    const getStatusColor = (item) => {
        if (item.isComplete) {
            return colors.primary;
        }

        if (item.requiresValidation) {
            switch (item.validationStatus) {
                case 'verified':
                    return colors.primary;
                case 'pending':
                    return colors.outline;
                case 'incomplete':
                    return colors.error;
                default:
                    return colors.error;
            }
        }

        return item.validationStatus === 'complete' ? colors.primary : colors.error;
    };

    return (
        <View style={styles.completionSection}>
            <View style={styles.sectionHeader}>
                <Text variant="cardTitle" color="textPrimary" style={styles.sectionTitle}>
                    {t('profile.completion.title')}
                </Text>
                <View style={styles.progressContainer}>
                    <Text variant="priceCard" color="primary" style={styles.percentageText}>
                        {realCompletionPercentage}%
                    </Text>
                </View>
            </View>

            <View style={[
                styles.progressBarContainer,
                {
                    backgroundColor: colors.surfaceVariant,
                    borderRadius: BORDER_RADIUS.sm
                }
            ]}>
                <View style={[
                    styles.progressBarFill,
                    {
                        backgroundColor: colors.primary,
                        width: `${realCompletionPercentage}%`,
                        borderRadius: BORDER_RADIUS.sm
                    }
                ]} />
            </View>

            <View style={styles.itemsContainer}>
                {completionItems.map((item, index) => (
                    <View key={index} style={styles.completionItem}>
                        <ProfileMenuItem
                            icon={item.icon}
                            title={item.title}
                            onPress={item.onPress}
                            statusIcon={getStatusIcon(item)}
                            statusColor={getStatusColor(item)}
                            showStatusIcon={true}
                        />
                    </View>
                ))}

                <View style={styles.completionItem}>
                    <ProfileMenuItem
                        icon="shield-check"
                        title={t('profile.completion.verifications.title')}
                        onPress={handleVerificationsPress}
                        statusIcon="chevron-right"
                        statusColor={colors.outline}
                        showStatusIcon={true}
                    />
                </View>
            </View>
        </View>
    );
};

const styles = StyleSheet.create({
    completionSection: {
        marginVertical: SPACING.sm,
    },
    sectionHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: SPACING.md,
        paddingHorizontal: SPACING.sm,
    },
    sectionTitle: {
        // Typography géré par le composant Text
    },
    progressContainer: {
        alignItems: 'flex-end',
    },
    percentageText: {
        // Typography géré par le composant Text
    },
    progressBarContainer: {
        height: 6,
        marginHorizontal: SPACING.sm,
        marginBottom: SPACING.lg,
        overflow: 'hidden',
    },
    progressBarFill: {
        height: '100%',
        minWidth: 6,
    },
    itemsContainer: {
        gap: SPACING.xs,
    },
    completionItem: {
        gap: SPACING.sm,
    },
});