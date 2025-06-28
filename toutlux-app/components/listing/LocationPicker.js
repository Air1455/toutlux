import React, { useState, useEffect } from 'react';
import { View, StyleSheet, Dimensions } from 'react-native';
import { Button, useTheme, Card } from 'react-native-paper';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { useTranslation } from 'react-i18next';
import MapView, { Marker, PROVIDER_GOOGLE } from 'react-native-maps';

import Text from '@/components/typography/Text';
import { SPACING, BORDER_RADIUS, ELEVATION } from '@/constants/spacing';

const { width } = Dimensions.get('window');

const LocationPicker = ({
                            location = { lat: 48.8566, lng: 2.3522 }, // Paris par défaut
                            onLocationChange,
                            address,
                            city,
                            country,
                            height = 200
                        }) => {
    const { colors } = useTheme();
    const { t } = useTranslation();

    const [mapRegion, setMapRegion] = useState({
        latitude: location.lat || 48.8566,
        longitude: location.lng || 2.3522,
        latitudeDelta: 0.01,
        longitudeDelta: 0.01,
    });

    const [isMapReady, setIsMapReady] = useState(false);
    const [showMap, setShowMap] = useState(false);

    useEffect(() => {
        if (location.lat && location.lng) {
            const newRegion = {
                latitude: location.lat,
                longitude: location.lng,
                latitudeDelta: 0.01,
                longitudeDelta: 0.01,
            };
            setMapRegion(newRegion);
        }
    }, [location]);

    const handleMapPress = (event) => {
        const { latitude, longitude } = event.nativeEvent.coordinate;
        const newLocation = { lat: latitude, lng: longitude };

        onLocationChange(newLocation);

        setMapRegion({
            ...mapRegion,
            latitude,
            longitude,
        });
    };

    const geocodeAddress = async () => {
        if (!address || !city) {
            return;
        }

        try {
            const fullAddress = `${address}, ${city}, ${country}`;
            // Ici vous pourriez intégrer un service de géocodage comme Google Geocoding API
            // Pour l'instant, on utilise une position par défaut
            console.log('Geocoding address:', fullAddress);

            // Exemple basique de géocodage (vous devriez utiliser un vrai service)
            const defaultLocation = { lat: 48.8566, lng: 2.3522 };
            onLocationChange(defaultLocation);

        } catch (error) {
            console.error('Geocoding error:', error);
        }
    };

    const formatLocationDisplay = () => {
        if (location.lat && location.lng) {
            return `${location.lat.toFixed(6)}, ${location.lng.toFixed(6)}`;
        }
        return t('listings.form.noLocationSelected');
    };

    const toggleMap = () => {
        setShowMap(!showMap);
    };

    return (
        <View style={styles.container}>
            <Text variant="cardTitle" color="textPrimary" style={styles.sectionTitle}>
                {t('listings.form.location')}
            </Text>

            <Card
                style={[
                    styles.locationCard,
                    {
                        backgroundColor: colors.surface,
                        borderRadius: BORDER_RADIUS.lg
                    }
                ]}
                elevation={ELEVATION.low}
            >
                <Card.Content style={styles.cardContent}>
                    <View style={styles.locationInfo}>
                        <MaterialCommunityIcons
                            name="map-marker"
                            size={24}
                            color={colors.primary}
                        />
                        <View style={styles.locationText}>
                            <Text variant="labelLarge" color="textPrimary" style={styles.locationLabel}>
                                {t('listings.form.coordinates')}
                            </Text>
                            <Text variant="bodyMedium" color="textSecondary" style={styles.locationValue}>
                                {formatLocationDisplay()}
                            </Text>
                        </View>
                    </View>

                    <View style={styles.locationActions}>
                        <Button
                            mode="outlined"
                            onPress={geocodeAddress}
                            disabled={!address || !city}
                            style={[styles.actionButton, { borderRadius: BORDER_RADIUS.md }]}
                            compact
                        >
                            {t('listings.form.geocodeAddress')}
                        </Button>

                        <Button
                            mode={showMap ? "contained" : "outlined"}
                            onPress={toggleMap}
                            style={[styles.actionButton, { borderRadius: BORDER_RADIUS.md }]}
                            compact
                        >
                            {showMap ? t('listings.form.hideMap') : t('listings.form.showMap')}
                        </Button>
                    </View>
                </Card.Content>
            </Card>

            {showMap && (
                <Card
                    style={[
                        styles.mapCard,
                        {
                            backgroundColor: colors.surface,
                            borderRadius: BORDER_RADIUS.lg
                        }
                    ]}
                    elevation={ELEVATION.low}
                >
                    <Card.Content style={styles.mapContent}>
                        <Text variant="bodyMedium" color="textHint" style={styles.mapInstructions}>
                            {t('listings.form.mapInstructions')}
                        </Text>

                        <View style={[styles.mapContainer, { height }]}>
                            <MapView
                                provider={PROVIDER_GOOGLE}
                                style={styles.map}
                                region={mapRegion}
                                onPress={handleMapPress}
                                onMapReady={() => setIsMapReady(true)}
                                showsUserLocation={false}
                                showsMyLocationButton={false}
                                toolbarEnabled={false}
                            >
                                {location.lat && location.lng && (
                                    <Marker
                                        coordinate={{
                                            latitude: location.lat,
                                            longitude: location.lng,
                                        }}
                                        title={t('listings.form.propertyLocation')}
                                        description={address}
                                        pinColor={colors.primary}
                                    />
                                )}
                            </MapView>

                            {!isMapReady && (
                                <View style={[styles.mapLoading, { backgroundColor: colors.surface }]}>
                                    <MaterialCommunityIcons
                                        name="map"
                                        size={48}
                                        color={colors.textSecondary}
                                    />
                                    <Text variant="bodyMedium" color="textSecondary" style={styles.mapLoadingText}>
                                        {t('listings.form.loadingMap')}
                                    </Text>
                                </View>
                            )}
                        </View>
                    </Card.Content>
                </Card>
            )}

            <Text variant="labelMedium" color="textHint" style={styles.helperText}>
                {t('listings.form.locationHelperText')}
            </Text>
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        gap: SPACING.md,
    },
    sectionTitle: {
        // Typography géré par le composant Text
    },
    locationCard: {
        overflow: 'hidden',
    },
    cardContent: {
        padding: SPACING.lg,
        gap: SPACING.lg,
    },
    locationInfo: {
        flexDirection: 'row',
        alignItems: 'flex-start',
        gap: SPACING.md,
    },
    locationText: {
        flex: 1,
        gap: SPACING.xs,
    },
    locationLabel: {
        // Typography géré par le composant Text
    },
    locationValue: {
        // Typography géré par le composant Text
    },
    locationActions: {
        flexDirection: 'row',
        gap: SPACING.sm,
        flexWrap: 'wrap',
    },
    actionButton: {
        flex: 1,
        minWidth: 120,
    },
    mapCard: {
        overflow: 'hidden',
    },
    mapContent: {
        padding: SPACING.lg,
        gap: SPACING.md,
    },
    mapInstructions: {
        textAlign: 'center',
        fontStyle: 'italic',
    },
    mapContainer: {
        borderRadius: BORDER_RADIUS.lg,
        overflow: 'hidden',
        position: 'relative',
        elevation: ELEVATION.low,
    },
    map: {
        width: '100%',
        height: '100%',
    },
    mapLoading: {
        position: 'absolute',
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        justifyContent: 'center',
        alignItems: 'center',
        gap: SPACING.md,
    },
    mapLoadingText: {
        // Typography géré par le composant Text
    },
    helperText: {
        textAlign: 'center',
        paddingHorizontal: SPACING.xs,
        fontStyle: 'italic',
    },
});

export default LocationPicker;