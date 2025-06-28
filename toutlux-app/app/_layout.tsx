import '../i18n';
import React, {useEffect} from 'react';
import {StyleSheet, useColorScheme, View} from 'react-native';
import { PaperProvider } from 'react-native-paper';
import { useFonts } from 'expo-font';
import { Stack } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';

import {Provider as StoreProvider, useDispatch, useSelector} from 'react-redux';
import { PersistGate } from 'redux-persist/integration/react';
import { Toasts } from '@backpackapp-io/react-native-toast';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { SafeAreaProvider, initialWindowMetrics } from 'react-native-safe-area-context';

// Import du NetworkProvider
import { NetworkProvider, NetworkIndicator } from '@/providers/NetworkProvider';

// Import de toutes les polices professionnelles
import {
    Prompt_400Regular,
    Prompt_500Medium,
    Prompt_600SemiBold,
    Prompt_700Bold,
    Prompt_800ExtraBold
} from '@expo-google-fonts/prompt';

import {
    Inter_300Light,
    Inter_400Regular,
    Inter_500Medium,
    Inter_600SemiBold,
    Inter_700Bold,
    Inter_800ExtraBold,
} from '@expo-google-fonts/inter';

import {
    Poppins_300Light,
    Poppins_400Regular,
    Poppins_500Medium,
    Poppins_600SemiBold,
    Poppins_700Bold,
    Poppins_800ExtraBold,
} from '@expo-google-fonts/poppins';

import {
    SourceSansPro_300Light,
    SourceSansPro_400Regular,
    SourceSansPro_600SemiBold,
    SourceSansPro_700Bold,
} from '@expo-google-fonts/source-sans-pro';

import { store, persistor } from '@/redux/store';
import useAppTheme from '@/hooks/useAppTheme';
import {setDarkMode} from "@/redux/themeReducer";
import { Platform } from 'react-native';

SplashScreen.preventAutoHideAsync();

