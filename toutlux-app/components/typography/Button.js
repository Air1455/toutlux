import React from 'react';
import { Text as RNText, StyleSheet } from 'react-native';
import { useTheme } from 'react-native-paper';
import { TYPOGRAPHY } from '@/utils/typography';

const Text = ({
                  variant = 'bodyMedium',
                  color,
                  style,
                  children,
                  ...props
              }) => {
    const { colors } = useTheme();

    // Mappage des couleurs de texte
    const getTextColor = () => {
        if (color) {
            // Si une couleur spécifique est fournie, utiliser les couleurs du thème
            return colors[color] || color;
        }

        // Couleurs par défaut selon le variant
        switch (variant) {
            case 'heroTitle':
            case 'pageTitle':
            case 'sectionTitle':
            case 'cardTitle':
                return colors.textPrimary;
            case 'priceHero':
            case 'priceCard':
            case 'priceSmall':
                return colors.textPrice;
            case 'bodyLarge':
            case 'bodyMedium':
            case 'bodySmall':
                return colors.textOnCard;
            case 'labelLarge':
            case 'labelMedium':
            case 'labelSmall':
                return colors.textSecondary;
            case 'buttonLarge':
            case 'buttonMedium':
            case 'buttonSmall':
                return colors.textPrimary;
            case 'subtitle':
            case 'caption':
                return colors.textSecondary;
            case 'overline':
                return colors.textHint;
            default:
                return colors.textPrimary;
        }
    };

    return (
        <RNText
            style={[
                TYPOGRAPHY[variant],
                { color: getTextColor() },
                style
            ]}
            {...props}
        >
            {children}
        </RNText>
    );
};

export default Text;