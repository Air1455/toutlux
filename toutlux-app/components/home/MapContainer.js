import React, {useEffect, useRef, useState} from 'react';
import {Platform, Text, View, StyleSheet, Pressable} from 'react-native';
import MapView, {Marker, PROVIDER_GOOGLE} from 'react-native-maps';
import { AppleMaps } from 'expo-maps';
import HouseCard from "@components/home/HouseCard";
import {useTheme} from "react-native-paper";

const MapContainer = ({ houses }) => {
    const mapRef = useRef(null);
    const { colors }= useTheme()
    const [selectedHouse, setSelectedHouse] = useState(null)
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
                <View style={{ borderRadius: 16, overflow: 'hidden', flex: 1 }}>
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
                                onPress={()=> setSelectedHouse(house)}
                                title={house.shortDescription}
                                description={`${house.city}, ${house.country}`}
                            />
                        ))}
                    </MapView>
                </View>
                {!!selectedHouse && <View style={styles.houseCardContainer}>
                    <Pressable
                        onPress={()=> setSelectedHouse(null)}
                        style={({ pressed }) => [
                            styles.closeButton,
                            pressed && styles.closeButtonPressed,
                        ]}
                        accessibilityLabel="Fermer"
                        accessibilityRole="button"
                    >
                        <Text style={styles.closeButtonText}>×</Text>
                    </Pressable>
                    <HouseCard house={selectedHouse} onMap={true} />
                </View>}
            </View>
        );
    } else {
        return <Text>Maps are only available on Android and iOS</Text>;
    }
};

export default MapContainer;

const styles = StyleSheet.create({
    container: { flex: 1, gap: 12 },
    map: { flex: 1, borderRadius: 10 },
    houseCardContainer: {
        position: "relative",
        minHeight: 50,
        marginTop: 10
    },
    closeButton: {
        position: 'absolute',
        top: 5,
        right: 5,
        width: 36,
        height: 36,
        borderRadius: 12,
        backgroundColor: 'white',
        justifyContent: 'center',
        alignItems: 'center',
        elevation: 5, // ombre Android
        shadowColor: '#000', // ombre iOS
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.25,
        shadowRadius: 3.84,
        zIndex: 1,
    },
    closeButtonPressed: {
        backgroundColor: '#ddd',
    },
    closeButtonText: {
        fontSize: 24,
        lineHeight: 24,
        color: '#333',
        fontWeight: 'bold',
    },
});
