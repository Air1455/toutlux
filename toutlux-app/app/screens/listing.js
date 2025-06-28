import React, { useState, useCallback } from 'react';
import {
    View,
    ScrollView,
    StyleSheet,
    Alert,
    Platform,
    KeyboardAvoidingView,
} from 'react-native';
import {
    TextInput,
    Button,
    Switch,
    useTheme,
    SegmentedButtons,
    Chip,
} from 'react-native-paper';
import { useForm, Controller } from 'react-hook-form';
import { yupResolver } from '@hookform/resolvers/yup';
import * as yup from 'yup';
import { useTranslation } from 'react-i18next';
import { useRouter } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';

import { SafeScreen } from '@components/layout/SafeScreen';
import { useAddHouseMutation } from '@/redux/api/houseApi';
import { useDocumentUpload } from '@/hooks/useDocumentUpload';
import LocationPicker from "@components/listing/LocationPicker";
import ImageUploadSection from "@components/listing/ImageUploadSection";
import Text from '@/components/typography/Text';
import { SPACING, BORDER_RADIUS } from '@/constants/spacing';

const createListingSchema = (t) => yup.object({
    shortDescription: yup.string()
        .required(t('validation.shortDescription.required'))
        .min(10, t('validation.shortDescription.min'))
        .max(100, t('validation.shortDescription.max')),
    longDescription: yup.string()
        .max(1000, t('validation.longDescription.max')),
    price: yup.number()
        .required(t('validation.price.required'))
        .positive(t('validation.price.positive'))
        .integer(t('validation.price.integer')),
    currency: yup.string()
        .required(t('validation.currency.required')),
    type: yup.string()
        .required(t('validation.type.required')),
    bedrooms: yup.number()
        .nullable()
        .positive(t('validation.bedrooms.positive'))
        .integer(t('validation.bedrooms.integer')),
    bathrooms: yup.number()
        .nullable()
        .positive(t('validation.bathrooms.positive'))
        .integer(t('validation.bathrooms.integer')),
    surface: yup.string()
        .nullable(),
    yearOfConstruction: yup.number()
        .nullable()
        .min(1800, t('validation.year.min'))
        .max(new Date().getFullYear(), t('validation.year.max')),
    address: yup.string()
        .required(t('validation.address.required')),
    city: yup.string()
        .required(t('validation.city.required')),
    country: yup.string()
        .required(t('validation.country.required')),
    isForRent: yup.boolean(),
    firstImage: yup.string()
        .required(t('validation.firstImage.required')),
});

const PROPERTY_TYPES = [
    { label: 'listings.types.apartment', value: 'apartment' },
    { label: 'listings.types.house', value: 'house' },
    { label: 'listings.types.villa', value: 'villa' },
    { label: 'listings.types.studio', value: 'studio' },
    { label: 'listings.types.loft', value: 'loft' },
    { label: 'listings.types.townhouse', value: 'townhouse' },
];

const CURRENCIES = [
    { label: 'EUR (€)', value: 'EUR' },
    { label: 'USD ($)', value: 'USD' },
    { label: 'XOF (CFA)', value: 'XOF' },
];

