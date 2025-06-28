import React from 'react';
import { View, StyleSheet } from 'react-native';
import { TextInput as PaperTextInput, useTheme } from 'react-native-paper';
import Text from '@/components/typography/Text';
import { SPACING, BORDER_RADIUS, ELEVATION } from '@/constants/spacing';

export const TextInput = ({
                              label,
                              error,
                              style,
                              containerStyle,
                              variant = 'form', // 'form', 'search', 'login'
                              right,
                              onClear,
                              value,
                              clearable = false,
                              ...props
                          }) => {
    const { colors } = useTheme();

    // Style SearchBar exact (copié de votre original)
    if (variant === 'search') {
        const placeholderColor = 'rgba(0, 0, 0, 0.5)';
        const textColor = 'rgb(27, 27, 31)';
        const iconColor = 'rgb(69, 70, 79)';

        const searchRight = value && value.length > 0 && clearable ? (
            <PaperTextInput.Icon
                icon="close-circle"
                iconColor={iconColor}
                onPress={onClear}
            />
        ) : (
            <PaperTextInput.Icon
                icon="magnify"
                iconColor={iconColor}
            />
        );

        return (
            <View style={containerStyle}>
                <PaperTextInput
                    mode="outlined"
                    value={value}
                    style={[
                        styles.searchInput,
                        {
                            backgroundColor: '#ffffff', // Fond blanc permanent
                        },
                        style
                    ]}
                    theme={{
                        roundness: BORDER_RADIUS.md,
                        colors: {
                            primary: colors.primary,
                            outline: 'rgba(0, 0, 0, 0.2)', // Bordure visible sur blanc
                            onSurface: textColor,
                            onSurfaceVariant: placeholderColor,
                            surface: '#ffffff',
                            placeholder: placeholderColor,
                        }
                    }}
                    textColor={textColor}
                    placeholderTextColor={placeholderColor}
                    right={right || searchRight}
                    {...props}
                />
            </View>
        );
    }

    // Style Login avec fond blanc (nouveau)
    if (variant === 'login') {
        const placeholderColor = 'rgba(0, 0, 0, 0.5)';
        const textColor = 'rgb(27, 27, 31)';

        return (
            <View style={[styles.container, containerStyle]}>
                <PaperTextInput
                    mode="outlined"
                    value={value}
                    style={[
                        styles.loginInput,
                        {
                            backgroundColor: '#ffffff', // Fond blanc comme SearchBar
                        },
                        style
                    ]}
                    theme={{
                        roundness: BORDER_RADIUS.md,
                        colors: {
                            primary: colors.primary,
                            outline: error ? colors.error : 'rgba(0, 0, 0, 0.2)',
                            onSurface: textColor,
                            onSurfaceVariant: placeholderColor,
                            surface: '#ffffff',
                            placeholder: placeholderColor,
                            error: colors.error,
                        }
                    }}
                    textColor={textColor}
                    placeholderTextColor={placeholderColor}
                    right={right}
                    error={!!error}
                    {...props}
                />

                {error && (
                    <Text variant="bodySmall" color="error" style={styles.errorText}>
                        {error}
                    </Text>
                )}
            </View>
        );
    }

    // Style formulaire classique (avec couleurs du thème)
    return (
        <View style={[styles.container, containerStyle]}>
            {label && (
                <Text
                    variant="labelLarge"
                    color={error ? "error" : "textSecondary"}
                    style={styles.label}
                >
                    {label}
                </Text>
            )}

            <PaperTextInput
                mode="outlined"
                value={value}
                style={[
                    styles.formInput,
                    {
                        backgroundColor: colors.surface, // Couleur du thème
                        borderColor: error ? colors.error : colors.outline,
                    },
                    style
                ]}
                theme={{
                    roundness: BORDER_RADIUS.md,
                    colors: {
                        primary: colors.primary,
                        outline: error ? colors.error : colors.outline,
                        onSurface: colors.textPrimary,
                        onSurfaceVariant: colors.textPlaceholder,
                        surface: colors.surface,
                        placeholder: colors.textPlaceholder,
                        error: colors.error,
                    }
                }}
                textColor={colors.textPrimary}
                placeholderTextColor={colors.textPlaceholder}
                right={right}
                error={!!error}
                {...props}
            />

            {error && (
                <Text variant="bodySmall" color="error" style={styles.errorText}>
                    {error}
                </Text>
            )}
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        marginBottom: SPACING.lg,
    },
    label: {
        marginBottom: SPACING.sm,
    },
    errorText: {
        marginTop: SPACING.xs,
        marginLeft: SPACING.xs,
    },
    // Style SearchBar exact
    searchInput: {
        width: '100%',
        height: 56,
        fontSize: 14,
        justifyContent: 'center',
        elevation: ELEVATION.low,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 1 },
        shadowOpacity: 0.1,
        shadowRadius: 2,
    },
    // Style Login avec fond blanc
    loginInput: {
        height: 56,
        fontSize: 16,
        elevation: ELEVATION.low,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 1 },
        shadowOpacity: 0.1,
        shadowRadius: 2,
    },
    // Style formulaire
    formInput: {
        height: 56,
        fontSize: 16,
    },
});