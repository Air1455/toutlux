// hooks/useHeaderOptions.js
import { useLayoutEffect } from 'react';
import { useTheme } from 'react-native-paper';
import { useNavigation } from 'expo-router';
import {Platform, Text, TouchableOpacity, View} from 'react-native';
import {MaterialCommunityIcons} from "@expo/vector-icons";

/**
 * Hook pour gérer les options du header de manière cohérente
 * @param {string} title - Le titre du header
 * @param {Array} dependencies - Dépendances supplémentaires pour le useLayoutEffect
 * @param {Object} customOptions - Options personnalisées à merger
 */
export const useHeaderOptions = (title, dependencies = [], customOptions = {}) => {
    const { colors } = useTheme();
    const navigation = useNavigation();

    useLayoutEffect(() => {
        const defaultOptions = {
            title,
            headerStyle: {
                backgroundColor: colors.background,
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
            headerTintColor: colors.onBackground,
            headerTitleStyle: {
                fontFamily: 'Prompt_800ExtraBold',
                fontSize: 20,
                fontWeight: 'bold',
                color: colors.onBackground,
            },
            headerBackTitleVisible: false,
            headerTitleAlign: Platform.OS === 'android' ? 'left' : 'center',
            // Ne pas toucher à headerStatusBarHeight - laisser React Navigation gérer
        };

        // Merger les options en profondeur
        const finalOptions = {
            ...defaultOptions,
            ...customOptions,
            headerStyle: {
                ...defaultOptions.headerStyle,
                ...(customOptions.headerStyle || {}),
            },
            headerTitleStyle: {
                ...defaultOptions.headerTitleStyle,
                ...(customOptions.headerTitleStyle || {}),
            },
        };

        navigation.setOptions(finalOptions);
    }, [navigation, title, colors.background, colors.onBackground, ...dependencies]);
};

/**
 * Hook spécialisé pour les écrans d'onboarding avec progress
 */
export const useOnboardingHeaderOptions = (title, progress, dependencies = []) => {
    const { colors } = useTheme();

    const customOptions = {
        headerTitleAlign: 'center',
        // headerRight: () => (
        //     <View style={{ marginRight: 16 }}>
        //         <Text style={{ color: colors.onSurfaceVariant, fontSize: 14 }}>
        //             {Math.round(progress * 100)}%
        //         </Text>
        //     </View>
        // ),
    };

    useHeaderOptions(title, [...dependencies, progress], customOptions);
};

/**
 * Hook pour les écrans sans bouton de retour
 */
export const useHeaderOptionsNoBack = (title, dependencies = []) => {
    const customOptions = {
        headerBackVisible: false,
        headerLeft: () => null,
    };

    useHeaderOptions(title, dependencies, customOptions);
};

/**
 * Hook pour les écrans modaux
 */
export const useModalHeaderOptions = (title, onClose, dependencies = []) => {
    const { colors } = useTheme();

    const customOptions = {
        presentation: 'modal',
        headerRight: () => (
            <TouchableOpacity onPress={onClose} style={{ marginRight: 16 }}>
                <MaterialCommunityIcons
                    name="close"
                    size={24}
                    color={colors.onBackground}
                />
            </TouchableOpacity>
        ),
    };

    useHeaderOptions(title, dependencies, customOptions);
};