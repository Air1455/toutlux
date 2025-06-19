import React from 'react';
import { View, StyleSheet, Image, TouchableOpacity } from 'react-native';
import { Text, useTheme } from 'react-native-paper';
import { LinearGradient } from 'expo-linear-gradient';
import { useTranslation } from 'react-i18next';
import { useRouter } from 'expo-router';
import {SafeScreen} from "@components/layout/SafeScreen";

export const WelcomeScreen = () => {
    const { colors } = useTheme();
    const { t } = useTranslation();
    const router = useRouter();

    return (
        <SafeScreen>
            <LinearGradient
                colors={[colors.background, colors.surface]}
                style={styles.container}
            >
                <View style={styles.welcomeContent}>
                    <View style={styles.welcomeImageContainer}>
                        <Image
                            source={require('@/assets/images/welcome-profile.png')}
                            style={styles.welcomeImage}
                            resizeMode="cover"
                        />
                    </View>

                    <View style={styles.welcomeTextContainer}>
                        <Text variant="headlineMedium" style={[styles.welcomeTitle, { color: colors.text }]}>
                            {t('profile.welcome')}
                        </Text>
                        <Text variant="bodyLarge" style={[styles.welcomeSubtitle, { color: colors.text }]}>
                            {t('profile.createToAccess')}
                        </Text>
                    </View>

                    <View style={styles.buttonGroup}>
                        <TouchableOpacity
                            style={[styles.actionButton, { backgroundColor: colors.primary, elevation: 4 }]}
                            onPress={() => router.push({ pathname: '/screens/login', params: { signup: true } })}
                        >
                            <Text style={styles.actionButtonText}>
                                {t('profile.createProfile')}
                            </Text>
                        </TouchableOpacity>

                        <TouchableOpacity
                            style={[styles.actionButton, {
                                backgroundColor: `${colors.onSurfaceVariant}30`,
                                borderWidth: 2,
                                borderColor: colors.onSurfaceVariant,
                            }]}
                            onPress={() => router.push('/screens/login')}
                        >
                            <Text style={[styles.actionButtonText, { color: colors.surface }]}>
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
        justifyContent: 'space-between',
        alignItems: 'center',
        padding: 24,
        paddingBottom: 32
    },
    welcomeImageContainer: {
        flex: .9,
        width: "100%",
        justifyContent: 'flex-end',
        alignItems: 'center',
    },
    welcomeImage: {
        width: "100%",
        height: 250,
        borderRadius: 50,
        backgroundColor: 'rgba(255,255,255,0.1)',
    },
    welcomeTextContainer: {
        marginTop: 40,
        marginBottom: 20,
        alignItems: 'center',
    },
    welcomeTitle: {
        fontSize: 28,
        fontWeight: 'bold',
        color: '#fff',
        textAlign: 'center',
        marginBottom: 12,
        textShadowColor: 'rgba(0,0,0,0.3)',
        textShadowOffset: { width: 0, height: 2 },
        textShadowRadius: 4,
    },
    welcomeSubtitle: {
        fontSize: 16,
        color: 'rgba(255,255,255,0.9)',
        textAlign: 'center',
        lineHeight: 24,
    },
    buttonGroup: {
        width: '100%',
        gap: 16,
    },
    actionButton: {
        paddingVertical: 16,
        paddingHorizontal: 32,
        borderRadius: 25,
        shadowColor: '#000',
        shadowOpacity: 0.3,
        shadowRadius: 10,
        shadowOffset: { width: 0, height: 5 },
        alignItems: 'center',
    },
    actionButtonText: {
        color: '#fff',
        fontWeight: 'bold',
        fontSize: 16,
        textAlign: 'center',
    },
});