import React from 'react';
import { View, StyleSheet, Alert } from 'react-native';
import { useTheme, Button } from 'react-native-paper';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { useTranslation } from 'react-i18next';

import Text from '@/components/typography/Text';
import { useResendEmailVerificationMutation, useResendPhoneVerificationMutation } from '@/redux/api/userApi';
import { SPACING, BORDER_RADIUS } from '@/constants/spacing';

export const VerificationActions = ({ user, style }) => {
    const { colors } = useTheme();
    const { t } = useTranslation();

    const [resendEmailVerification, { isLoading: isResendingEmail }] = useResendEmailVerificationMutation();
    const [resendPhoneVerification, { isLoading: isResendingPhone }] = useResendPhoneVerificationMutation();

    const handleResendEmail = async () => {
        try {
            await resendEmailVerification().unwrap();
            Alert.alert(
                t('verification.email.title'),
                t('verification.email.sent'),
                [{ text: t('common.ok') }]
            );
        } catch (error) {
            Alert.alert(
                t('common.error'),
                error?.data?.message || t('verification.email.error'),
                [{ text: t('common.ok') }]
            );
        }
    };

    const handleResendPhone = async () => {
        try {
            await resendPhoneVerification().unwrap();
            Alert.alert(
                t('verification.phone.title'),
                t('verification.phone.sent'),
                [{ text: t('common.ok') }]
            );
        } catch (error) {
            Alert.alert(
                t('common.error'),
                error?.data?.message || t('verification.phone.error'),
                [{ text: t('common.ok') }]
            );
        }
    };

    const needsEmailVerification = !user?.isEmailVerified;
    const needsPhoneVerification = !user?.isPhoneVerified;

    if (!needsEmailVerification && !needsPhoneVerification) {
        return null;
    }

    return (
        <View style={[
            styles.container,
            {
                backgroundColor: colors.surfaceVariant,
                borderRadius: BORDER_RADIUS.lg
            },
            style
        ]}>
            <View style={styles.header}>
                <MaterialCommunityIcons
                    name="shield-alert-outline"
                    size={20}
                    color={colors.primary}
                />
                <Text variant="labelLarge" color="textPrimary" style={styles.title}>
                    {t('verification.actions.title')}
                </Text>
            </View>

            <Text variant="bodyMedium" color="textSecondary" style={styles.subtitle}>
                {t('verification.actions.subtitle')}
            </Text>

            <View style={styles.actionsContainer}>
                {needsEmailVerification && (
                    <Button
                        mode="outlined"
                        onPress={handleResendEmail}
                        loading={isResendingEmail}
                        disabled={isResendingEmail || isResendingPhone}
                        icon="email-outline"
                        style={[
                            styles.actionButton,
                            { borderRadius: BORDER_RADIUS.md }
                        ]}
                        contentStyle={styles.buttonContent}
                    >
                        {t('verification.actions.resendEmail')}
                    </Button>
                )}

                {needsPhoneVerification && (
                    <Button
                        mode="outlined"
                        onPress={handleResendPhone}
                        loading={isResendingPhone}
                        disabled={isResendingEmail || isResendingPhone}
                        icon="phone-outline"
                        style={[
                            styles.actionButton,
                            { borderRadius: BORDER_RADIUS.md }
                        ]}
                        contentStyle={styles.buttonContent}
                    >
                        {t('verification.actions.resendPhone')}
                    </Button>
                )}
            </View>
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        padding: SPACING.lg,
        gap: SPACING.md
    },
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.sm
    },
    title: {
        // Typography géré par le composant Text
    },
    subtitle: {
        lineHeight: 20
    },
    actionsContainer: {
        gap: SPACING.sm
    },
    actionButton: {
        // Border radius géré dans le style principal
    },
    buttonContent: {
        paddingVertical: SPACING.xs
    }
});