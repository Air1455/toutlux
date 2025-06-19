import React from 'react';
import {
    TouchableOpacity,
    Text,
    StyleSheet,
    ActivityIndicator
} from 'react-native';
import { useTheme } from 'react-native-paper';

export const Button = ({
                           mode = 'contained',
                           onPress,
                           style,
                           loading = false,
                           disabled = false,
                           children,
                           textStyle,
                           ...props
                       }) => {
    const { colors } = useTheme();

    const getBackgroundColor = () => {
        if (disabled) return colors.disabled;
        if (mode === 'outlined') return 'transparent';
        return colors.primary;
    };

    const getBorderColor = () => {
        if (disabled) return colors.disabled;
        if (mode === 'outlined') return colors.primary;
        return 'transparent';
    };

    const getTextColor = () => {
        if (disabled) return colors.placeholder;
        if (mode === 'outlined') return colors.primary;
        return colors.surface;
    };

    return (
        <TouchableOpacity
            onPress={onPress}
            disabled={disabled || loading}
            style={[
                styles.button,
                {
                    backgroundColor: getBackgroundColor(),
                    borderColor: getBorderColor(),
                },
                mode === 'outlined' && styles.outlined,
                style,
            ]}
            {...props}
        >
            {loading ? (
                <ActivityIndicator
                    color={getTextColor()}
                    size="small"
                />
            ) : (
                <Text
                    style={[
                        styles.text,
                        { color: getTextColor() },
                        textStyle,
                    ]}
                >
                    {children}
                </Text>
            )}
        </TouchableOpacity>
    );
};

const styles = StyleSheet.create({
    button: {
        height: 48,
        borderRadius: 8,
        justifyContent: 'center',
        alignItems: 'center',
        paddingHorizontal: 24,
    },
    outlined: {
        borderWidth: 1,
    },
    text: {
        fontSize: 16,
        fontWeight: '600',
        textAlign: 'center',
    },
});