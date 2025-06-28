import React from 'react';
import { View, StyleSheet } from 'react-native';
import { FontAwesome5 } from '@expo/vector-icons';
import { useTheme } from "react-native-paper";
import { formatPrice } from '@/utils/currencyUtils';
import Text from '@/components/typography/Text';
import { SPACING } from '@/constants/spacing';

export function DetailRow({ icon, label, value, type = 'text', priceOptions = {} }) {
    const { colors } = useTheme();

    // Fonction pour formater la valeur selon son type
    const formatValue = () => {
        if (type === 'price' && typeof value === 'object') {
            // Si c'est un prix, on attend un objet { amount, currency, isForRent }
            const { amount, currency, isForRent = false } = value;
            const formattedPrice = formatPrice(amount, currency, {
                isRental: isForRent,
                ...priceOptions
            });
            // Ensure we return a string
            return formattedPrice ? String(formattedPrice) : '';
        }

        // Pour les autres types, conversion simple en string
        if (value === null || value === undefined) {
            return '';
        }

        return String(value);
    };

    const getValueColor = () => {
        if (type === 'price') {
            return 'textPrice';
        }
        return 'textPrimary';
    };

    const getValueVariant = () => {
        if (type === 'price') {
            return 'priceSmall';
        }
        return 'bodyMedium';
    };

    const formattedValue = formatValue();

    return (
        <View style={styles.row}>
            <FontAwesome5
                name={icon}
                size={16}
                color={colors.textSecondary}
                style={styles.icon}
            />
            <Text variant="labelLarge" color="textSecondary" style={styles.label}>
                {label} :
            </Text>
            <Text
                variant={getValueVariant()}
                color={getValueColor()}
                style={styles.value}
            >
                {formattedValue}
            </Text>
        </View>
    );
}

const styles = StyleSheet.create({
    row: {
        flexDirection: 'row',
        alignItems: 'flex-start',
        marginVertical: SPACING.xs,
        flexWrap: 'wrap',
        gap: SPACING.sm,
    },
    icon: {
        marginRight: SPACING.sm,
        width: 20,
    },
    label: {
        marginRight: SPACING.xs,
    },
    value: {
        flex: 1,
    },
});