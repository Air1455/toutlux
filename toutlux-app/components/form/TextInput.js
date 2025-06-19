import React from 'react';
import { View, Text, TextInput as RNTextInput, StyleSheet } from 'react-native';
import { useTheme } from 'react-native-paper';

export const TextInput = ({
                              label,
                              error,
                              style,
                              ...props
                          }) => {
    const { colors } = useTheme();

    return (
        <View style={[styles.container, style]}>
            {label && (
                <Text style={[
                    styles.label,
                    { color: error ? colors.error : colors.text }
                ]}>
                    {label}
                </Text>
            )}
            <RNTextInput
                style={[
                    styles.input,
                    {
                        borderColor: error ? colors.error : colors.text,
                        color: colors.text,
                        backgroundColor: colors.surface,
                    }
                ]}
                placeholderTextColor={colors.placeholder}
                {...props}
            />
            {error && (
                <Text style={[styles.errorText, { color: colors.error }]}>
                    {error}
                </Text>
            )}
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        marginBottom: 16,
    },
    label: {
        marginBottom: 8,
        fontSize: 14,
        fontWeight: '500',
    },
    input: {
        height: 48,
        borderWidth: 1,
        borderRadius: 8,
        paddingHorizontal: 16,
        fontSize: 16,
    },
    errorText: {
        fontSize: 12,
        marginTop: 4,
    },
});