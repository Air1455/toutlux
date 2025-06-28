import React, { useState, useEffect } from 'react';
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
import { useRouter, useLocalSearchParams } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';

import { SafeScreen } from '@components/layout/SafeScreen';
import { useGetHousesQuery, useUpdateHouseMutation } from '@/redux/api/houseApi';
import CreateListingForm from "@components/listing/CreateListingForm";
import Text from '@/components/typography/Text';
import { SPACING, BORDER_RADIUS } from '@/constants/spacing';
import {LoadingScreen} from "@components/Loading";

const editListingSchema = (t) => yup.object({
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

export default function EditListingScreen() {
    const { colors } = useTheme();
    const { t } = useTranslation();
    const router = useRouter();
    const { id } = useLocalSearchParams();

    const { data: allHouses = [], isLoading: isLoadingHouses } = useGetHousesQuery();
    const [updateHouse, { isLoading: isUpdating }] = useUpdateHouseMutation();

    const [isInitialized, setIsInitialized] = useState(false);

    const schema = editListingSchema(t);

    const {
        control,
        handleSubmit,
        setValue,
        watch,
        reset,
        formState: { errors, isDirty },
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

    // Trouver la maison à éditer
    const house = allHouses.find(h => h.id === parseInt(id));

    // Initialiser le formulaire avec les données de la maison
    useEffect(() => {
        if (house && !isInitialized) {
            console.log('Initializing form with house data:', house);

            const formData = {
                shortDescription: house.shortDescription || '',
                longDescription: house.longDescription || '',
                price: house.price || 0,
                currency: house.currency || 'EUR',
                type: house.type || 'apartment',
                bedrooms: house.bedrooms || 1,
                bathrooms: house.bathrooms || 1,
                garages: house.garages || 0,
                swimmingPools: house.swimmingPools || 0,
                floors: house.floors || 1,
                surface: house.surface || '',
                yearOfConstruction: house.yearOfConstruction || null,
                address: house.address || '',
                city: house.city || '',
                country: house.country || 'France',
                isForRent: house.isForRent || false,
                firstImage: house.firstImage || '',
                otherImages: house.otherImages || [],
                location: house.location || { lat: 0, lng: 0 },
            };

            reset(formData);
            setIsInitialized(true);
        }
    }, [house, reset, isInitialized]);

    const onSubmit = async (data) => {
        try {
            console.log('Updating listing with data:', data);

            await updateHouse({
                id: parseInt(id),
                ...data,
            }).unwrap();

            Alert.alert(
                t('common.success'),
                t('listings.updateSuccess'),
                [
                    {
                        text: 'OK',
                        onPress: () => router.back(),
                    },
                ]
            );
        } catch (error) {
            console.error('Error updating listing:', error);
            Alert.alert(
                t('common.error'),
                error?.data?.message || t('listings.updateError')
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

    if (isLoadingHouses) {
        return <LoadingScreen />
    }

    if (!house) {
        return (
            <LinearGradient colors={[colors.background, colors.surface]} style={[styles.container, styles.centered]}>
                <Text variant="bodyLarge" color="error" style={styles.errorText}>
                    {t('listings.listingNotFound')}
                </Text>
                <Button
                    mode="outlined"
                    onPress={() => router.back()}
                    style={styles.backButton}
                >
                    {t('common.goBack')}
                </Button>
            </LinearGradient>
        );
    }

    if (!isInitialized) {
        return (
            <LinearGradient colors={[colors.background, colors.surface]} style={[styles.container, styles.centered]}>
                <ActivityIndicator size="large" color={colors.primary} />
                <Text variant="bodyLarge" color="textPrimary" style={styles.loadingText}>
                    {t('listings.initializingForm')}
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
                            {t('listings.editListing')}
                        </Text>
                        <Text variant="bodyMedium" color="textSecondary" style={styles.subtitle}>
                            {house.shortDescription}
                        </Text>
                    </View>

                    <CreateListingForm
                        control={control}
                        errors={errors}
                        setValue={setValue}
                        watch={watch}
                        isEditMode={true}
                    />
                </ScrollView>

                <View style={[styles.footer, { borderTopColor: colors.outline }]}>
                    <Button
                        mode="outlined"
                        onPress={handleCancel}
                        style={styles.footerButton}
                        disabled={isUpdating}
                    >
                        {t('common.cancel')}
                    </Button>

                    <Button
                        mode="contained"
                        onPress={handleSubmit(onSubmit)}
                        style={styles.footerButton}
                        loading={isUpdating}
                        disabled={isUpdating || !isDirty}
                    >
                        {t('listings.saveChanges')}
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
    errorText: {
        textAlign: 'center',
        marginBottom: SPACING.xl,
    },
    backButton: {
        paddingHorizontal: SPACING.xxl,
        borderRadius: BORDER_RADIUS.md,
    },
});