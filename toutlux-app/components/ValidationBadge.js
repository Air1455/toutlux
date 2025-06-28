import React from 'react';
import { View, StyleSheet } from 'react-native';
import { useTheme, Chip } from 'react-native-paper';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { useTranslation } from 'react-i18next';

import Text from '@/components/typography/Text';
import { transparentColors, validationColors } from '@/utils/colorUtils';
import { SPACING } from '@/constants/spacing';

export const ValidationBadge = ({
                                    isVerified,
                                    type = 'email', // 'email', 'phone', 'identity', 'financial'
                                    size = 'small', // 'small', 'medium'
                                    style
                                }) => {
    const { colors } = useTheme();
    const { t } = useTranslation();

    const getValidationConfig = () => {
        const configs = {
            email: {
                verifiedText: t('validation.verified'),
                pendingText: t('validation.pending'),
                icon: 'email-check',
                pendingIcon: 'email-alert'
            },
            phone: {
                verifiedText: t('validation.verified'),
                pendingText: t('validation.pending'),
                icon: 'phone-check',
                pendingIcon: 'phone-alert'
            },
            identity: {
                verifiedText: t('validation.verified'),
                pendingText: t('validation.pending'),
                icon: 'shield-check',
                pendingIcon: 'shield-alert'
            },
            financial: {
                verifiedText: t('validation.verified'),
                pendingText: t('validation.pending'),
                icon: 'file-check',
                pendingIcon: 'file-alert'
            }
        };
        return configs[type] || configs.email;
    };

    const config = getValidationConfig();
    const isSmall = size === 'small';

    if (isVerified) {
        return (
            <Chip
                icon={config.icon}
                mode="flat"
                compact={isSmall}
                style={style}
                textStyle={{
                    color: validationColors.verified.text,
                    fontSize: isSmall ? 11 : 12,
                    fontWeight: '600'
                }}
            >
                {config.verifiedText}
            </Chip>
        );
    }

    return (
        <Chip
            icon={config.pendingIcon}
            mode="outlined"
            compact={isSmall}
            style={style}
            textStyle={{
                color: validationColors.pending.text,
                fontSize: isSmall ? 11 : 12,
                fontWeight: '600'
            }}
        >
            {config.pendingText}
        </Chip>
    );
};

export const ValidationStatus = ({
                                     isVerified,
                                     type = 'email',
                                     showText = true,
                                     iconOnly = false
                                 }) => {
    const { colors } = useTheme();
    const { t } = useTranslation();

    const getValidationConfig = () => {
        const configs = {
            email: {
                verifiedText: t('validation.email.verified'),
                pendingText: t('validation.email.pending'),
                icon: 'check-circle',
                pendingIcon: 'clock-outline'
            },
            phone: {
                verifiedText: t('validation.phoneNumber.verified'),
                pendingText: t('validation.phoneNumber.pending'),
                icon: 'check-circle',
                pendingIcon: 'clock-outline'
            },
            identity: {
                verifiedText: t('validation.identityCard.verified'),
                pendingText: t('validation.identityCard.pending'),
                icon: 'check-circle',
                pendingIcon: 'clock-outline'
            },
            financial: {
                verifiedText: t('validation.financial.verified'),
                pendingText: t('validation.financial.pending'),
                icon: 'check-circle',
                pendingIcon: 'clock-outline'
            }
        };
        return configs[type] || configs.email;
    };

    const config = getValidationConfig();
    const statusColor = isVerified
        ? validationColors.verified.text
        : validationColors.pending.text;
    const statusText = isVerified ? config.verifiedText : config.pendingText;
    const statusIcon = isVerified ? config.icon : config.pendingIcon;

    if (iconOnly) {
        return (
            <MaterialCommunityIcons
                name={statusIcon}
                size={20}
                color={statusColor}
            />
        );
    }

    return (
        <View style={styles.statusContainer}>
            <MaterialCommunityIcons
                name={statusIcon}
                size={16}
                color={statusColor}
            />
            {showText && (
                <Text
                    variant="labelMedium"
                    style={[styles.statusText, { color: statusColor }]}
                >
                    {statusText}
                </Text>
            )}
        </View>
    );
};

const styles = StyleSheet.create({
    statusContainer: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.xs,
    },
    statusText: {
        // Typography géré par le composant Text
    },
});