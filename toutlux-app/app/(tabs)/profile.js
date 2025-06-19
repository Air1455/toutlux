import React from 'react';
import { View, StyleSheet, ScrollView } from 'react-native';
import { Text, useTheme, ActivityIndicator } from 'react-native-paper';
import { useDispatch } from 'react-redux';
import { useTranslation } from 'react-i18next';
import { LinearGradient } from 'expo-linear-gradient';

import { useCurrentUser } from '@/hooks/useIsCurrentUser';
import { logout } from "@/redux/authSlice";

import { WelcomeScreen } from '@/components/profile/WelcomeScreen';
import { ProfileCard } from '@/components/profile/ProfileCard';
import { ProfileCompletionSection } from '@/components/profile/ProfileCompletionSection';
import MyListing from "@components/profile/MyListing";
import { ProfileMenuItem } from "@components/profile/ProfileMenuItem";
import { SafeScreen } from "@components/layout/SafeScreen";

export default function ProfileScreen() {
    const { colors } = useTheme();
    const { t } = useTranslation();
    const dispatch = useDispatch();

    // ✅ HOOK SIMPLIFIÉ
    const { user, isLoading, isAuthenticated, error } = useCurrentUser();

    // Écran de bienvenue pour utilisateurs non connectés
    if (!isAuthenticated || error) {
        return <WelcomeScreen />;
    }

    // Écran de chargement
    if (isLoading) {
        return (
            <SafeScreen>
                <LinearGradient colors={[colors.background, colors.surface]} style={[styles.container, styles.centered]}>
                    <ActivityIndicator size="large" color={colors.primary} />
                    <Text variant="bodyLarge" style={{ color: colors.onBackground, marginTop: 16 }}>
                        {t('loading')}
                    </Text>
                </LinearGradient>
            </SafeScreen>
        );
    }

    return (
        <SafeScreen>
            <LinearGradient colors={[colors.background, colors.surface]} style={styles.container}>
                <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.scrollContent}>
                    <View style={styles.header}>
                        <Text style={[styles.headerTitle, { color: colors.onBackground }]}>
                            Profile
                        </Text>
                    </View>

                    <View style={styles.profileCard}>
                        <ProfileCard user={user} />
                        <ProfileCompletionSection user={user} />
                        <MyListing user={user}/>
                        <ProfileMenuItem
                            icon="logout"
                            title={t('profile.logout')}
                            onPress={() => dispatch(logout())}
                            isLogout={true}
                        />
                    </View>
                </ScrollView>
            </LinearGradient>
        </SafeScreen>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    scrollContent: {
        paddingBottom: 20,
    },
    centered: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
    },
    header: {
        paddingHorizontal: 20,
        paddingVertical: 16,
    },
    headerTitle: {
        fontSize: 24,
        fontWeight: 'bold',
        fontFamily: 'Prompt_800ExtraBold',
    },
    profileCard: {
        marginHorizontal: 16,
    },
});