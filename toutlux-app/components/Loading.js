import React from 'react';
import { View, StyleSheet } from 'react-native';
import { ActivityIndicator, useTheme } from 'react-native-paper';
import { useTranslation } from 'react-i18next';
import { LinearGradient } from 'expo-linear-gradient';

import Text from '@/components/typography/Text';
import { SPACING } from '@/constants/spacing';

export const LoadingScreen = ({message, withGradient = true, size = 'large', containerStyle}) => {
    const { colors } = useTheme();
    const { t } = useTranslation();

    const Container = withGradient ? LinearGradient : View;

    const containerProps = withGradient
        ? { colors: [colors.background, colors.surface] }
        : { style: { backgroundColor: colors.background } };

    return (
        <Container
            {...containerProps}
            style={[styles.container, containerStyle]}
        >
            <View style={styles.content}>
                <ActivityIndicator size={size} color={colors.primary} />
                <Text variant="bodyLarge" color="textPrimary" style={styles.text}>
                    {message || t('common.loading')}
                </Text>
            </View>
        </Container>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    content: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
        gap: SPACING.lg,
    },
    text: {
        textAlign: 'center',
        marginTop: SPACING.md,
    },
});