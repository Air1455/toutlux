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

    // Version simplifiée de renderChildren
    const renderChildren = () => {
        if (children === null || children === undefined) {
            return '';
        }

        if (typeof children === 'string' || typeof children === 'number') {
            return children;
        }

        if (typeof children === 'boolean') {
            return children.toString();
        }

        // Pour les arrays, joindre les éléments
        if (Array.isArray(children)) {
            return children
                .filter(child => child !== null && child !== undefined)
                .map(child => {
                    if (typeof child === 'string' || typeof child === 'number') {
                        return child;
                    }
                    return String(child);
                })
                .join('');
        }

        // Si c'est un objet React ou autre chose
        if (React.isValidElement(children)) {
            console.warn('Text component: Received React element as children');
            return '';
        }

        // Dernier recours
        return String(children);
    };

    // Mappage des couleurs de texte
    const getTextColor = () => {
        if (color) {
            return colors[color] || color;
        }

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

    const processedChildren = renderChildren();

    return (
        <RNText
            style={[
                TYPOGRAPHY[variant],
                { color: getTextColor() },
                style
            ]}
            {...props}
        >
            {processedChildren}
        </RNText>
    );
};

export default Text;