function AppContent() {
    const colorScheme = useColorScheme();
    const dispatch = useDispatch();
    const isDarkMode = useSelector((state) => state.theme.isDarkMode);
    const { theme } = useAppTheme();

    // 🎯 SOLUTION: Configuration globale du header avec le thème
    const getBaseHeaderOptions = (title) => ({
        headerShown: true,
        headerBackVisible: true,
        ...(title && { title }),
        // Configuration immédiate avec le thème Paper
        headerStyle: {
            backgroundColor: theme.colors.background, // Utilise directement le thème
            ...Platform.select({
                ios: {
                    shadowOpacity: 0.1,
                    shadowRadius: 4,
                    shadowOffset: { width: 0, height: 2 },
                },
                android: {
                    elevation: 4,
                },
            }),
        },
        headerTintColor: theme.colors.onBackground,
        headerTitleStyle: {
            fontFamily: 'Prompt_800ExtraBold',
            fontSize: 20,
            fontWeight: 'bold',
            color: theme.colors.onBackground,
        },
        headerBackTitleVisible: false,
        headerTitleAlign: Platform.OS === 'android' ? 'left' : 'center',
        // 🔧 Clé pour éviter le flash
        animation: 'none',
    });

    useEffect(() => {
        if (isDarkMode === null) {
            const systemIsDark = colorScheme === 'dark';
            dispatch(setDarkMode(systemIsDark));
        }
    }, [isDarkMode, colorScheme, dispatch]);

    return (
        <PaperProvider theme={theme}>
            <NetworkProvider>
                <View style={styles.container}>
                    <NetworkIndicator />
                    <Stack
                        // 🎯 SOLUTION: Options par défaut globales
                        screenOptions={{
                            headerShown: true,
                            headerBackVisible: true,
                            headerStyle: {
                                backgroundColor: theme.colors.surface,
                                ...Platform.select({
                                    ios: {
                                        shadowOpacity: 0.1,
                                        shadowRadius: 4,
                                        shadowOffset: { width: 0, height: 2 },
                                    },
                                    android: {
                                        elevation: 4,
                                    },
                                }),
                            },
                            headerTintColor: theme.colors.onSurface,
                            headerTitleStyle: {
                                fontFamily: 'Prompt_800ExtraBold',
                                fontSize: 20,
                                fontWeight: 'bold',
                                color: theme.colors.onSurface,
                            },
                            headerBackTitleVisible: false,
                            headerTitleAlign: Platform.OS === 'android' ? 'left' : 'center',
                            animation: 'none', // Évite le flash
                        }}
                    >
                        <Stack.Screen name="index" options={{ headerShown: false }} />
                        <Stack.Screen name="(tabs)" options={{ headerShown: false }} />

                        <Stack.Screen
                            name="screens/house_details/[id]"
                            options={getBaseHeaderOptions()}
                        />
                        <Stack.Screen
                            name="screens/seller_profile/[id]"
                            options={getBaseHeaderOptions()}
                        />
                        <Stack.Screen
                            name="screens/on_boarding"
                            options={getBaseHeaderOptions()}
                        />
                        <Stack.Screen
                            name="screens/login"
                            options={getBaseHeaderOptions()}
                        />
                        <Stack.Screen
                            name="screens/change_password"
                            options={getBaseHeaderOptions()}
                        />
                        <Stack.Screen
                            name="screens/manage_listings"
                            options={getBaseHeaderOptions()}
                        />
                        <Stack.Screen
                            name="screens/listing"
                            options={getBaseHeaderOptions()}
                        />
                        <Stack.Screen
                            name="screens/conversation"
                            options={getBaseHeaderOptions()}
                        />
                        <Stack.Screen
                            name="screens/edit_listing/[id]"
                            options={getBaseHeaderOptions()}
                        />
                        <Stack.Screen
                            name="screens/create_listing"
                            options={getBaseHeaderOptions()}
                        />
                        <Stack.Screen
                            name="screens/contact_seller"
                            options={getBaseHeaderOptions()}
                        />
                        <Stack.Screen
                            name="+not-found"
                            options={getBaseHeaderOptions('Oops!')}
                        />
                    </Stack>
                    <Toasts />
                </View>
            </NetworkProvider>
        </PaperProvider>
    );
}

export default function RootLayout() {
    const [fontsLoaded, fontError] = useFonts({
        SpaceMono: require('../assets/fonts/SpaceMono-Regular.ttf'),

        // Prompt (pour compatibilité avec l'existant)
        Prompt_400Regular,
        Prompt_500Medium,
        Prompt_600SemiBold,
        Prompt_700Bold,
        Prompt_800ExtraBold,

        // Inter (Police principale recommandée)
        Inter_300Light,
        Inter_400Regular,
        Inter_500Medium,
        Inter_600SemiBold,
        Inter_700Bold,
        Inter_800ExtraBold,

        // Poppins (Pour les titres et éléments premium)
        Poppins_300Light,
        Poppins_400Regular,
        Poppins_500Medium,
        Poppins_600SemiBold,
        Poppins_700Bold,
        Poppins_800ExtraBold,

        // Source Sans Pro (Alternative corporate)
        SourceSansPro_300Light,
        SourceSansPro_400Regular,
        SourceSansPro_600SemiBold,
        SourceSansPro_700Bold,
    });

    React.useEffect(() => {
        if (fontsLoaded || fontError) {
            SplashScreen.hideAsync();
        }
    }, [fontsLoaded, fontError]);

    if (!fontsLoaded && !fontError) {
        return null;
    }

    return (
        <StoreProvider store={store}>
            <PersistGate loading={null} persistor={persistor}>
                <GestureHandlerRootView style={styles.container}>
                    <SafeAreaProvider initialMetrics={initialWindowMetrics}>
                        <AppContent />
                    </SafeAreaProvider>
                </GestureHandlerRootView>
            </PersistGate>
        </StoreProvider>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
});