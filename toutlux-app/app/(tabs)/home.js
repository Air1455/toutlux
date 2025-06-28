import React, { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { ActivityIndicator, FlatList, StyleSheet, View } from 'react-native';
import { useTheme } from 'react-native-paper';
import { useDispatch, useSelector } from 'react-redux';
import { LinearGradient } from "expo-linear-gradient";

import FilterBy from '@/components/home/FilterBy';
import HouseCard from '@/components/home/HouseCard';
import PopularTogoDestinations from '@/components/home/PopularTogoDestinations';
import SearchBar from '@/components/home/SearchBar';
import MapContainer from "@/components/home/MapContainer";
import Text from '@/components/typography/Text';
import { SafeScreen } from "@components/layout/SafeScreen";

import { useGetHousesQuery } from '@/redux/api/houseApi';
import { setAllHouses } from '@/redux/houseFilterReducer';
import { SPACING, ELEVATION } from '@/constants/spacing';
import {LoadingScreen} from "@components/Loading";

const HomeScreen = () => {
    const { colors } = useTheme();
    const dispatch = useDispatch();
    const { t } = useTranslation();

    // ✅ Ajout de refetchOnMountOrArgChange et refetchOnFocus
    const { data: houses, error, isLoading, refetch, isFetching } = useGetHousesQuery(undefined, {
        refetchOnMountOrArgChange: true,
        refetchOnFocus: true,
    });

    const filteredHouses = useSelector(state => state.houseFilter.filteredHouses);
    const viewMode = useSelector(state => state.houseFilter.filters.viewMode);

    const [refreshing, setRefreshing] = React.useState(false);

    useEffect(() => {
        if (houses?.data?.length) {
            dispatch(setAllHouses(houses?.data));
        }
    }, [houses, dispatch]);

    // ✅ Ajout d'un useEffect pour forcer le refetch si pas de données après le montage
    useEffect(() => {
        // Si le composant est monté, pas de loading, pas de données et pas d'erreur, on refetch
        const timer = setTimeout(() => {
            if (!isLoading && !houses?.data?.length && !error && !isFetching) {
                console.log('🔄 No data after mount, forcing refetch...');
                refetch();
            }
        }, 1000); // Attendre 1 seconde après le montage

        return () => clearTimeout(timer);
    }, []); // Exécuté une seule fois au montage

    const onRefresh = React.useCallback(() => {
        setRefreshing(true);
        refetch().finally(() => setRefreshing(false));
    }, [refetch]);

    const renderItem = React.useCallback(({ item }) => (
        <HouseCard house={item} />
    ), []);

    const getItemLayout = React.useCallback((_, index) => ({
        length: 120,
        offset: 120 * index,
        index
    }), []);

    // Header de la FlatList (sans search et filter)
    const renderHeader = React.useCallback(() => {
        const isShowingAllResults = filteredHouses.length === houses?.data?.length;

        return (
            <View style={styles.listHeaderContainer}>
                {/* Sections conditionnelles */}
                {isShowingAllResults ? (
                    <>
                        {/* Destinations populaires du Togo */}
                        <View style={styles.destinationsSection}>
                            <Text variant="sectionTitle" color="textPrimary" style={styles.sectionTitle}>
                                {t('home.popularTogo')}
                            </Text>
                            <PopularTogoDestinations />
                        </View>

                        {/* En-tête des recommandations */}
                        <View style={styles.picksSection}>
                            <Text variant="sectionTitle" color="textPrimary" style={[styles.sectionTitle, styles.picksTitle]}>
                                {t('home.topPicksAfrica')}
                            </Text>
                            <Text variant="bodyMedium" color="textSecondary" style={styles.picksSubtitle}>
                                {t('home.topPicksSubtitle')}
                            </Text>
                        </View>
                    </>
                ) : (
                    /* Résultats de recherche/filtrage */
                    <View style={styles.resultsSection}>
                        <Text variant="sectionTitle" color="textPrimary" style={styles.sectionTitle}>
                            {t('home.listResults')}
                        </Text>
                        <Text variant="bodyMedium" color="textSecondary" style={styles.resultsCount}>
                            {filteredHouses.length} {filteredHouses.length <= 1 ? t('home.propertyFound') : t('home.propertiesFound')}
                        </Text>
                    </View>
                )}
            </View>
        );
    }, [filteredHouses.length, houses?.data?.length, colors, t]);

    const renderFooter = React.useCallback(() => (
        isFetching && !isLoading ? (
            <View style={styles.footerLoader}>
                <ActivityIndicator size="small" color={colors.primary} />
            </View>
        ) : null
    ), [isFetching, isLoading, colors.primary]);

    const renderEmptyState = () => (
        <View style={styles.emptyContainer}>
            <View style={styles.emptyIconContainer}>
                <Text variant="heroTitle" style={styles.emptyIcon}>🏠</Text>
            </View>
            <Text variant="pageTitle" color="textPrimary" style={styles.emptyTitle}>
                {t('home.emptyState.title')}
            </Text>
            <Text variant="bodyLarge" color="textSecondary" style={styles.emptyDescription}>
                {t('home.emptyState.description')}
            </Text>
        </View>
    );

    // Composant de loader pour la liste uniquement
    const renderListLoader = () => (
        <View style={styles.listLoader}>
            <ActivityIndicator size="large" color={colors.primary} />
            <Text variant="bodyLarge" color="textPrimary" style={styles.loadingText}>
                {t('common.loading')}
            </Text>
        </View>
    );

    return (
        <SafeScreen>
            <LinearGradient colors={[colors.background, colors.surface]} style={styles.container}>
                {viewMode === 'map' ? (
                    <View style={styles.mapContainer}>
                        {/* En-tête fixe pour la vue carte */}
                        <View style={styles.mapHeader}>
                            <SearchBar />
                            <FilterBy />
                        </View>

                        {/* Conteneur de la carte */}
                        <View style={styles.mapContent}>
                            {isLoading && !houses?.data?.length ? (
                                renderListLoader()
                            ) : (
                                <MapContainer houses={filteredHouses} />
                            )}
                        </View>
                    </View>
                ) : (
                    <View style={styles.listContainer}>
                        {/* Sections fixes - toujours affichées */}
                        <View style={styles.fixedHeader}>
                            {/* Section de recherche */}
                            <View style={styles.searchSection}>
                                <SearchBar />
                            </View>

                            {/* Section de filtres */}
                            <View style={styles.filterSection}>
                                <Text variant="sectionTitle" color="textPrimary" style={styles.sectionTitle}>
                                    {t('home.filterBy')}
                                </Text>
                                <FilterBy />
                            </View>
                        </View>

                        {/* Zone de la liste - avec loader conditionnel */}
                        {isLoading && !houses?.data?.length ? (
                            renderListLoader()
                        ) : (
                            <FlatList
                                data={filteredHouses}
                                keyExtractor={item => item.id.toString()}
                                onRefresh={onRefresh}
                                refreshing={refreshing}
                                renderItem={renderItem}
                                initialNumToRender={10}
                                maxToRenderPerBatch={10}
                                windowSize={5}
                                getItemLayout={getItemLayout}
                                ListHeaderComponent={renderHeader}
                                ListFooterComponent={renderFooter}
                                ListEmptyComponent={renderEmptyState}
                                removeClippedSubviews
                                contentContainerStyle={[
                                    styles.flatListContent,
                                    filteredHouses.length === 0 && styles.emptyListContent
                                ]}
                                showsVerticalScrollIndicator={false}
                            />
                        )}
                    </View>
                )}
            </LinearGradient>
        </SafeScreen>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    centered: {
        justifyContent: 'center',
        alignItems: 'center',
    },
    // Nouveau conteneur pour la vue liste
    listContainer: {
        flex: 1,
    },
    // Header fixe contenant search et filter
    fixedHeader: {
        paddingHorizontal: SPACING.lg,
        paddingBottom: SPACING.sm,
        backgroundColor: 'transparent',
        elevation: ELEVATION.low,
        shadowColor: '#000',
        shadowOffset: {
            width: 0,
            height: 2,
        },
        shadowOpacity: 0.1,
        shadowRadius: 3.84,
        zIndex: 1,
        gap: SPACING.md
    },
    searchSection: {
        marginVertical: SPACING.md,
    },
    filterSection: {
        marginBottom: SPACING.md,
    },
    // Header de la FlatList (sans search et filter)
    listHeaderContainer: {
        paddingHorizontal: SPACING.lg,
        paddingTop: SPACING.md,
        paddingBottom: SPACING.sm,
    },
    destinationsSection: {
        marginBottom: SPACING.lg,
    },
    picksSection: {
        marginBottom: SPACING.md,
    },
    resultsSection: {
        marginBottom: SPACING.md,
    },
    sectionTitle: {
        marginBottom: SPACING.sm,
    },
    picksTitle: {
        marginBottom: SPACING.xs,
    },
    picksSubtitle: {
        marginBottom: SPACING.sm,
    },
    resultsCount: {
        marginTop: SPACING.xs,
    },
    flatListContent: {
        flexGrow: 1,
        paddingBottom: SPACING.xl,
    },
    emptyListContent: {
        justifyContent: 'center',
    },
    footerLoader: {
        paddingVertical: SPACING.md,
        alignItems: 'center',
    },
    // Nouveau style pour le loader de la liste uniquement
    listLoader: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
        paddingVertical: SPACING.xxl,
        minHeight: 300, // Hauteur minimale pour un bon aspect visuel
    },
    loadingText: {
        marginTop: SPACING.md,
        textAlign: 'center',
    },
    emptyContainer: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
        paddingHorizontal: SPACING.xxl,
        paddingVertical: SPACING.huge,
        minHeight: 400, // Hauteur minimale pour éviter le rétrécissement
    },
    emptyIconContainer: {
        marginBottom: SPACING.lg,
        alignItems: 'center',
        justifyContent: 'center',
        minHeight: 80, // Container fixe pour l'icône
        minWidth: 80,
    },
    emptyIcon: {
        fontSize: 64,
        lineHeight: 80, // Important : définit la hauteur de ligne
        textAlign: 'center',
        includeFontPadding: false, // Android uniquement
        textAlignVertical: 'center', // Android uniquement
    },
    emptyTitle: {
        textAlign: 'center',
        marginBottom: SPACING.md,
    },
    emptyDescription: {
        textAlign: 'center',
        lineHeight: 24,
        maxWidth: 300, // Limite la largeur pour une meilleure lisibilité
    },
    // Styles pour la vue carte
    mapContainer: {
        flex: 1,
    },
    mapHeader: {
        paddingHorizontal: SPACING.lg,
        paddingVertical: SPACING.md,
        elevation: ELEVATION.low,
        gap: SPACING.md,
    },
    mapContent: {
        flex: 1,
    },
});

export default React.memo(HomeScreen);