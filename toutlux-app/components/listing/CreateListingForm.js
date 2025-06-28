// components/listing/CreateListingForm.js
import React from 'react';
import { View, StyleSheet } from 'react-native';
import {
    TextInput,
    Switch,
    Chip,
    useTheme,
} from 'react-native-paper';
import { Controller } from 'react-hook-form';
import { useTranslation } from 'react-i18next';

import ImageUploadSection from './ImageUploadSection';
import LocationPicker from './LocationPicker';
import CurrencyPicker from '../CurrencyPicker';
import Text from '@/components/typography/Text';
import { formatPrice } from '@/utils/currencyUtils';
import { SPACING, BORDER_RADIUS } from '@/constants/spacing';

const PROPERTY_TYPES = [
    { label: 'listings.types.apartment', value: 'apartment' },
    { label: 'listings.types.house', value: 'house' },
    { label: 'listings.types.villa', value: 'villa' },
    { label: 'listings.types.studio', value: 'studio' },
    { label: 'listings.types.loft', value: 'loft' },
    { label: 'listings.types.townhouse', value: 'townhouse' },
    { label: 'listings.types.duplex', value: 'duplex' },
    { label: 'listings.types.penthouse', value: 'penthouse' },
];

const CreateListingForm = ({ control, errors, setValue, watch, isEditMode = false }) => {
    const { colors } = useTheme();
    const { t } = useTranslation();

    const formData = watch();

    // Aperçu du prix formaté en temps réel
    const pricePreview = React.useMemo(() => {
        if (formData.price && formData.currency) {
            try {
                return formatPrice(formData.price, formData.currency, {
                    isRental: formData.isForRent,
                    locale: 'fr-FR'
                });
            } catch (error) {
                console.warn('Erreur formatage prix:', error);
                return `${formData.price} ${formData.currency}`;
            }
        }
        return '';
    }, [formData.price, formData.currency, formData.isForRent]);

    return (
        <View style={styles.container}>
            {/* Informations de base */}
            <View style={styles.section}>
                <Text variant="sectionTitle" color="textPrimary" style={styles.sectionTitle}>
                    {t('listings.steps.basicInfo')}
                </Text>

                <View style={styles.field}>
                    <Text variant="labelLarge" color="textPrimary" style={styles.label}>
                        {t('listings.form.shortDescription')} *
                    </Text>
                    <Controller
                        control={control}
                        name="shortDescription"
                        render={({ field: { onChange, value } }) => (
                            <TextInput
                                mode="outlined"
                                value={value || ''}
                                onChangeText={onChange}
                                placeholder={t('listings.form.shortDescriptionPlaceholder')}
                                error={!!errors.shortDescription}
                                maxLength={100}
                                multiline
                                numberOfLines={2}
                                style={styles.input}
                            />
                        )}
                    />
                    {errors.shortDescription && (
                        <Text variant="labelMedium" color="error" style={styles.errorText}>
                            {errors.shortDescription.message}
                        </Text>
                    )}
                </View>

                <View style={styles.field}>
                    <Text variant="labelLarge" color="textPrimary" style={styles.label}>
                        {t('listings.form.longDescription')}
                    </Text>
                    <Controller
                        control={control}
                        name="longDescription"
                        render={({ field: { onChange, value } }) => (
                            <TextInput
                                mode="outlined"
                                value={value || ''}
                                onChangeText={onChange}
                                placeholder={t('listings.form.longDescriptionPlaceholder')}
                                multiline
                                numberOfLines={4}
                                error={!!errors.longDescription}
                                maxLength={1000}
                                style={styles.input}
                            />
                        )}
                    />
                    {errors.longDescription && (
                        <Text variant="labelMedium" color="error" style={styles.errorText}>
                            {errors.longDescription.message}
                        </Text>
                    )}
                </View>

                <View style={styles.field}>
                    <Text variant="labelLarge" color="textPrimary" style={styles.label}>
                        {t('listings.form.propertyType')} *
                    </Text>
                    <Controller
                        control={control}
                        name="type"
                        render={({ field: { onChange, value } }) => (
                            <View style={styles.typeSelector}>
                                {PROPERTY_TYPES.map((type) => (
                                    <Chip
                                        key={type.value}
                                        selected={value === type.value}
                                        onPress={() => onChange(type.value)}
                                        style={styles.typeChip}
                                        showSelectedOverlay
                                    >
                                        {t(type.label)}
                                    </Chip>
                                ))}
                            </View>
                        )}
                    />
                    {errors.type && (
                        <Text variant="labelMedium" color="error" style={styles.errorText}>
                            {errors.type.message}
                        </Text>
                    )}
                </View>

                <View style={styles.field}>
                    <View style={styles.switchRow}>
                        <Text variant="labelLarge" color="textPrimary" style={styles.label}>
                            {t('listings.form.listingType')}
                        </Text>
                        <Controller
                            control={control}
                            name="isForRent"
                            render={({ field: { onChange, value } }) => (
                                <View style={styles.switchContainer}>
                                    <Text variant="bodyMedium" color="textPrimary" style={styles.switchLabel}>
                                        {value ? t('listings.forRent') : t('listings.forSale')}
                                    </Text>
                                    <Switch
                                        value={value}
                                        onValueChange={onChange}
                                    />
                                </View>
                            )}
                        />
                    </View>
                </View>
            </View>

            {/* Prix et détails */}
            <View style={styles.section}>
                <Text variant="sectionTitle" color="textPrimary" style={styles.sectionTitle}>
                    {t('listings.steps.details')}
                </Text>

                <View style={styles.row}>
                    <View style={[styles.field, styles.halfField]}>
                        <Text variant="labelLarge" color="textPrimary" style={styles.label}>
                            {t('listings.form.price')} *
                        </Text>
                        <Controller
                            control={control}
                            name="price"
                            render={({ field: { onChange, value } }) => (
                                <TextInput
                                    mode="outlined"
                                    value={value?.toString() || ''}
                                    onChangeText={(text) => {
                                        const numericValue = text.replace(/[^0-9]/g, '');
                                        onChange(numericValue ? parseInt(numericValue, 10) : 0);
                                    }}
                                    placeholder="0"
                                    keyboardType="numeric"
                                    error={!!errors.price}
                                    right={pricePreview ? <TextInput.Affix text="💰" /> : null}
                                    style={styles.input}
                                />
                            )}
                        />
                        {errors.price && (
                            <Text variant="labelMedium" color="error" style={styles.errorText}>
                                {errors.price.message}
                            </Text>
                        )}
                    </View>

                    <View style={[styles.field, styles.halfField]}>
                        <Text variant="labelLarge" color="textPrimary" style={styles.label}>
                            {t('listings.form.currency')} *
                        </Text>
                        <Controller
                            control={control}
                            name="currency"
                            render={({ field: { onChange, value } }) => (
                                <CurrencyPicker
                                    selectedCurrency={value}
                                    onSelect={onChange}
                                    error={!!errors.currency}
                                />
                            )}
                        />
                        {errors.currency && (
                            <Text variant="labelMedium" color="error" style={styles.errorText}>
                                {errors.currency.message}
                            </Text>
                        )}
                    </View>
                </View>

                {/* Aperçu du prix formaté */}
                {pricePreview && (
                    <View style={[styles.pricePreview, { backgroundColor: colors.primaryContainer }]}>
                        <Text variant="labelMedium" color="textSecondary" style={styles.pricePreviewLabel}>
                            {t('listings.form.pricePreview')}:
                        </Text>
                        <Text variant="priceCard" color="primary" style={styles.pricePreviewValue}>
                            {pricePreview}
                        </Text>
                    </View>
                )}

                <View style={styles.row}>
                    <View style={[styles.field, styles.halfField]}>
                        <Text variant="labelLarge" color="textPrimary" style={styles.label}>
                            {t('listings.form.bedrooms')}
                        </Text>
                        <Controller
                            control={control}
                            name="bedrooms"
                            render={({ field: { onChange, value } }) => (
                                <TextInput
                                    mode="outlined"
                                    value={value?.toString() || ''}
                                    onChangeText={(text) => {
                                        const numericValue = text.replace(/[^0-9]/g, '');
                                        onChange(numericValue ? parseInt(numericValue, 10) : null);
                                    }}
                                    placeholder="1"
                                    keyboardType="numeric"
                                    left={<TextInput.Icon icon="bed" />}
                                    style={styles.input}
                                />
                            )}
                        />
                    </View>

                    <View style={[styles.field, styles.halfField]}>
                        <Text variant="labelLarge" color="textPrimary" style={styles.label}>
                            {t('listings.form.bathrooms')}
                        </Text>
                        <Controller
                            control={control}
                            name="bathrooms"
                            render={({ field: { onChange, value } }) => (
                                <TextInput
                                    mode="outlined"
                                    value={value?.toString() || ''}
                                    onChangeText={(text) => {
                                        const numericValue = text.replace(/[^0-9]/g, '');
                                        onChange(numericValue ? parseInt(numericValue, 10) : null);
                                    }}
                                    placeholder="1"
                                    keyboardType="numeric"
                                    left={<TextInput.Icon icon="shower" />}
                                    style={styles.input}
                                />
                            )}
                        />
                    </View>
                </View>

                <View style={styles.row}>
                    <View style={[styles.field, styles.halfField]}>
                        <Text variant="labelLarge" color="textPrimary" style={styles.label}>
                            {t('listings.form.surface')}
                        </Text>
                        <Controller
                            control={control}
                            name="surface"
                            render={({ field: { onChange, value } }) => (
                                <TextInput
                                    mode="outlined"
                                    value={value || ''}
                                    onChangeText={onChange}
                                    placeholder="120"
                                    keyboardType="numeric"
                                    right={<TextInput.Affix text="m²" />}
                                    left={<TextInput.Icon icon="ruler" />}
                                    style={styles.input}
                                />
                            )}
                        />
                    </View>

                    <View style={[styles.field, styles.halfField]}>
                        <Text variant="labelLarge" color="textPrimary" style={styles.label}>
                            {t('listings.form.yearOfConstruction')}
                        </Text>
                        <Controller
                            control={control}
                            name="yearOfConstruction"
                            render={({ field: { onChange, value } }) => (
                                <TextInput
                                    mode="outlined"
                                    value={value?.toString() || ''}
                                    onChangeText={(text) => {
                                        const numericValue = text.replace(/[^0-9]/g, '');
                                        if (numericValue.length <= 4) {
                                            onChange(numericValue ? parseInt(numericValue, 10) : null);
                                        }
                                    }}
                                    placeholder={new Date().getFullYear().toString()}
                                    keyboardType="numeric"
                                    maxLength={4}
                                    left={<TextInput.Icon icon="calendar" />}
                                    style={styles.input}
                                />
                            )}
                        />
                    </View>
                </View>

                <View style={styles.row}>
                    <View style={[styles.field, styles.halfField]}>
                        <Text variant="labelLarge" color="textPrimary" style={styles.label}>
                            {t('listings.form.garages')}
                        </Text>
                        <Controller
                            control={control}
                            name="garages"
                            render={({ field: { onChange, value } }) => (
                                <TextInput
                                    mode="outlined"
                                    value={value?.toString() || ''}
                                    onChangeText={(text) => {
                                        const numericValue = text.replace(/[^0-9]/g, '');
                                        onChange(numericValue ? parseInt(numericValue, 10) : 0);
                                    }}
                                    placeholder="0"
                                    keyboardType="numeric"
                                    left={<TextInput.Icon icon="garage" />}
                                    style={styles.input}
                                />
                            )}
                        />
                    </View>

                    <View style={[styles.field, styles.halfField]}>
                        <Text variant="labelLarge" color="textPrimary" style={styles.label}>
                            {t('listings.form.swimmingPools')}
                        </Text>
                        <Controller
                            control={control}
                            name="swimmingPools"
                            render={({ field: { onChange, value } }) => (
                                <TextInput
                                    mode="outlined"
                                    value={value?.toString() || ''}
                                    onChangeText={(text) => {
                                        const numericValue = text.replace(/[^0-9]/g, '');
                                        onChange(numericValue ? parseInt(numericValue, 10) : 0);
                                    }}
                                    placeholder="0"
                                    keyboardType="numeric"
                                    left={<TextInput.Icon icon="pool" />}
                                    style={styles.input}
                                />
                            )}
                        />
                    </View>
                </View>

                <View style={styles.field}>
                    <Text variant="labelLarge" color="textPrimary" style={styles.label}>
                        {t('listings.form.floors')}
                    </Text>
                    <Controller
                        control={control}
                        name="floors"
                        render={({ field: { onChange, value } }) => (
                            <TextInput
                                mode="outlined"
                                value={value?.toString() || ''}
                                onChangeText={(text) => {
                                    const numericValue = text.replace(/[^0-9]/g, '');
                                    onChange(numericValue ? parseInt(numericValue, 10) : 1);
                                }}
                                placeholder="1"
                                keyboardType="numeric"
                                left={<TextInput.Icon icon="stairs" />}
                                style={[styles.input, styles.fullWidthInput]}
                            />
                        )}
                    />
                </View>
            </View>

            {/* Localisation */}
            <View style={styles.section}>
                <Text variant="sectionTitle" color="textPrimary" style={styles.sectionTitle}>
                    {t('listings.steps.location')}
                </Text>

                <View style={styles.field}>
                    <Text variant="labelLarge" color="textPrimary" style={styles.label}>
                        {t('listings.form.address')} *
                    </Text>
                    <Controller
                        control={control}
                        name="address"
                        render={({ field: { onChange, value } }) => (
                            <TextInput
                                mode="outlined"
                                value={value || ''}
                                onChangeText={onChange}
                                placeholder={t('listings.form.addressPlaceholder')}
                                error={!!errors.address}
                                multiline
                                numberOfLines={2}
                                left={<TextInput.Icon icon="map-marker" />}
                                style={styles.input}
                            />
                        )}
                    />
                    {errors.address && (
                        <Text variant="labelMedium" color="error" style={styles.errorText}>
                            {errors.address.message}
                        </Text>
                    )}
                </View>

                <View style={styles.row}>
                    <View style={[styles.field, styles.halfField]}>
                        <Text variant="labelLarge" color="textPrimary" style={styles.label}>
                            {t('listings.form.city')} *
                        </Text>
                        <Controller
                            control={control}
                            name="city"
                            render={({ field: { onChange, value } }) => (
                                <TextInput
                                    mode="outlined"
                                    value={value || ''}
                                    onChangeText={onChange}
                                    placeholder={t('listings.form.cityPlaceholder')}
                                    error={!!errors.city}
                                    left={<TextInput.Icon icon="city" />}
                                    style={styles.input}
                                />
                            )}
                        />
                        {errors.city && (
                            <Text variant="labelMedium" color="error" style={styles.errorText}>
                                {errors.city.message}
                            </Text>
                        )}
                    </View>

                    <View style={[styles.field, styles.halfField]}>
                        <Text variant="labelLarge" color="textPrimary" style={styles.label}>
                            {t('listings.form.country')} *
                        </Text>
                        <Controller
                            control={control}
                            name="country"
                            render={({ field: { onChange, value } }) => (
                                <TextInput
                                    mode="outlined"
                                    value={value || ''}
                                    onChangeText={onChange}
                                    placeholder={t('listings.form.countryPlaceholder')}
                                    error={!!errors.country}
                                    left={<TextInput.Icon icon="flag" />}
                                    style={styles.input}
                                />
                            )}
                        />
                        {errors.country && (
                            <Text variant="labelMedium" color="error" style={styles.errorText}>
                                {errors.country.message}
                            </Text>
                        )}
                    </View>
                </View>

                <LocationPicker
                    location={formData.location}
                    onLocationChange={(location) => setValue('location', location)}
                    address={formData.address}
                    city={formData.city}
                    country={formData.country}
                />
            </View>

            {/* Images */}
            <View style={styles.section}>
                <Text variant="sectionTitle" color="textPrimary" style={styles.sectionTitle}>
                    {t('listings.steps.images')}
                </Text>

                <ImageUploadSection
                    firstImage={formData.firstImage}
                    otherImages={formData.otherImages}
                    onFirstImageChange={(url) => setValue('firstImage', url)}
                    onOtherImagesChange={(urls) => setValue('otherImages', urls)}
                    error={errors.firstImage?.message}
                />
            </View>
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        paddingHorizontal: SPACING.lg,
        gap: SPACING.xxxl,
    },
    section: {
        gap: SPACING.lg,
    },
    sectionTitle: {
        marginBottom: SPACING.sm,
    },
    field: {
        gap: SPACING.sm,
    },
    halfField: {
        flex: 1,
    },
    row: {
        flexDirection: 'row',
        gap: SPACING.md,
    },
    label: {
        marginBottom: SPACING.xs,
    },
    input: {
        borderRadius: BORDER_RADIUS.md,
    },
    errorText: {
        marginTop: SPACING.xs,
    },
    typeSelector: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: SPACING.sm,
        marginTop: SPACING.sm,
    },
    typeChip: {
        marginBottom: SPACING.sm,
    },
    switchRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
    },
    switchContainer: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.md,
    },
    switchLabel: {
        // Typography géré par le composant Text
    },
    pricePreview: {
        padding: SPACING.md,
        borderRadius: BORDER_RADIUS.md,
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.sm,
        marginTop: SPACING.sm,
    },
    pricePreviewLabel: {
        // Typography géré par le composant Text
    },
    pricePreviewValue: {
        // Typography géré par le composant Text
    },
    fullWidthInput: {
        width: '100%',
    },
});

export default CreateListingForm;