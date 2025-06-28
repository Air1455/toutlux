import React from 'react';
import { TextInput as PaperTextInput, useTheme } from 'react-native-paper';
import { TYPOGRAPHY } from '@/utils/typography';

const TextInput = ({
                       mode = 'outlined',
                       style,
                       contentStyle,
                       ...props
                   }) => {
    const { colors } = useTheme();

    return (
        <PaperTextInput
            mode={mode}
            style={[
                {
                    backgroundColor: mode === 'outlined' ? 'transparent' : colors.surface,
                },
                style
            ]}
            contentStyle={[
                TYPOGRAPHY.bodyMedium,
                { color: colors.textPrimary },
                contentStyle
            ]}
            theme={{
                colors: {
                    primary: colors.primary,
                    outline: colors.outline,
                    onSurface: colors.textPrimary,
                    onSurfaceVariant: colors.textSecondary,
                    placeholder: colors.textPlaceholder,
                    surface: colors.surface,
                    surfaceVariant: colors.surfaceVariant,
                }
            }}
            placeholderTextColor={colors.textPlaceholder}
            {...props}
        />
    );
};

export default TextInput;