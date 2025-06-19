import {Image, StyleSheet, Text, useWindowDimensions, View} from "react-native";

export default function PopularTogoDestinations() {
    const { width: screenWidth } = useWindowDimensions();
    const gap = 12;
    const usableWidth = screenWidth + 20 - gap;

    const third = (usableWidth / 12) * 4;
    const twoThirds = (usableWidth / 12) * 6;

    return (
        <View style={styles.imgContainer}>
            <View style={styles.row}>
                <View style={[styles.imageWrapper, { width: twoThirds, marginRight: gap }]}>
                    <Image
                        source={require('@/assets/images/home-1.jpg')}
                        style={styles.image}
                    />
                    <Text style={styles.imageLabel}>Lomé</Text>
                </View>
                <View style={[styles.imageWrapper, { width: third }]}>
                    <Image
                        source={require('@/assets/images/home-2.jpg')}
                        style={styles.image}
                    />
                    <Text style={styles.imageLabel}>Kpalimé</Text>
                </View>
            </View>
            <View style={styles.row}>
                <View style={[styles.imageWrapper, { width: third, marginRight: gap }]}>
                    <Image
                        source={require('@/assets/images/home-3.jpg')}
                        style={styles.image}
                    />
                    <Text style={styles.imageLabel}>Aného</Text>
                </View>
                <View style={[styles.imageWrapper, { width: twoThirds }]}>
                    <Image
                        source={require('@/assets/images/home-4.jpg')}
                        style={styles.image}
                    />
                    <Text style={styles.imageLabel}>Kara</Text>
                </View>
            </View>
        </View>
    );
}

const styles = StyleSheet.create({
    row: {
        flexDirection: "row",
        marginBottom: 12,
    },
    imgContainer: {

    },
    imageWrapper: {
        position: 'relative',
        borderRadius: 16,
        overflow: 'hidden',
        height: 133,
    },
    imageLabel: {
        position: 'absolute',
        bottom: 8,
        left: 8,
        backgroundColor: 'rgba(255,255,255,0.7)',
        color: 'black',
        paddingHorizontal: 18,
        paddingVertical: 4,
        borderRadius: 100,
        fontSize: 14,
        overflow: 'hidden',
        fontFamily: 'Prompt_400Regular',
    },
    image: {
        height: 133,
        borderRadius: 16,
        width: '100%',
        padding: '0px 8px',
        border: '0',
        boxSizing: 'border-box',
        boxShadow: '0px 0px 10px rgba(3,3,3,0.1)',
        backgroundColor: 'rgba(255,255,255,0.64)',
        color: '#030303',
    },
})