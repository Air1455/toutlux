import React from 'react';
import { View, StyleSheet } from 'react-native';
import { Card as PaperCard, useTheme } from 'react-native-paper';
import Text from './Text';

const Card = ({
                  title,
                  subtitle,
                  price,
                  priceUnit,
                  children,
                  style,
                  elevation = 2,
                  onPress,
                  ...props
              }) => {
    const { colors } = useTheme();

    return (
        <PaperCard
            style={[
                styles.card,
                {
                    backgroundColor: colors.surface,
                    shadowColor: colors.shadow,
                },
                style
            ]}
            elevation={elevation}
            onPress={onPress}
            {...props}
        >
            <PaperCard.Content style={styles.content}>
                {title && (
                    <Text variant="cardTitle" style={styles.title}>
                        {title}
                    </Text>
                )}

                {subtitle && (
                    <Text variant="bodyMedium" color="textSecondary" style={styles.subtitle}>
                        {subtitle}
                    </Text>
                )}

                {price && (
                    <View style={styles.priceContainer}>
                        <Text variant="priceCard" color="textPrice">
                            {price}
                        </Text>
                        {priceUnit && (
                            <Text variant="labelMedium" color="textSecondary" style={styles.priceUnit}>
                                {priceUnit}
                            </Text>
                        )}
                    </View>
                )}

                {children}
            </PaperCard.Content>
        </PaperCard>
    );
};

const styles = StyleSheet.create({
    card: {
        borderRadius: 12,
        marginVertical: 4,
        marginHorizontal: 8,
    },
    content: {
        paddingVertical: 16,
        paddingHorizontal: 16,
    },
    title: {
        marginBottom: 8,
    },
    subtitle: {
        marginBottom: 12,
    },
    priceContainer: {
        flexDirection: 'row',
        alignItems: 'baseline',
        marginTop: 8,
    },
    priceUnit: {
        marginLeft: 4,
    },
});

export default Card;