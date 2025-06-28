import React from 'react';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useTheme } from 'react-native-paper';
import { useSelector } from 'react-redux';
import { StatusBar } from 'expo-status-bar';
import {Platform} from "react-native";

/**
 * SafeScreen - Pour les écrans SANS header de navigation
 */
export const SafeScreen = React.memo(({
                                          children,
                                          style,
                                          edges = ['top', 'left', 'right'], // CHANGEMENT: retiré 'bottom' par défaut
                                          statusBarStyle,
                                          statusBarBackgroundColor,
                                          withStatusBar = true,
                                          includeBottom = false, // Nouvelle prop pour inclure bottom quand nécessaire
                                      }) => {
    const { colors } = useTheme();
    const isDarkMode = useSelector((state) => state.theme.isDarkMode);
    const TAB_HEIGHT = Platform.OS === 'ios' ? 85 : 70;

    const barStyle = statusBarStyle || (isDarkMode ? 'light' : 'dark');

    // Ajouter bottom edge seulement si explicitement demandé
    const finalEdges = includeBottom ? [...edges, 'bottom'] : edges;

    const containerStyle = [
        {
            backgroundColor: colors.background,
            flex: 1,
            paddingBottom: includeBottom ? 0 : TAB_HEIGHT
        },
        style
    ];

    return (
        <>
            {withStatusBar && (
                <StatusBar style={barStyle}/>
            )}
            <SafeAreaView
                style={containerStyle}
                edges={finalEdges}
            >
                {children}
            </SafeAreaView>
        </>
    );
});

SafeScreen.displayName = 'SafeScreen';