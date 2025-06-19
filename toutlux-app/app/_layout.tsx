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
import { Prompt_400Regular, Prompt_800ExtraBold } from '@expo-google-fonts/prompt';
import { store, persistor } from '@/redux/store';
import useAppTheme from '@/hooks/useAppTheme';
import {setDarkMode} from "@/redux/themeReducer";

SplashScreen.preventAutoHideAsync();

function AppContent() {
    const colorScheme = useColorScheme();
    const dispatch = useDispatch();
    const isDarkMode = useSelector((state) => state.theme.isDarkMode);
    const { theme } = useAppTheme();

    // Fonction pour obtenir les options de base du header
    const getBaseHeaderOptions = (title?: string) => ({
        headerShown: true,
        headerBackVisible: true,
        ...(title && { title }),
    });

    useEffect(() => {
        if (isDarkMode === null) {
            const systemIsDark = colorScheme === 'dark';
            dispatch(setDarkMode(systemIsDark));
        }
    }, [isDarkMode, colorScheme, dispatch]);

    return (
        <PaperProvider theme={theme}>
            <Stack>
                <Stack.Screen name="index" options={{ headerShown: false }} />
                <Stack.Screen name="(tabs)" options={{ headerShown: false }} />

                <Stack.Screen
                    name="screens/house_details/[id]"
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
                    name="+not-found"
                    options={getBaseHeaderOptions('Oops!')}
                />
            </Stack>
            <Toasts />
        </PaperProvider>
    );
}

export default function RootLayout() {
    const [fontsLoaded, fontError] = useFonts({
        SpaceMono: require('../assets/fonts/SpaceMono-Regular.ttf'),
        Prompt_800ExtraBold,
        Prompt_400Regular,
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