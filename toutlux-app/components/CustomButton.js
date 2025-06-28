// components/CustomButton.js
import React from 'react';
import { TouchableOpacity, StyleSheet, ActivityIndicator } from 'react-native';
import { useTheme } from 'react-native-paper';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import Text from '@/components/typography/Text';
import { SPACING, BORDER_RADIUS } from '@/constants/spacing';

const CustomButton = ({
                          content,
                          onPress,
                          iconName,
                          variant = 'default',
                          radius = 'medium',
                          disabled = false,
                          loading = false,
                          style,
                          textStyle,
                          iconSize = 18,
                          ...props
                      }) => {
    const { colors } = useTheme();

    const getButtonStyle = () => {
        const baseStyle = {
            paddingVertical: SPACING.sm,
            paddingHorizontal: SPACING.lg,
            flexDirection: 'row',
            alignItems: 'center',
            justifyContent: 'center',
            gap: SPACING.sm,
            // CLÉS IMPORTANTES pour la largeur adaptative :
            flexShrink: 1,      // Permet au bouton de se rétrécir
            flexGrow: 0,        // Empêche le bouton de grandir
            minWidth: 0,        // Pas de largeur minimale imposée
        };

        const radiusStyle = {
            small: { borderRadius: BORDER_RADIUS.sm },
            medium: { borderRadius: BORDER_RADIUS.md },
            large: { borderRadius: BORDER_RADIUS.lg },
            rounded: { borderRadius: 50 },
        };

        const variantStyle = {
            default: {
                backgroundColor: colors.surface,
                borderWidth: 1,
                borderColor: colors.outline,
            },
            yellow: {
                backgroundColor: '#FFC107',
                borderWidth: 0,
            },
            blue: {
                backgroundColor: colors.primary,
                borderWidth: 0,
            },
            outline: {
                backgroundColor: 'transparent',
                borderWidth: 1,
                borderColor: colors.primary,
            },
        };

        return [
            baseStyle,
            radiusStyle[radius],
            variantStyle[variant],
            disabled && { opacity: 0.6 },
            style,
        ];
    };

    const getTextColor = () => {
        switch (variant) {
            case 'yellow':
                return '#000';
            case 'blue':
                return '#fff';
            case 'outline':
                return colors.primary;
            default:
                return colors.textPrimary;
        }
    };

    const renderContent = () => {
        if (content === null || content === undefined) {
            return '';
        }
        return String(content);
    };

    return (
        <TouchableOpacity
            style={getButtonStyle()}
            onPress={onPress}
            disabled={disabled || loading}
            activeOpacity={0.7}
            {...props}
        >
            {loading ? (
                <ActivityIndicator size="small" color={getTextColor()} />
            ) : (
                <>
                    {iconName && (
                        <MaterialCommunityIcons
                            name={iconName}
                            size={iconSize}
                            color={getTextColor()}
                        />
                    )}
                    <Text
                        variant="buttonMedium"
                        style={[
                            { color: getTextColor() },
                            textStyle
                        ]}
                        numberOfLines={1} // Empêche le texte de passer sur plusieurs lignes
                    >
                        {renderContent()}
                    </Text>
                </>
            )}
        </TouchableOpacity>
    );
};

export default CustomButton;