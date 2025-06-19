import React, { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { ActivityIndicator, FlatList, StyleSheet, Text, View } from 'react-native';
import { useTheme } from 'react-native-paper';
import { useDispatch, useSelector } from 'react-redux';

import FilterBy from '@/components/home/FilterBy';
import HouseCard from '@/components/home/HouseCard';
import PopularTogoDestinations from '@/components/home/PopularTogoDestinations';
import SearchBar from '@/components/home/SearchBar';
import MapContainer from "@/components/home/MapContainer";
import { useGetHousesQuery } from '@/redux/api/houseApi';
import { setAllHouses } from '@/redux/houseFilterReducer';
import {LinearGradient} from "expo-linear-gradient";
import {SafeScreen} from "@components/layout/SafeScreen";

const HomeScreen = () => {
    const { colors } = useTheme();
    const dispatch = useDispatch();
    const { t } = useTranslation();
    const { data: houses, error, isLoading, refetch } = useGetHousesQuery();
    const filteredHouses = useSelector(state => state.houseFilter.filteredHouses);
    const viewMode = useSelector(state => state.houseFilter.filters.viewMode);
    const [refreshing, setRefreshing] = React.useState(false);

    useEffect(() => {
        if (houses?.length) {
            dispatch(setAllHouses(houses));
        }
    }, [houses, dispatch]);


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

    const renderHeader = React.useCallback(() => (
        filteredHouses.length === houses?.length ? (
            <>
                <View style={styles.groupContainer}>
                    <Text style={[styles.subtitle, { color: colors.text }]}>
                        {t('home.popularTogo')}
                    </Text>
                    <PopularTogoDestinations />
                </View>
                <View style={styles.groupContainer}>
                    <Text style={[styles.subtitle, { color: colors.text, marginBottom: -8 }]}>
                        {t('home.topPicksAfrica')}
                    </Text>
                </View>
            </>
        ) : (
            <Text style={[styles.subtitle, { color: colors.text }]}>
                {t('home.listResults')}
            </Text>
        )
    ), [filteredHouses.length, houses?.length, colors.text, t]);

    const renderFooter = React.useCallback(() => (
        isLoading ? (
            <View style={styles.footerLoader}>
                <ActivityIndicator size="large" color={colors.primary} />
            </View>
        ) : null
    ), [isLoading, colors.primary]);

    return (
        <SafeScreen>
            <LinearGradient colors={[colors.background, colors.surface]} style={styles.container}>
                <View style={[styles.groupContainer]}>
                    <SearchBar />
                </View>
                <View style={styles.groupContainer}>
                    <Text style={[styles.subtitle, { color: colors.text }]}>
                        {t('home.filterBy')}
                    </Text>
                    <FilterBy />
                </View>
                {viewMode === 'map' ? (
                    <View style={[styles.groupContainer, { flex: 1 }]}>
                        <MapContainer houses={filteredHouses} />
                    </View>
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
                        removeClippedSubviews
                        contentContainerStyle={styles.flatListContent}
                    />
                )}
            </LinearGradient>
        </SafeScreen>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        paddingHorizontal: 16,
        paddingBottom: 8,
    },
    groupContainer: {
        marginVertical: 10,
    },
    subtitle: {
        fontSize: 16,
        fontFamily: 'Prompt_800ExtraBold',
        fontWeight: '600',
        lineHeight: 24,
        marginBottom: 8,
    },
    footerLoader: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
    },
    flatListContent: {
        paddingBottom: 20,
    },
});

export default React.memo(HomeScreen);