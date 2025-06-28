import React, {useEffect, useRef, useState} from 'react';
import {Platform, View, StyleSheet, Pressable} from 'react-native';
import MapView, {Marker, PROVIDER_GOOGLE} from 'react-native-maps';
import { AppleMaps } from 'expo-maps';
import HouseCard from "@components/home/HouseCard";
import {useTheme} from "react-native-paper";
import Text from '@/components/typography/Text';
import { SPACING, BORDER_RADIUS, ELEVATION } from '@/constants/spacing';

const MapContainer = ({ houses }) => {
    const mapRef = useRef(null);
    const { colors } = useTheme();
    const [selectedHouse, setSelectedHouse] = useState(null);
    const [mapReady, setMapReady] = useState(false);

    useEffect(() => {
        if (Platform.OS === 'android' && houses?.length > 0 && mapReady && mapRef.current) {
            const coordinates = houses.map(house => ({
                latitude: house.location[1],
                longitude: house.location[0],
            }));

            mapRef.current.fitToCoordinates(coordinates, {
                edgePadding: {
                    top: 50,
                    right: 50,
                    bottom: 50,
                    left: 50,
                },
                animated: true,
            });
        }
    }, [houses, mapReady]);

    if (Platform.OS === 'ios') {
        return <AppleMaps.View style={{ flex: 1 }} />;
    } else if (Platform.OS === 'android') {
        return (
            <View style={styles.container}>
                {/* Carte - prend 50% si maison sélectionnée, sinon toute la place */}
                <View style={[
                    styles.mapWrapper,
                    { borderColor: colors.outline },
                    selectedHouse ? { flex: 0.5 } : { flex: 1 }
                ]}>
                    <MapView
                        ref={mapRef}
                        onMapReady={() => setMapReady(true)}
                        style={styles.map}
                        provider={PROVIDER_GOOGLE}
                        initialRegion={{
                            latitude: 6.1319,
                            longitude: 1.2228,
                            latitudeDelta: 0.5,
                            longitudeDelta: 0.5,
                        }}
                    >
                        {houses.map((house) => (
                            <Marker
                                key={house.id}
                                coordinate={{
                                    latitude: house.location[1],
                                    longitude: house.location[0],
                                }}
                                pinColor={colors.primary}
                                onPress={() => setSelectedHouse(house)}
                                title={house.shortDescription}
                                description={`${house.city}, ${house.country}`}
                            />
                        ))}
                    </MapView>
                </View>

                {/* Fiche maison - prend 50% de la hauteur quand visible */}
                {!!selectedHouse && (
                    <View style={[styles.houseCardContainer, { flex: 0.5 }]}>
                        <Pressable
                            onPress={() => setSelectedHouse(null)}
                            style={({ pressed }) => [
                                styles.closeButton,
                                {
                                    backgroundColor: colors.surface,
                                    shadowColor: colors.shadow,
                                },
                                pressed && [styles.closeButtonPressed, { backgroundColor: colors.surfaceVariant }],
                            ]}
                            accessibilityLabel="Fermer"
                            accessibilityRole="button"
                        >
                            <Text variant="bodyLarge" style={[styles.closeButtonText, { color: colors.onSurface }]}>
                                ×
                            </Text>
                        </Pressable>
                        <HouseCard house={selectedHouse} onMap={true} />
                    </View>
                )}
            </View>
        );
    } else {
        return (
            <View style={[styles.container, styles.centered]}>
                <Text variant="bodyLarge" color="textSecondary" style={styles.unavailableText}>
                    Maps are only available on Android and iOS
                </Text>
            </View>
        );
    }
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        gap: SPACING.md
    },
    centered: {
        justifyContent: 'center',
        alignItems: 'center',
    },
    mapWrapper: {
        borderRadius: BORDER_RADIUS.lg,
        overflow: 'hidden',
        elevation: ELEVATION.low,
    },
    map: {
        flex: 1,
    },
    houseCardContainer: {
        position: "relative",
        minHeight: 50,
        marginTop: SPACING.md,
    },
    closeButton: {
        position: 'absolute',
        top: SPACING.xs,
        right: SPACING.lg,
        width: 30,
        height: 30,
        borderRadius: BORDER_RADIUS.md,
        justifyContent: 'center',
        alignItems: 'center',
        elevation: ELEVATION.medium,
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.25,
        shadowRadius: 3.84,
        zIndex: 1,
    },
    closeButtonPressed: {
        // Style appliqué dynamiquement
    },
    closeButtonText: {
        fontSize: 24,
        lineHeight: 24,
        fontWeight: 'bold',
        textAlign: 'center',
    },
    unavailableText: {
        textAlign: 'center',
        paddingHorizontal: SPACING.xl,
    },
});

export default MapContainer;