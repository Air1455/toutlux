import React from 'react';
import { View, StyleSheet } from 'react-native';
import { Text, useTheme } from 'react-native-paper';

export const StatCard = ({ value, label }) => {
    const { colors } = useTheme();

    return (
        <View style={styles.statCard}>
            <Text style={[styles.statValue, { color: colors.onSurface }]}>
                {value}
            </Text>
            <Text style={[styles.statLabel, { color: colors.onSurfaceVariant }]}>
                {label}
            </Text>
        </View>
    );
};

const styles = StyleSheet.create({
    statCard: {
        justifyContent: "center",
        paddingHorizontal: 10,
        paddingVertical: 10,
        flex: 1,
    },
    statValue: {
        fontSize: 18,
        fontWeight: 'bold',
        fontFamily: 'Prompt_800ExtraBold',
    },
    statLabel: {
        fontSize: 14,
        fontFamily: 'Prompt_400Regular',
    },
});