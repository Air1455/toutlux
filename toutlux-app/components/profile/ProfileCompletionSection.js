import React from 'react';
import { View, StyleSheet } from 'react-native';
import { Text, useTheme } from 'react-native-paper';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';

import { useUserPermissions } from '@/hooks/useIsCurrentUser';
import { ProfileMenuItem } from './ProfileMenuItem';

export const ProfileCompletionSection = ({ user }) => {
    const { colors } = useTheme();
    const { t } = useTranslation();
    const router = useRouter();

    // ✅ HOOK POUR DONNÉES DE COMPLETION
    const {
        completionPercentage,
        missingFields,
        isEmailVerified,
        isPhoneVerified,
        isIdentityVerified
    } = useUserPermissions();

    // ✅ LOGIQUE SIMPLIFIÉE basée sur missingFields
    const getStepCompletion = (step) => {
        switch (step) {
            case 0: // Personal info
                return !missingFields.some(field =>
                    ['firstName', 'lastName', 'phoneNumber', 'profilePicture'].includes(field)
                );
            case 1: // Identity docs
                return !missingFields.some(field =>
                    ['identityCardType', 'identityCard', 'selfieWithId'].includes(field)
                );
            case 2: // Financial docs
                return !missingFields.includes('financialDocs');
            case 3: // Terms
                return !missingFields.some(field =>
                    ['termsAccepted', 'privacyAccepted'].includes(field)
                );
            default:
                return false;
        }
    };

    const completionItems = [
        {
            icon: 'account',
            title: t('profile.completion.personalInfo.title'),
            onPress: () => router.push('/screens/on_boarding?step=0&fromCompletion=true'),
            isComplete: getStepCompletion(0),
        },
        {
            icon: 'file-document',
            title: t('profile.completion.identityDocs.title'),
            onPress: () => router.push('/screens/on_boarding?step=1&fromCompletion=true'),
            isComplete: getStepCompletion(1),
        },
        {
            icon: 'bank',
            title: t('profile.completion.financialDocs.title'),
            onPress: () => router.push('/screens/on_boarding?step=2&fromCompletion=true'),
            isComplete: getStepCompletion(2),
        },
        {
            icon: 'file-check',
            title: t('profile.completion.terms.title'),
            onPress: () => router.push('/screens/on_boarding?step=3&fromCompletion=true'),
            isComplete: getStepCompletion(3),
        },
    ];

    const completedCount = completionItems.filter(item => item.isComplete).length;

    return (
        <View style={styles.completionSection}>
            <View style={styles.sectionHeader}>
                <Text style={[styles.sectionTitle, { color: colors.onSurface }]}>
                    {t('profile.completion.title')}
                </Text>
                <View style={styles.progressContainer}>
                    <Text style={[styles.progressText, { color: colors.onSurfaceVariant }]}>
                        {completedCount}/4
                    </Text>
                    <Text style={[styles.percentageText, { color: colors.primary }]}>
                        {completionPercentage}%
                    </Text>
                </View>
            </View>

            <View style={[styles.progressBarContainer, { backgroundColor: colors.surfaceVariant }]}>
                <View style={[styles.progressBarFill, {
                    backgroundColor: colors.primary,
                    width: `${completionPercentage}%`
                }]} />
            </View>

            <View style={{ gap: 6 }}>
                {completionItems.map((item, index) => (
                    <ProfileMenuItem
                        key={index}
                        icon={item.icon}
                        title={item.title}
                        onPress={item.onPress}
                        statusIcon={item.isComplete ? 'check-circle' : 'clock-outline'}
                        statusColor={item.isComplete ? colors.primary : colors.outline}
                        showStatusIcon={true}
                    />
                ))}
            </View>
        </View>
    );
};

const styles = StyleSheet.create({
    completionSection: {
        marginVertical: 10,
    },
    sectionHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: 12,
        paddingHorizontal: 10
    },
    sectionTitle: {
        fontSize: 18,
        fontWeight: 'bold',
        fontFamily: 'Prompt_800ExtraBold',
    },
    progressContainer: {
        alignItems: 'flex-end',
    },
    progressText: {
        fontSize: 12,
        fontFamily: 'Prompt_400Regular',
    },
    percentageText: {
        fontSize: 16,
        fontWeight: 'bold',
        fontFamily: 'Prompt_700Bold',
    },
    progressBarContainer: {
        height: 6,
        borderRadius: 3,
        marginHorizontal: 10,
        marginBottom: 16,
        overflow: 'hidden',
    },
    progressBarFill: {
        height: '100%',
        borderRadius: 3,
        minWidth: 6,
    },
});