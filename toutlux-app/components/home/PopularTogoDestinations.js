import React from 'react';
import { Image, StyleSheet, View } from "react-native";
import Text from '@/components/typography/Text';
import { SPACING, BORDER_RADIUS } from '@/constants/spacing';

export default function PopularTogoDestinations() {
    return (
        <View style={styles.container}>
            <View style={styles.row}>
                <View style={[styles.imageWrapper, styles.large]}>
                    <Image
                        source={require('@/assets/images/home-1.jpg')}
                        style={styles.image}
                    />
                    <View style={styles.labelContainer}>
                        <Text variant="labelMedium" style={styles.imageLabel}>Lomé</Text>
                    </View>
                </View>
                <View style={[styles.imageWrapper, styles.small]}>
                    <Image
                        source={require('@/assets/images/home-2.jpg')}
                        style={styles.image}
                    />
                    <View style={styles.labelContainer}>
                        <Text variant="labelMedium" style={styles.imageLabel}>Kpalimé</Text>
                    </View>
                </View>
            </View>
            <View style={styles.row}>
                <View style={[styles.imageWrapper, styles.small]}>
                    <Image
                        source={require('@/assets/images/home-3.jpg')}
                        style={styles.image}
                    />
                    <View style={styles.labelContainer}>
                        <Text variant="labelMedium" style={styles.imageLabel}>Aného</Text>
                    </View>
                </View>
                <View style={[styles.imageWrapper, styles.large]}>
                    <Image
                        source={require('@/assets/images/home-4.jpg')}
                        style={styles.image}
                    />
                    <View style={styles.labelContainer}>
                        <Text variant="labelMedium" style={styles.imageLabel}>Kara</Text>
                    </View>
                </View>
            </View>
        </View>
    );
}

const styles = StyleSheet.create({
    container: {
        width: '100%', // Prend toute la largeur du parent
    },
    row: {
        flexDirection: "row",
        marginBottom: SPACING.md,
        gap: SPACING.md, // Plus moderne que marginRight
    },
    imageWrapper: {
        position: 'relative',
        borderRadius: BORDER_RADIUS.lg,
        overflow: 'hidden',
        height: 133,
    },
    large: {
        flex: 1.8, // Prend 2/3 de l'espace
    },
    small: {
        flex: 1.2, // Prend 1/3 de l'espace
    },
    labelContainer: {
        position: 'absolute',
        bottom: SPACING.sm,
        left: SPACING.sm,
    },
    imageLabel: {
        backgroundColor: 'rgba(255,255,255,0.9)',
        color: '#000',
        paddingHorizontal: SPACING.md,
        paddingVertical: SPACING.xs,
        borderRadius: BORDER_RADIUS.md,
        fontSize: 12,
        fontWeight: '500',
        textAlign: 'center',
    },
    image: {
        width: '100%',
        height: '100%',
        backgroundColor: '#f0f0f0',
    },
});