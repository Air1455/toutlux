import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { FontAwesome5 } from '@expo/vector-icons';
import {useTheme} from "react-native-paper";

export function DetailRow({ icon, label, value }) {
    const {colors}= useTheme()

    return (
        <View style={styles.row}>
            <FontAwesome5 name={icon} size={16} color={colors.text} style={styles.icon} />
            <Text style={[styles.label, {color: colors.text}]}>{label} :</Text>
            <Text style={{color: colors.text}}>{value}</Text>
        </View>
    );
}

const styles = StyleSheet.create({
    row: {
        flexDirection: 'row',
        alignItems: 'center',
        marginVertical: 4,
    },
    icon: {
        marginRight: 8,
        width: 20,
    },
    label: {
        fontWeight: '600',
        marginRight: 4,
    },
});
