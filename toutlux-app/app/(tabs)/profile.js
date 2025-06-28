import React, { useState, useCallback } from 'react';
import { View, StyleSheet, ScrollView, RefreshControl, Platform } from 'react-native';
import { useTheme, ActivityIndicator } from 'react-native-paper';
import { useDispatch } from 'react-redux';
import { useTranslation } from 'react-i18next';
import { LinearGradient } from 'expo-linear-gradient';

import { useCurrentUser } from '@/hooks/useIsCurrentUser';
import { logout } from "@/redux/authSlice";
import { authApi } from '@/redux/api/authApi';

import { WelcomeScreen } from '@/components/profile/WelcomeScreen';
import { ProfileCard } from '@/components/profile/ProfileCard';
import { ProfileCompletionSection } from '@/components/profile/ProfileCompletionSection';
import MyListing from "@components/listing/MyListing";
import { ProfileMenuItem } from "@components/profile/ProfileMenuItem";
import { SafeScreen } from "@components/layout/SafeScreen";
import Text from '@/components/typography/Text';
import { SPACING } from '@/constants/spacing';
import {LoadingScreen} from "@components/Loading";

export default function ProfileScreen() {
    const { colors } = useTheme();
    const { t } = useTranslation();
    const dispatch = useDispatch();

    const { user, isLoading, isAuthenticated, error, refetch } = useCurrentUser();

    // État pour le pull-to-refresh
    const [refreshing, setRefreshing] = useState(false);

    // Fonction de refresh avec invalidation du cache
    const onRefresh = useCallback(async () => {
        setRefreshing(true);
        try {
            // Invalider le cache RTK Query
            dispatch(authApi.util.invalidateTags([{ type: 'User', id: 'CURRENT' }]));
            await refetch().unwrap();

        } catch (error) {
            console.error('❌ Erreur lors du refresh:', error);
        } finally {
            setRefreshing(false);
        }
    }, [refetch, dispatch]);

    if (!isAuthenticated || error) {
        return <WelcomeScreen />;
    }

    if (isLoading) {
        return (
            <SafeScreen>
                <LoadingScreen />
            </SafeScreen>
        );
    }

    // Configuration du RefreshControl en fonction de la plateforme
    const refreshControlProps = {
        refreshing,
        onRefresh,
        colors: [colors.primary], // Android
        tintColor: colors.primary, // iOS
        progressBackgroundColor: colors.surface, // Android
        ...(Platform.OS === 'ios' && {
            title: t('common.pullToRefresh'),
            titleColor: colors.textPrimary,
        }),
    };

    return (
        <SafeScreen>
            <LinearGradient colors={[colors.background, colors.surface]} style={styles.container}>
                <ScrollView
                    showsVerticalScrollIndicator={false}
                    contentContainerStyle={styles.scrollContent}
                    refreshControl={<RefreshControl {...refreshControlProps} />}
                >
                    <View style={styles.header}>
                        <Text variant="pageTitle" color="textPrimary">
                            {t('profile.title')}
                        </Text>
                    </View>

                    <View style={styles.profileCard}>
                        <ProfileCard user={user} />
                        <ProfileCompletionSection user={user} onRefresh={onRefresh} />
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
        paddingBottom: SPACING.xl,
        minHeight: '100%',
    },
    centered: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
    },
    header: {
        paddingHorizontal: SPACING.xl,
        paddingVertical: SPACING.lg,
    },
    profileCard: {
        marginHorizontal: SPACING.lg,
    },
    loadingText: {
        marginTop: SPACING.lg,
    },
});