import Fba from "@/components/Fba";
import React from "react";
import { StyleSheet, useWindowDimensions, View } from "react-native";
import { Surface, useTheme, ActivityIndicator } from "react-native-paper";
import { Image } from 'expo-image';
import { LAYOUT } from '@/constants/welcome';
import { BlurView } from 'expo-blur';

const calculateDimensions = (screenWidth) => {
    const usableWidth = screenWidth - LAYOUT.CONTAINER_PADDING * 2 - LAYOUT.GAP;
    return {
        quarter: (usableWidth / 12) * 3,
        threeQuarters: (usableWidth / 12) * 9,
        third: (usableWidth / 12) * 5,
        twoThirds: (usableWidth / 12) * 7,
        usableWidth,
        gap: LAYOUT.GAP
    };
};

const ImageGrid = React.memo(({ dimensions, onImageLoad }) => {
    const { quarter, threeQuarters, third, twoThirds, usableWidth, gap } = dimensions;

    const imageProps = {
        contentFit: "cover",
        transition: 200,
        onLoad: onImageLoad
    };

    return (
        <View style={{ flex: 1.2 }}>
            <View style={styles.row}>
                <Image
                    source={require('@/assets/images/welcome-1.jpg')}
                    style={[styles.image, { width: quarter, marginRight: gap }]}
                    accessibilityLabel="Welcome image 1"
                    {...imageProps}
                />
                <Image
                    source={require('@/assets/images/welcome-2.jpg')}
                    style={[styles.image, { width: threeQuarters }]}
                    accessibilityLabel="Welcome image 2"
                    {...imageProps}
                />
            </View>
            <View style={styles.row}>
                <Image
                    source={require('@/assets/images/welcome-3.jpg')}
                    style={[styles.image, { width: twoThirds, marginRight: gap }]}
                    accessibilityLabel="Welcome image 3"
                    {...imageProps}
                />
                <Image
                    source={require('@/assets/images/welcome-4.jpg')}
                    style={[styles.image, { width: third }]}
                    accessibilityLabel="Welcome image 4"
                    {...imageProps}
                />
            </View>
            <View style={styles.row}>
                <Image
                    source={require('@/assets/images/welcome-5.jpg')}
                    style={[styles.image, { width: usableWidth + gap }]}
                    accessibilityLabel="Welcome image 5"
                    {...imageProps}
                />
            </View>
        </View>
    );
});

const LoadingOverlay = React.memo(({ visible, color }) => {
    if (!visible) return null;

    return (
        <BlurView intensity={50} style={styles.loadingOverlay}>
            <ActivityIndicator size="large" color={color} />
        </BlurView>
    );
});

export default function Index() {
    const { width: screenWidth } = useWindowDimensions();
    const { colors } = useTheme();
    const [imagesLoaded, setImagesLoaded] = React.useState(0);
    const totalImages = 6;

    const dimensions = React.useMemo(() =>
            calculateDimensions(screenWidth),
        [screenWidth]
    );

    const handleImageLoad = React.useCallback(() => {
        setImagesLoaded(prev => prev + 1);
    }, []);

    return (
        <Surface
            style={[
                styles.container,
                {
                    paddingHorizontal: LAYOUT.CONTAINER_PADDING,
                    backgroundColor: colors.background
                }
            ]}
        >
            <ImageGrid
                dimensions={dimensions}
                onImageLoad={handleImageLoad}
            />

            <View style={styles.bottomSection}>
                <View style={styles.logo}>
                    <Image
                        source={require('@/assets/images/icon.png')}
                        style={styles.logoImage}
                        contentFit="contain"
                        accessibilityLabel="App logo"
                        onLoad={handleImageLoad}
                    />
                </View>
                <View style={styles.fba}>
                    <Fba />
                </View>
            </View>

            <LoadingOverlay
                visible={imagesLoaded < totalImages}
                color={colors.primary}
            />
        </Surface>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        paddingVertical: 64,
    },
    row: {
        flexDirection: "row",
        marginBottom: 12,
        flex: 1,
    },
    image: {
        borderRadius: 8,
        flex: 1,
        height: "100%",
    },
    bottomSection: {
        flex: 0.8,
    },
    logo: {
        alignItems: "center",
        flex: 1.5,
        justifyContent: "flex-start",
    },
    logoImage: {
        width: LAYOUT.LOGO_WIDTH,
        flex: 1,
        height: "100%",
    },
    fba: {
        flex: 0.5,
        alignItems: "flex-end",
        justifyContent: "flex-end",
    },
    loadingOverlay: {
        ...StyleSheet.absoluteFillObject,
        justifyContent: 'center',
        alignItems: 'center',
    },
});