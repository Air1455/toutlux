import React, {useEffect, useMemo, useState} from 'react';
import {
    View, ScrollView, StyleSheet, Alert, Platform, KeyboardAvoidingView,
} from 'react-native';
import { useForm } from 'react-hook-form';
import { yupResolver } from '@hookform/resolvers/yup';
import { useTheme, ProgressBar, Text } from 'react-native-paper';
import * as yup from 'yup';
import { useRouter, useLocalSearchParams } from 'expo-router';
import { useSelector } from 'react-redux';
import { LinearGradient } from 'expo-linear-gradient';

import { Button } from '@/components/form/Button';
import { useTranslation } from 'react-i18next';

import {useGetMeQuery,} from '@/redux/api/authApi';

import {useUpdateProfileStepMutation, useAcceptTermsMutation} from '@/redux/api/userApi';

import StepOne from '@components/on_boarding/StepOne';
import StepTwo from '@components/on_boarding/StepTwo';
import StepThree from '@components/on_boarding/StepThree';
import StepFour from '@components/on_boarding/StepFour';
import { useOnboardingHeaderOptions } from "@/hooks/useHeaderOptions";


const getStepSchema = (step, t) => {
    switch (step) {
        case 0:
            return yup.object({
                firstName: yup.string()
                    .trim()
                    .required(t('validation.firstName.required')),
                lastName: yup.string()
                    .trim()
                    .required(t('validation.lastName.required')),
                email: yup.string()
                    .trim()
                    .lowercase()
                    .email(t('validation.email.invalid'))
                    .required(t('validation.email.required')),
                phoneNumber: yup.string()
                    .matches(/^\d{4,15}$/, t('validation.phoneNumber.invalid'))
                    .required(t('validation.phoneNumber.required')),
                phoneNumberIndicatif: yup.string()
                    .matches(/^\d{1,4}$/, t('validation.phoneNumberIndicatif.invalid'))
                    .required(t('validation.phoneNumberIndicatif.required')),
                profilePicture: yup.string()
                    .required(t('validation.profilePicture.required')),
            });
        case 1:
            return yup.object({
                identityCardType: yup.string()
                    .oneOf(['national_id', 'passport', 'driving_license'])
                    .required(t('validation.identityCardType.required')),
                identityCard: yup.string()
                    .required(t('validation.identityCard.required')),
                selfieWithId: yup.string()
                    .required(t('validation.selfieWithId.required')),
            });
        case 2:
            return yup.object().shape({
                incomeSource: yup.string().required('La source de revenus est requise'),
                occupation: yup.string(),
                // Validation conditionnelle des documents
                incomeProof: yup.string().test(
                    'at-least-one-document',
                    'Au moins un justificatif est requis',
                    function(value) {
                        // Utilise this.parent pour accéder aux autres champs
                        return !!value || !!this.parent.ownershipProof;
                    }
                ),
                ownershipProof: yup.string(),
            });
        case 3:
            return yup.object({
                termsAccepted: yup.boolean()
                    .oneOf([true], t('validation.termsAccepted.required')),
                privacyAccepted: yup.boolean()
                    .oneOf([true], t('validation.privacyAccepted.required')),
                marketingAccepted: yup.boolean().default(false),
            });
        default:
            return yup.object({});
    }
};

