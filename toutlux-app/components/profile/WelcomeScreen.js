import React from 'react';
import { View, StyleSheet, Image, TouchableOpacity } from 'react-native';
import { useTheme } from 'react-native-paper';
import { LinearGradient } from 'expo-linear-gradient';
import { useTranslation } from 'react-i18next';
import { useRouter } from 'expo-router';

import Text from '@/components/typography/Text';
import { SafeScreen } from "@components/layout/SafeScreen";
import { SPACING, BORDER_RADIUS, ELEVATION } from '@/constants/spacing';

export const WelcomeScreen = () => {
    const { colors } = useTheme();
    const { t } = useTranslation();
    const router = useRouter();

    return (
        <SafeScreen edges={['top', 'left', 'right']}>
            <LinearGradient
                colors={[colors.background, colors.surface]}
                style={styles.container}
            >
                <View style={styles.welcomeContent}>
                    {/* Image en haut */}
                    <View style={styles.welcomeImageContainer}>
                        <Image
                            source={require('@/assets/images/welcome-profile.png')}
                            style={styles.welcomeImage}
                            resizeMode="cover"
                        />
                    </View>
                    {/* Texte au centre */}
                    <View style={styles.welcomeTextContainer}>
                        <Text
                            variant="heroTitle"
                            color="textPrimary"
                            style={styles.welcomeTitle}
                        >
                            {t('profile.welcome')}
                        </Text>
                        <Text
                            variant="bodyLarge"
                            color="textSecondary"
                            style={styles.welcomeSubtitle}
                        >
                            {t('profile.createToAccess')}
                        </Text>
                    </View>
                    {/* Boutons en bas avec marge pour tab bar */}
                    <View style={styles.buttonGroup}>
                        <TouchableOpacity
                            style={[
                                styles.actionButton,
                                {
                                    backgroundColor: colors.primary,
                                    borderRadius: BORDER_RADIUS.xl
                                }
                            ]}
                            onPress={() => router.push({ pathname: '/screens/login', params: { signup: true } })}
                        >
                            <Text variant="buttonLarge" style={styles.actionButtonText}>
                                {t('profile.createProfile')}
                            </Text>
                        </TouchableOpacity>
                        <TouchableOpacity
                            style={[
                                styles.actionButton,
                                {
                                    backgroundColor: `${colors.textSecondary}30`,
                                    borderColor: colors.textSecondary,
                                    borderRadius: BORDER_RADIUS.xl
                                }
                            ]}
                            onPress={() => router.push('/screens/login')}
                        >
                            <Text
                                variant="buttonLarge"
                                color="textPrimary"
                                style={styles.actionButtonText}
                            >
                                {t('profile.login')}
                            </Text>
                        </TouchableOpacity>
                    </View>
                </View>
            </LinearGradient>
        </SafeScreen>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    welcomeContent: {
        flex: 1,
        padding: SPACING.xl,
    },
    welcomeImageContainer: {
        justifyContent: 'flex-end',
        alignItems: 'center',
        paddingVertical: SPACING.xl,
        flex: .8
    },
    welcomeImage: {
        width: "100%",
        height: 220,
        backgroundColor: 'rgba(255,255,255,0.1)',
        borderRadius: BORDER_RADIUS.xxl
    },
    welcomeTextContainer: {
        alignItems: 'center',
        paddingVertical: SPACING.xl,
    },
    welcomeTitle: {
        textAlign: 'center',
        marginBottom: SPACING.md,
        textShadowColor: 'rgba(0,0,0,0.3)',
        textShadowOffset: { width: 0, height: 2 },
        textShadowRadius: 4,
    },
    welcomeSubtitle: {
        textAlign: 'center',
        lineHeight: 24,
        maxWidth: 300,
    },
    buttonGroup: {
        width: '100%',
        gap: SPACING.lg,
    },
    actionButton: {
        paddingVertical: SPACING.lg,
        paddingHorizontal: SPACING.xxxl,
        shadowColor: '#000',
        shadowOpacity: 0.3,
        shadowRadius: 10,
        shadowOffset: { width: 0, height: 5 },
        alignItems: 'center',
        elevation: ELEVATION.high,
        borderWidth: 2,
    },
    actionButtonText: {
        color: '#fff',
        textAlign: 'center',
    },
});