export default function CreateListingScreen() {
    const { colors } = useTheme();
    const { t } = useTranslation();
    const router = useRouter();

    const [addHouse, { isLoading: isSubmitting }] = useAddHouseMutation();
    const { getImageUrl } = useDocumentUpload();

    const [step, setStep] = useState(1);
    const totalSteps = 4;

    const schema = createListingSchema(t);

    const {
        control,
        handleSubmit,
        setValue,
        watch,
        trigger,
        formState: { errors },
    } = useForm({
        resolver: yupResolver(schema),
        defaultValues: {
            isForRent: false,
            currency: 'EUR',
            type: 'apartment',
            country: 'France',
            bedrooms: 1,
            bathrooms: 1,
            garages: 0,
            swimmingPools: 0,
            floors: 1,
            otherImages: [],
            location: { lat: 0, lng: 0 },
        },
    });

    const formData = watch();

    const nextStep = async () => {
        const fieldsToValidate = getFieldsForStep(step);
        const isValid = await trigger(fieldsToValidate);

        if (isValid && step < totalSteps) {
            setStep(step + 1);
        }
    };

    const prevStep = () => {
        if (step > 1) {
            setStep(step - 1);
        }
    };

    const getFieldsForStep = (stepNumber) => {
        switch (stepNumber) {
            case 1:
                return ['shortDescription', 'longDescription', 'type', 'isForRent'];
            case 2:
                return ['price', 'currency', 'bedrooms', 'bathrooms', 'surface', 'yearOfConstruction'];
            case 3:
                return ['address', 'city', 'country'];
            case 4:
                return ['firstImage'];
            default:
                return [];
        }
    };

    const onSubmit = async (data) => {
        try {
            const listingData = {
                ...data,
                otherImages: data.otherImages || [],
                location: data.location || { lat: 0, lng: 0 },
            };

            console.log('Creating listing with data:', listingData);

            await addHouse(listingData).unwrap();

            Alert.alert(
                t('common.success'),
                t('listings.createSuccess'),
                [
                    {
                        text: 'OK',
                        onPress: () => router.back(),
                    },
                ]
            );
        } catch (error) {
            console.error('Error creating listing:', error);
            Alert.alert(
                t('common.error'),
                error?.data?.message || t('listings.createError')
            );
        }
    };

    const renderStepContent = () => {
        switch (step) {
            case 1:
                return renderBasicInfoStep();
            case 2:
                return renderDetailsStep();
            case 3:
                return renderLocationStep();
            case 4:
                return renderImagesStep();
            default:
                return null;
        }
    };

    const renderBasicInfoStep = () => (
        <View style={styles.stepContent}>
            <Text variant="pageTitle" color="textPrimary" style={styles.stepTitle}>
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
                            value={value}
                            onChangeText={onChange}
                            placeholder={t('listings.form.shortDescriptionPlaceholder')}
                            error={!!errors.shortDescription}
                            maxLength={100}
                        />
                    )}
                />
                {errors.shortDescription && (
                    <Text variant="bodySmall" color="error" style={styles.errorText}>
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
                            value={value}
                            onChangeText={onChange}
                            placeholder={t('listings.form.longDescriptionPlaceholder')}
                            multiline
                            numberOfLines={4}
                            error={!!errors.longDescription}
                            maxLength={1000}
                        />
                    )}
                />
                {errors.longDescription && (
                    <Text variant="bodySmall" color="error" style={styles.errorText}>
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
                                >
                                    {t(type.label)}
                                </Chip>
                            ))}
                        </View>
                    )}
                />
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
    );

    const renderDetailsStep = () => (
        <View style={styles.stepContent}>
            <Text variant="pageTitle" color="textPrimary" style={styles.stepTitle}>
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
                                value={value?.toString()}
                                onChangeText={(text) => onChange(parseInt(text) || 0)}
                                placeholder="0"
                                keyboardType="numeric"
                                error={!!errors.price}
                            />
                        )}
                    />
                    {errors.price && (
                        <Text variant="bodySmall" color="error" style={styles.errorText}>
                            {errors.price.message}
                        </Text>
                    )}
                </View>

                <View style={[styles.field, styles.halfField]}>
                    <Text variant="labelLarge" color="textPrimary" style={styles.label}>
                        {t('listings.form.currency')}
                    </Text>
                    <Controller
                        control={control}
                        name="currency"
                        render={({ field: { onChange, value } }) => (
                            <SegmentedButtons
                                value={value}
                                onValueChange={onChange}
                                buttons={CURRENCIES.map(currency => ({
                                    value: currency.value,
                                    label: currency.label,
                                }))}
                                style={styles.segmentedButtons}
                            />
                        )}
                    />
                </View>
            </View>

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
                                value={value?.toString()}
                                onChangeText={(text) => onChange(parseInt(text) || null)}
                                placeholder="1"
                                keyboardType="numeric"
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
                                value={value?.toString()}
                                onChangeText={(text) => onChange(parseInt(text) || null)}
                                placeholder="1"
                                keyboardType="numeric"
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
                                value={value}
                                onChangeText={onChange}
                                placeholder="120 m²"
                                right={<TextInput.Affix text="m²" />}
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
                                value={value?.toString()}
                                onChangeText={(text) => onChange(parseInt(text) || null)}
                                placeholder={new Date().getFullYear().toString()}
                                keyboardType="numeric"
                                maxLength={4}
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
                                value={value?.toString()}
                                onChangeText={(text) => onChange(parseInt(text) || 0)}
                                placeholder="0"
                                keyboardType="numeric"
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
                                value={value?.toString()}
                                onChangeText={(text) => onChange(parseInt(text) || 0)}
                                placeholder="0"
                                keyboardType="numeric"
                            />
                        )}
                    />
                </View>
            </View>
        </View>
    );

    const renderLocationStep = () => (
        <View style={styles.stepContent}>
            <Text variant="pageTitle" color="textPrimary" style={styles.stepTitle}>
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
                            value={value}
                            onChangeText={onChange}
                            placeholder={t('listings.form.addressPlaceholder')}
                            error={!!errors.address}
                            multiline
                            numberOfLines={2}
                        />
                    )}
                />
                {errors.address && (
                    <Text variant="bodySmall" color="error" style={styles.errorText}>
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
                                value={value}
                                onChangeText={onChange}
                                placeholder={t('listings.form.cityPlaceholder')}
                                error={!!errors.city}
                            />
                        )}
                    />
                    {errors.city && (
                        <Text variant="bodySmall" color="error" style={styles.errorText}>
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
                                value={value}
                                onChangeText={onChange}
                                placeholder={t('listings.form.countryPlaceholder')}
                                error={!!errors.country}
                            />
                        )}
                    />
                    {errors.country && (
                        <Text variant="bodySmall" color="error" style={styles.errorText}>
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
    );

    const renderImagesStep = () => (
        <View style={styles.stepContent}>
            <Text variant="pageTitle" color="textPrimary" style={styles.stepTitle}>
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
    );

    return (
        <LinearGradient colors={[colors.background, colors.surface]} style={styles.container}>
            <KeyboardAvoidingView
                style={styles.keyboardView}
                behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
            >
                {/* Progress indicator */}
                <View style={styles.progressContainer}>
                    <Text variant="bodyMedium" color="textSecondary" style={styles.progressText}>
                        {t('listings.stepProgress', { current: step, total: totalSteps })}
                    </Text>
                    <View style={styles.progressBar}>
                        {Array.from({ length: totalSteps }, (_, index) => (
                            <View
                                key={index}
                                style={[
                                    styles.progressStep,
                                    {
                                        backgroundColor: index < step
                                            ? colors.primary
                                            : colors.surfaceVariant
                                    }
                                ]}
                            />
                        ))}
                    </View>
                </View>

                <ScrollView
                    style={styles.scrollView}
                    contentContainerStyle={styles.scrollContent}
                    showsVerticalScrollIndicator={false}
                    keyboardShouldPersistTaps="handled"
                >
                    {renderStepContent()}
                </ScrollView>

                {/* Navigation buttons */}
                <View style={[styles.navigation, { borderTopColor: colors.outline }]}>
                    <Button
                        mode="outlined"
                        onPress={step === 1 ? () => router.back() : prevStep}
                        style={styles.navButton}
                        disabled={isSubmitting}
                    >
                        {step === 1 ? t('common.cancel') : t('common.back')}
                    </Button>

                    <Button
                        mode="contained"
                        onPress={step === totalSteps ? handleSubmit(onSubmit) : nextStep}
                        style={styles.navButton}
                        loading={isSubmitting}
                        disabled={isSubmitting}
                    >
                        {step === totalSteps ? t('listings.create') : t('common.next')}
                    </Button>
                </View>
            </KeyboardAvoidingView>
        </LinearGradient>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    keyboardView: {
        flex: 1,
    },
    progressContainer: {
        paddingHorizontal: SPACING.xl,
        paddingVertical: SPACING.lg,
        alignItems: 'center',
        gap: SPACING.md,
    },
    progressText: {
        textAlign: 'center',
    },
    progressBar: {
        flexDirection: 'row',
        gap: SPACING.sm,
        width: '100%',
        maxWidth: 200,
    },
    progressStep: {
        flex: 1,
        height: 4,
        borderRadius: BORDER_RADIUS.xs,
    },
    scrollView: {
        flex: 1,
    },
    scrollContent: {
        paddingHorizontal: SPACING.xl,
        paddingBottom: SPACING.xl,
    },
    stepContent: {
        gap: SPACING.xl,
    },
    stepTitle: {
        textAlign: 'center',
        marginBottom: SPACING.md,
    },
    field: {
        gap: SPACING.sm,
    },
    halfField: {
        flex: 1,
    },
    row: {
        flexDirection: 'row',
        gap: SPACING.lg,
    },
    label: {
        marginBottom: SPACING.xs,
    },
    errorText: {
        marginTop: SPACING.xs,
    },
    typeSelector: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: SPACING.sm,
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
        textAlign: 'right',
    },
    segmentedButtons: {
        marginTop: SPACING.sm,
    },
    navigation: {
        flexDirection: 'row',
        paddingHorizontal: SPACING.xl,
        paddingVertical: SPACING.lg,
        gap: SPACING.md,
        borderTopWidth: 1,
    },
    navButton: {
        flex: 1,
        borderRadius: BORDER_RADIUS.md,
    },
});