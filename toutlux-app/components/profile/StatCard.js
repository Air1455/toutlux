import React from 'react';
import { View, StyleSheet } from 'react-native';
import { useTheme } from 'react-native-paper';

import Text from '@/components/typography/Text';
import { SPACING } from '@/constants/spacing';

export const StatCard = ({ value, label, valueColor }) => {
    const { colors } = useTheme();

    return (
        <View style={styles.statCard}>
            <Text
                variant="cardTitle"
                color={valueColor ? undefined : "textPrimary"}
                style={[
                    styles.statValue,
                    valueColor && { color: valueColor }
                ]}
            >
                {value}
            </Text>
            <Text variant="bodyMedium" color="textSecondary" style={styles.statLabel}>
                {label}
            </Text>
        </View>
    );
};

const styles = StyleSheet.create({
    statCard: {
        justifyContent: "center",
        paddingHorizontal: SPACING.sm,
        paddingVertical: SPACING.sm,
        flex: 1,
    },
    statValue: {
        // Typography géré par le composant Text
    },
    statLabel: {
        // Typography géré par le composant Text
    },
});