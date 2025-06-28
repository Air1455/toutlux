// components/CurrencyPicker.js
import React, { useState } from 'react';
import {
    View,
    StyleSheet,
    TouchableOpacity,
    Modal,
    FlatList,
    SectionList,
} from 'react-native';
import { useTheme, Searchbar, Button } from 'react-native-paper';
import { MaterialIcons } from '@expo/vector-icons';
import { useTranslation } from 'react-i18next';

import Text from '@/components/typography/Text';
import {
    CURRENCIES_BY_REGION,
    POPULAR_CURRENCIES,
    GLOBAL_CURRENCIES,
    searchCurrencies
} from '@/utils/currencyUtils';
import { SPACING, BORDER_RADIUS, ELEVATION } from '@/constants/spacing';

const CurrencyPicker = ({
                            selectedCurrency,
                            onSelect,
                            disabled = false,
                            error = false,
                            style = {}
                        }) => {
    const { colors } = useTheme();
    const { t } = useTranslation();
    const [modalVisible, setModalVisible] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [showPopularOnly, setShowPopularOnly] = useState(true);

    const selectedCurrencyInfo = GLOBAL_CURRENCIES[selectedCurrency];

    // Filtrage des devises selon la recherche
    const filteredCurrencies = searchQuery
        ? searchCurrencies(searchQuery)
        : showPopularOnly
            ? POPULAR_CURRENCIES.map(code => ({ code, ...GLOBAL_CURRENCIES[code] }))
            : null; // On utilisera les sections par région

    const handleCurrencySelect = (currencyCode) => {
        onSelect(currencyCode);
        setModalVisible(false);
        setSearchQuery('');
        setShowPopularOnly(true);
    };

    const handleModalClose = () => {
        setModalVisible(false);
        setSearchQuery('');
        setShowPopularOnly(true);
    };

    const renderCurrencyItem = ({ item }) => (
        <TouchableOpacity
            style={[
                styles.currencyItem,
                { borderBottomColor: colors.outline }
            ]}
            onPress={() => handleCurrencySelect(item.code)}
        >
            <View style={styles.currencyInfo}>
                <Text variant="labelLarge" color="textPrimary" style={styles.currencyCode}>
                    {item.code}
                </Text>
                <Text variant="bodyLarge" color="primary" style={styles.currencySymbol}>
                    {item.symbol}
                </Text>
            </View>
            <View style={styles.currencyDetails}>
                <Text variant="bodyLarge" color="textPrimary" style={styles.currencyName}>
                    {item.name}
                </Text>
                <Text variant="labelMedium" color="textSecondary" style={styles.currencyRegion}>
                    {item.region}
                </Text>
            </View>
            {selectedCurrency === item.code && (
                <MaterialIcons
                    name="check"
                    size={24}
                    color={colors.primary}
                />
            )}
        </TouchableOpacity>
    );

    const renderSectionList = () => {
        if (searchQuery || showPopularOnly) {
            return (
                <FlatList
                    data={filteredCurrencies}
                    renderItem={renderCurrencyItem}
                    keyExtractor={(item) => item.code}
                    style={styles.currencyList}
                    showsVerticalScrollIndicator={false}
                    ListEmptyComponent={
                        <View style={styles.emptyContainer}>
                            <Text variant="bodyLarge" color="textSecondary" style={styles.emptyText}>
                                {searchQuery ? t('listings.form.noCurrencyFound') : t('listings.form.noPopularCurrency')}
                            </Text>
                        </View>
                    }
                />
            );
        }

        // Affichage par sections pour toutes les devises
        const sections = Object.entries(CURRENCIES_BY_REGION)
            .filter(([title]) => title !== 'Popular') // Exclure Popular car on a un toggle
            .map(([title, currencies]) => ({
                title,
                data: currencies
            }));

        return (
            <SectionList
                sections={sections}
                renderItem={renderCurrencyItem}
                renderSectionHeader={({ section: { title } }) => (
                    <View style={[
                        styles.sectionHeader,
                        {
                            backgroundColor: colors.surface,
                            borderRadius: BORDER_RADIUS.xs
                        }
                    ]}>
                        <Text variant="labelLarge" color="primary" style={styles.sectionTitle}>
                            {title}
                        </Text>
                    </View>
                )}
                keyExtractor={(item) => item.code}
                style={styles.currencyList}
                showsVerticalScrollIndicator={false}
                stickySectionHeadersEnabled={true}
            />
        );
    };

    return (
        <View style={[styles.container, style]}>
            <TouchableOpacity
                style={[
                    styles.selector,
                    {
                        backgroundColor: colors.surface,
                        borderColor: error ? colors.error : colors.outline,
                        borderRadius: BORDER_RADIUS.md,
                    },
                    disabled && { opacity: 0.6 }
                ]}
                onPress={() => setModalVisible(true)}
                disabled={disabled}
            >
                <View style={styles.selectedCurrency}>
                    {selectedCurrencyInfo ? (
                        <>
                            <Text variant="bodyLarge" color="primary" style={styles.selectedSymbol}>
                                {selectedCurrencyInfo.symbol}
                            </Text>
                            <Text variant="bodyLarge" color="textPrimary" style={styles.selectedCode}>
                                {selectedCurrency}
                            </Text>
                            <Text variant="bodyMedium" color="textSecondary" style={styles.selectedName}>
                                - {selectedCurrencyInfo.name}
                            </Text>
                        </>
                    ) : (
                        <Text variant="bodyLarge" color="textHint" style={styles.placeholder}>
                            {t('listings.form.selectCurrency')}
                        </Text>
                    )}
                </View>
                <MaterialIcons
                    name="arrow-drop-down"
                    size={24}
                    color={colors.textSecondary}
                />
            </TouchableOpacity>

            <Modal
                visible={modalVisible}
                animationType="slide"
                presentationStyle="pageSheet"
                onRequestClose={handleModalClose}
            >
                <View style={[styles.modal, { backgroundColor: colors.background }]}>
                    {/* Header */}
                    <View style={[
                        styles.modalHeader,
                        {
                            borderBottomColor: colors.outline,
                            paddingHorizontal: SPACING.lg,
                            paddingVertical: SPACING.lg
                        }
                    ]}>
                        <Text variant="pageTitle" color="textPrimary" style={styles.modalTitle}>
                            {t('listings.form.selectCurrency')}
                        </Text>
                        <Button
                            onPress={handleModalClose}
                            icon="close"
                            style={{ borderRadius: BORDER_RADIUS.md }}
                        >
                            {t('common.close')}
                        </Button>
                    </View>

                    {/* Search */}
                    <View style={styles.searchContainer}>
                        <Searchbar
                            placeholder={t('listings.form.searchCurrency')}
                            onChangeText={setSearchQuery}
                            value={searchQuery}
                            style={[
                                styles.searchBar,
                                { borderRadius: BORDER_RADIUS.md }
                            ]}
                            icon="magnify"
                            clearIcon="close"
                        />
                    </View>

                    {/* Toggle buttons */}
                    {!searchQuery && (
                        <View style={styles.toggleContainer}>
                            <Button
                                mode={showPopularOnly ? 'contained' : 'outlined'}
                                onPress={() => setShowPopularOnly(true)}
                                style={[
                                    styles.toggleButton,
                                    { borderRadius: BORDER_RADIUS.md }
                                ]}
                                compact
                                icon="star"
                            >
                                {t('common.popular')}
                            </Button>
                            <Button
                                mode={!showPopularOnly ? 'contained' : 'outlined'}
                                onPress={() => setShowPopularOnly(false)}
                                style={[
                                    styles.toggleButton,
                                    { borderRadius: BORDER_RADIUS.md }
                                ]}
                                compact
                                icon="earth"
                            >
                                {t('common.all')}
                            </Button>
                        </View>
                    )}

                    {/* Currency List */}
                    {renderSectionList()}
                </View>
            </Modal>
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        marginTop: SPACING.sm,
    },
    selector: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: SPACING.lg,
        paddingVertical: SPACING.md,
        borderWidth: 1,
        minHeight: 48,
        elevation: ELEVATION.low,
    },
    selectedCurrency: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.xs,
        flex: 1,
    },
    selectedSymbol: {
        minWidth: 30,
    },
    selectedCode: {
        // Typography géré par le composant Text
    },
    selectedName: {
        flex: 1,
    },
    placeholder: {
        // Typography géré par le composant Text
    },
    modal: {
        flex: 1,
    },
    modalHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        borderBottomWidth: 1,
    },
    modalTitle: {
        // Typography géré par le composant Text
    },
    searchContainer: {
        paddingHorizontal: SPACING.lg,
        paddingVertical: SPACING.lg,
    },
    searchBar: {
        elevation: 0,
        backgroundColor: 'transparent',
    },
    toggleContainer: {
        flexDirection: 'row',
        paddingHorizontal: SPACING.lg,
        gap: SPACING.md,
        marginBottom: SPACING.lg,
    },
    toggleButton: {
        flex: 1,
    },
    currencyList: {
        flex: 1,
        paddingHorizontal: SPACING.lg,
    },
    currencyItem: {
        flexDirection: 'row',
        alignItems: 'center',
        paddingVertical: SPACING.lg,
        borderBottomWidth: 1,
        gap: SPACING.lg,
    },
    currencyInfo: {
        alignItems: 'center',
        minWidth: 60,
    },
    currencyCode: {
        // Typography géré par le composant Text
    },
    currencySymbol: {
        marginTop: 2,
    },
    currencyDetails: {
        flex: 1,
    },
    currencyName: {
        // Typography géré par le composant Text
    },
    currencyRegion: {
        marginTop: 2,
    },
    sectionHeader: {
        paddingVertical: SPACING.sm,
        paddingHorizontal: SPACING.xs,
        marginVertical: SPACING.xs,
    },
    sectionTitle: {
        // Typography géré par le composant Text
    },
    emptyContainer: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
        paddingVertical: SPACING.huge,
    },
    emptyText: {
        textAlign: 'center',
    },
});

export default CurrencyPicker;