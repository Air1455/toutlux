import React, { useState } from 'react';
import {
    View,
    ScrollView,
    StyleSheet,
    Alert,
    Platform,
    KeyboardAvoidingView,
} from 'react-native';
import {
    Button,
    useTheme,
    ActivityIndicator,
} from 'react-native-paper';
import { useForm } from 'react-hook-form';
import { yupResolver } from '@hookform/resolvers/yup';
import * as yup from 'yup';
import { useTranslation } from 'react-i18next';
import { useRouter } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';

import { SafeScreen } from '@components/layout/SafeScreen';
import { useAddHouseMutation } from '@/redux/api/houseApi';
import CreateListingForm from "@components/listing/CreateListingForm";
import { GLOBAL_CURRENCIES, getDefaultCurrency } from '@/utils/currencyUtils';
import Text from '@/components/typography/Text';
import { SPACING, BORDER_RADIUS } from '@/constants/spacing';

// Fonction pour détecter le pays par défaut
const getDefaultCountry = (locale = 'fr-FR') => {
    const countryByLocale = {
        'fr-CI': 'Côte d\'Ivoire',
        'fr-SN': 'Sénégal',
        'fr-ML': 'Mali',
        'fr-BF': 'Burkina Faso',
        'fr-CM': 'Cameroun',
        'fr-GA': 'Gabon',
        'en-GH': 'Ghana',
        'en-NG': 'Nigeria',
        'ar-MA': 'Maroc',
        'ar-TN': 'Tunisie',
        'en-ZA': 'Afrique du Sud',
        'en-KE': 'Kenya',
        'fr-FR': 'France',
        'en-US': 'États-Unis',
        'en-GB': 'Royaume-Uni',
    };

    return countryByLocale[locale] || 'Togo'; // Togo par défaut selon votre localisation
};

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
        .required(t('validation.currency.required'))
        .test('valid-currency', t('validation.currency.invalid'), (value) => {
            return value && GLOBAL_CURRENCIES.hasOwnProperty(value);
        }),
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

export default function CreateListingScreen() {
    const { colors } = useTheme();
    const { t, i18n } = useTranslation();
    const router = useRouter();

    const [addHouse, { isLoading: isSubmitting }] = useAddHouseMutation();

    const schema = createListingSchema(t);

    // Détection intelligente des valeurs par défaut
    const defaultCurrency = getDefaultCurrency(i18n.language, null);
    const defaultCountry = getDefaultCountry(i18n.language);

    const {
        control,
        handleSubmit,
        setValue,
        watch,
        formState: { errors, isDirty },
    } = useForm({
        resolver: yupResolver(schema),
        defaultValues: {
            isForRent: false,
            currency: defaultCurrency,
            type: 'apartment',
            country: defaultCountry,
            bedrooms: 1,
            bathrooms: 1,
            garages: 0,
            swimmingPools: 0,
            floors: 1,
            otherImages: [],
            location: { lat: 0, lng: 0 },
        },
    });

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

    const handleCancel = () => {
        if (isDirty) {
            Alert.alert(
                t('listings.unsavedChangesTitle'),
                t('listings.unsavedChangesMessage'),
                [
                    { text: t('common.stay'), style: 'cancel' },
                    {
                        text: t('common.discard'),
                        style: 'destructive',
                        onPress: () => router.back(),
                    },
                ]
            );
        } else {
            router.back();
        }
    };

    if (isSubmitting) {
        return (
            <LinearGradient colors={[colors.background, colors.surface]} style={[styles.container, styles.centered]}>
                <ActivityIndicator size="large" color={colors.primary} />
                <Text variant="bodyLarge" color="textPrimary" style={styles.loadingText}>
                    {t('listings.creating')}
                </Text>
            </LinearGradient>
        );
    }

    return (
        <LinearGradient colors={[colors.background, colors.surface]} style={styles.container}>
            <KeyboardAvoidingView
                style={styles.keyboardView}
                behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
            >
                <ScrollView
                    style={styles.scrollView}
                    contentContainerStyle={styles.scrollContent}
                    showsVerticalScrollIndicator={false}
                    keyboardShouldPersistTaps="handled"
                >
                    <View style={styles.header}>
                        <Text variant="pageTitle" color="textPrimary" style={styles.title}>
                            {t('listings.createListing')}
                        </Text>
                        <Text variant="bodyMedium" color="textSecondary" style={styles.subtitle}>
                            {t('listings.fillAllFields')}
                        </Text>
                    </View>

                    <CreateListingForm
                        control={control}
                        errors={errors}
                        setValue={setValue}
                        watch={watch}
                        isEditMode={false}
                    />
                </ScrollView>

                <View style={[styles.footer, { borderTopColor: colors.outline }]}>
                    <Button
                        mode="outlined"
                        onPress={handleCancel}
                        style={styles.footerButton}
                        disabled={isSubmitting}
                    >
                        {t('common.cancel')}
                    </Button>

                    <Button
                        mode="contained"
                        onPress={handleSubmit(onSubmit)}
                        style={styles.footerButton}
                        loading={isSubmitting}
                        disabled={isSubmitting}
                    >
                        {t('listings.create')}
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
    centered: {
        justifyContent: 'center',
        alignItems: 'center',
    },
    scrollView: {
        flex: 1,
    },
    scrollContent: {
        paddingBottom: SPACING.xl,
    },
    header: {
        paddingHorizontal: SPACING.xl,
        paddingVertical: SPACING.xl,
        alignItems: 'center',
        gap: SPACING.sm,
    },
    title: {
        textAlign: 'center',
    },
    subtitle: {
        textAlign: 'center',
        paddingHorizontal: SPACING.xl,
    },
    footer: {
        flexDirection: 'row',
        paddingHorizontal: SPACING.xl,
        paddingVertical: SPACING.lg,
        gap: SPACING.md,
        borderTopWidth: 1,
    },
    footerButton: {
        flex: 1,
        borderRadius: BORDER_RADIUS.md,
    },
    loadingText: {
        marginTop: SPACING.lg,
        textAlign: 'center',
    },
});