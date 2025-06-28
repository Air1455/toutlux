import { useLayoutEffect } from 'react';
import { useTheme } from 'react-native-paper';
import { useNavigation } from 'expo-router';
import {Platform } from 'react-native';

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
        // 🎯 Vérification que le thème est prêt avant application
        if (!colors || !colors.surface || !colors.onSurface) {
            return; // Attendre que le thème soit chargé
        }

        const defaultOptions = {
            title,
            headerStyle: {
                backgroundColor: colors.surface, // Changé de background à surface
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
            headerTintColor: colors.onSurface, // Changé de onBackground à onSurface
            headerTitleStyle: {
                fontFamily: 'Prompt_800ExtraBold',
                fontSize: 20,
                fontWeight: 'bold',
                color: colors.onSurface, // Changé de onBackground à onSurface
            },
            headerBackTitleVisible: false,
            headerTitleAlign: Platform.OS === 'android' ? 'left' : 'center',
            // 🔧 AJOUT: Désactive les animations pour éviter le flash
            animation: 'none',
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
    }, [navigation, title, colors.surface, colors.onSurface, ...dependencies]);
};

/**
 * Hook spécialisé pour les écrans d'onboarding avec progress
 */
export const useOnboardingHeaderOptions = (title, progress, dependencies = []) => {
    const customOptions = {headerTitleAlign: 'center'};
    useHeaderOptions(title, [...dependencies, progress], customOptions);
};