export default function OnBoardingScreen() {
    const { t } = useTranslation();
    const { colors } = useTheme();
    const router = useRouter();
    const params = useLocalSearchParams();
    const token = useSelector((state) => state.auth.token);

    const initialStep = params.step ? parseInt(params.step) : 0;
    const isFromCompletion = params.fromCompletion === 'true';

    const { data: user, isLoading: isLoadingUser, refetch } = useGetMeQuery(undefined, {
        skip: !token,
        refetchOnMountOrArgChange: true,
    });
    const [updateProfileStep, { isLoading: isUpdating }] = useUpdateProfileStepMutation();
    const [acceptTerms, { isLoading: isAcceptingTerms }] = useAcceptTermsMutation();

    const [step, setStep] = useState(initialStep);
    const progress = (step + 1) / 4;
    const isLoading = isUpdating || isAcceptingTerms || isLoadingUser;

    const schema = useMemo(() => getStepSchema(step, t), [step, t]);

    const {
        control,
        handleSubmit,
        setValue,
        watch,
        formState: { errors },
        trigger,
        getValues,
        reset,
    } = useForm({
        resolver: yupResolver(schema),
        mode: 'onChange',
        defaultValues: {
            firstName: '',
            lastName: '',
            email: '',
            phoneNumber: '',
            phoneNumberIndicatif: '228',
            profilePicture: '',
            identityCardType: '',
            identityCard: '',
            selfieWithId: '',
            incomeSource: '',
            incomeProof: '',
            ownershipProof: '',
            termsAccepted: false,
            privacyAccepted: false,
            marketingAccepted: false,
        },
    });

    const getStepTitle = () => {
        const titles = [
            t('onboarding.step1.title'),
            t('onboarding.step2.title'),
            t('onboarding.step3.title'),
            t('onboarding.step4.title'),
        ];
        return titles[step];
    };

    // ✅ AJOUT: Hook pour gérer le header
    useOnboardingHeaderOptions(getStepTitle(), progress, [step, t]);

    // Charger les données utilisateur
    useEffect(() => {
        if (user && !isLoadingUser) {
            const userData = {
                firstName: user.firstName || '',
                lastName: user.lastName || '',
                email: user.email || '',
                phoneNumber: user.phoneNumber || '',
                phoneNumberIndicatif: user.phoneNumberIndicatif || '228',
                profilePicture: user.profilePicture || '',
                identityCardType: user.identityCardType || '',
                identityCard: user.identityCard || '',
                selfieWithId: user.selfieWithId || '',
                incomeSource: user.incomeSource || '',
                incomeProof: user.incomeProof || '',
                ownershipProof: user.ownershipProof || '',
                termsAccepted: user.termsAccepted || false,
                privacyAccepted: user.privacyAccepted || false,
                marketingAccepted: user.marketingAccepted || false,
            };

            console.log('📥 Loading user data into form:', userData);
            reset(userData);
        }
    }, [user, isLoadingUser, reset]);

    const getStepData = (stepNumber, data) => {
        // Normaliser les données pour gérer snake_case vs camelCase
        const normalizedData = {};

        // Convertir toutes les clés en camelCase
        Object.keys(data).forEach(key => {
            const camelKey = key.replace(/_([a-z])/g, (g) => g[1].toUpperCase());
            normalizedData[camelKey] = data[key];
        });
        
        // Gérer spécifiquement phoneNumberIndicatif
        if (data.phoneNumberIndicatif) {
            normalizedData.phoneNumberIndicatif = data.phoneNumberIndicatif;
        }

        switch (stepNumber) {
            case 0:
                return {
                    firstName: normalizedData.firstName,
                    lastName: normalizedData.lastName,
                    phoneNumber: normalizedData.phoneNumber,
                    phoneNumberIndicatif: normalizedData.phoneNumberIndicatif,
                    profilePicture: normalizedData.profilePicture,
                };
            case 1:
                return {
                    identityCardType: normalizedData.identityCardType,
                    identityCard: normalizedData.identityCard,
                    selfieWithId: normalizedData.selfieWithId,
                };
            case 2:
                return {
                    incomeSource: normalizedData.incomeSource,
                    incomeProof: normalizedData.incomeProof || '',
                    ownershipProof: normalizedData.ownershipProof || '',
                };
            case 3:
                return {
                    termsAccepted: normalizedData.termsAccepted,
                    privacyAccepted: normalizedData.privacyAccepted,
                    marketingAccepted: normalizedData.marketingAccepted || false,
                };
            default:
                return {};
        }
    };

    const saveStepData = async (stepNumber, data) => {
        try {
            const stepData = getStepData(stepNumber, data);

            console.log('📤 Saving step data:', {
                step: stepNumber,
                data: stepData
            });

            const response = await updateProfileStep({
                step: stepNumber,
                data: stepData
            }).unwrap();

            // Rafraîchir les données utilisateur
            await refetch();

            return response;
        } catch (error) {
            console.error('❌ Erreur sauvegarde étape:', error);

            // Gestion d'erreur plus détaillée
            if (error?.data?.details) {
                const errorDetails = Object.entries(error.data.details)
                    .map(([field, message]) => `${field}: ${message}`)
                    .join('\n');
                throw new Error(errorDetails);
            }

            throw error;
        }
    };

    const onSubmit = async (data) => {
        try {
            // Sauvegarder d'abord les données de l'étape 3
            await saveStepData(3, data);

            // Puis accepter les termes avec la version
            await acceptTerms({ version: '1.0' }).unwrap();

            // Rafraîchir une dernière fois pour s'assurer que tout est à jour
            await refetch();

            Alert.alert(
                t('common.success'),
                t('onboarding.completed.message'),
                [
                    {
                        text: 'OK',
                        onPress: () => {
                            if (isFromCompletion) {
                                router.back();
                            } else {
                                router.replace('/(tabs)/profile');
                            }
                        }
                    }
                ]
            );
        } catch (error) {
            console.error('❌ Erreur finalisation:', error);

            const errorMessage = error?.message || t('onboarding.errors.finalStep');

            Alert.alert(
                t('common.error'),
                errorMessage
            );
        }
    };

    const nextStep = async () => {
        try {
            const isValid = await trigger();
            console.log(isValid ? 'Validation réussie' : 'Validation échouée', errors);
            if (!isValid) {
                console.log('Validation échouée:', errors);
                return;
            }

            const currentFormData = getValues();

            if (isFromCompletion) {
                await saveStepData(step, currentFormData);
                Alert.alert(
                    t('common.success'),
                    t('onboarding.step.saved', {stepTitle: getStepTitle()}),
                    [{ text: 'OK', onPress: () => router.back() }]
                );
                return;
            }

            if (step < 3) {
                await saveStepData(step, currentFormData);
                setStep(prev => prev + 1);
            } else {
                await onSubmit(currentFormData);
            }
        } catch (error) {
            console.error('Erreur nextStep:', error);
            Alert.alert(
                t('common.error'),
                t('onboarding.errors.saveStep')
            );
        }
    };

    const prevStep = () => {
        if (isFromCompletion) {
            router.back();
        } else {
            setStep(prev => Math.max(prev - 1, 0));
        }
    };

    const getButtonText = () => {
        if (isFromCompletion) {
            return t('common.save');
        }
        return step < 3 ? t('common.next') : t('common.finalize');
    };

    if (isLoadingUser) {
        return (
            <LinearGradient colors={[colors.background, colors.surface]} style={[styles.container, styles.centered]}>
                <Text style={{ color: colors.onBackground }}>
                    {t('loading')}
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
                {/* Header */}
                <View style={styles.header}>
                    <Text style={[styles.stepIndicator, { color: colors.onSurfaceVariant }]}>
                        {t('onboarding.stepProgress', 'Étape {{current}} sur {{total}}', { current: step + 1, total: 4 })}
                    </Text>
                </View>

                {/* Barre de progression */}
                <ProgressBar
                    progress={progress}
                    color={colors.primary}
                    style={[styles.progressBar, { backgroundColor: colors.surfaceVariant }]}
                />

                <ScrollView
                    contentContainerStyle={styles.scrollContent}
                    showsVerticalScrollIndicator={false}
                    keyboardShouldPersistTaps="handled"
                >
                    {step === 0 && (
                        <StepOne
                            control={control}
                            errors={errors}
                            user={user}
                            setValue={setValue}
                            watch={watch}
                        />
                    )}
                    {step === 1 && (
                        <StepTwo
                            control={control}
                            errors={errors}
                            setValue={setValue}
                            watch={watch}
                        />
                    )}
                    {step === 2 && (
                        <StepThree
                            control={control}
                            errors={errors}
                            setValue={setValue}
                            watch={watch}
                        />
                    )}
                    {step === 3 && (
                        <StepFour
                            control={control}
                            errors={errors}
                            watch={watch}
                        />
                    )}

                    <View style={styles.footerButtons}>
                        <Button
                            mode="outlined"
                            onPress={prevStep}
                            style={styles.backButton}
                            disabled={isLoading}
                        >
                            {isFromCompletion ? t('common.cancel') : t('common.back')}
                        </Button>

                        <Button
                            mode="contained"
                            onPress={nextStep}
                            loading={isLoading}
                            disabled={isLoading}
                            style={styles.nextButton}
                        >
                            {getButtonText()}
                        </Button>
                    </View>

                    {!isFromCompletion && (
                        <View style={styles.infoContainer}>
                            <Text style={[styles.infoText, { color: colors.onSurfaceVariant }]}>
                                {t('onboarding.saveInfo')}
                            </Text>
                        </View>
                    )}
                </ScrollView>
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
    header: {
        paddingHorizontal: 20,
        paddingBottom: 16,
        alignItems: 'center',
    },
    stepIndicator: {
        fontSize: 14,
    },
    scrollContent: {
        flexGrow: 1,
        padding: 24,
        paddingBottom: 40,
    },
    progressBar: {
        height: 8,
        marginHorizontal: 20,
        marginBottom: 20,
        borderRadius: 4,
    },
    footerButtons: {
        marginTop: 32,
        flexDirection: 'row',
        justifyContent: 'space-between',
        gap: 12,
    },
    backButton: {
        flex: 1,
    },
    nextButton: {
        flex: 1,
    },
    infoContainer: {
        marginTop: 20,
        padding: 16,
        borderRadius: 8,
        backgroundColor: 'rgba(0,0,0,0.05)',
    },
    infoText: {
        fontSize: 14,
        textAlign: 'center',
    },
});