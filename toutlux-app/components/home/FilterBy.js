import { StyleSheet, View } from "react-native";
import CustomButton from "@components/CustomButton";
import { useDispatch, useSelector } from "react-redux";
import { setFilters } from "@/redux/houseFilterReducer";
import { useTranslation } from "react-i18next";
import {memo, useCallback} from "react";

const FilterBy = () => {
    const dispatch = useDispatch();
    const { filters } = useSelector(state => state.houseFilter);
    const { t } = useTranslation();

    const { viewMode = 'list' } = filters;
    const selectedTypes = filters.types || ['house', 'hotel'];

    const toggleType = useCallback((type) => {
        const newTypes = selectedTypes.includes(type)
            ? selectedTypes.filter(t => t !== type)
            : [...selectedTypes, type];
        dispatch(setFilters({ types: newTypes }));
    }, [selectedTypes, dispatch]);

    const toggleViewMode = useCallback(() => {
        const newViewMode = viewMode === 'list' ? 'map' : 'list';
        dispatch(setFilters({ viewMode: newViewMode }));
    }, [viewMode, dispatch]);

    return (
        <View style={styles.btnContainer}>
            <CustomButton
                content={t('filterBy.house')}
                onPress={() => toggleType('house')}
                radius="rounded"
                variant={selectedTypes.includes('house') ? 'yellow' : 'default'}
            />
            <CustomButton
                content={t('filterBy.hotel')}
                onPress={() => toggleType('hotel')}
                radius="rounded"
                variant={selectedTypes.includes('hotel') ? 'yellow' : 'default'}
            />
            <CustomButton
                content={t(viewMode === 'list' ? 'filterBy.viewMap' : 'filterBy.viewList')}
                iconName="map"
                radius="rounded"
                variant="yellow"
                onPress={toggleViewMode}
            />
        </View>
    );
};

const styles = StyleSheet.create({
    btnContainer: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        gap: 8,
    },
});

export default memo(FilterBy);