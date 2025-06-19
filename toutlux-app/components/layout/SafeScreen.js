import React from 'react';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useTheme } from 'react-native-paper';
import { useSelector } from 'react-redux';
import { StatusBar } from 'expo-status-bar';

/**
 * SafeScreen - Pour les écrans SANS header de navigation
 */
export const SafeScreen = React.memo(({
                                          children,
                                          style,
                                          edges = ['top', 'left', 'right', 'bottom'], // inclure 'top'
                                          statusBarStyle,
                                          statusBarBackgroundColor,
                                          withStatusBar = true,
                                      }) => {
    const { colors } = useTheme();
    const isDarkMode = useSelector((state) => state.theme.isDarkMode);

    const barStyle = statusBarStyle || (isDarkMode ? 'light' : 'dark');
    const backgroundColor = statusBarBackgroundColor || colors.background;

    return (
        <>
            {withStatusBar && (
                <StatusBar style={barStyle}/>
            )}
            <SafeAreaView
                style={[{ backgroundColor: colors.background, flex: 1 }, style]}
                edges={edges}
            >
                {children}
            </SafeAreaView>
        </>
    );
});
