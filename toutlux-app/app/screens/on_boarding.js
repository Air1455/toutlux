import React, {useEffect, useMemo, useState} from 'react';
import {
    View, ScrollView, StyleSheet, Alert, Platform, KeyboardAvoidingView,
} from 'react-native';
import { useForm } from 'react-hook-form';
import { yupResolver } from '@hookform/resolvers/yup';
import { useTheme, ProgressBar } from 'react-native-paper';
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
import Text from '@/components/typography/Text';
import { SPACING, BORDER_RADIUS } from '@/constants/spacing';
import {LoadingScreen} from "@components/Loading";

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
                incomeSource: yup.string().required(t('validation.incomeSource.required')),
                occupation: yup.string(),
                incomeProof: yup.string().test(
                    'at-least-one-document',
                    t('validation.financialDocs.required'),
                    function(value) {
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
            occupation: '',
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
                occupation: user.occupation || '',
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

    // ✅ CORRECTION: Fonction de mapping des données améliorée
    const getStepData = (stepNumber, data) => {
        console.log('📤 getStepData called with:', { stepNumber, data });

        // S'assurer que les données sont propres
        const cleanData = {};
        Object.keys(data).forEach(key => {
            if (data[key] !== null && data[key] !== undefined) {
                cleanData[key] = data[key];
            }
        });

        switch (stepNumber) {
            case 0:
                const step0Data = {
                    firstName: cleanData.firstName || '',
                    lastName: cleanData.lastName || '',
                    phoneNumber: cleanData.phoneNumber || '',
                    phoneNumberIndicatif: cleanData.phoneNumberIndicatif || '228',
                    profilePicture: cleanData.profilePicture || '',
                };
                console.log('📤 Step 0 data prepared:', step0Data);
                return step0Data;

            case 1:
                const step1Data = {
                    identityCardType: cleanData.identityCardType || '',
                    identityCard: cleanData.identityCard || '',
                    selfieWithId: cleanData.selfieWithId || '',
                };
                console.log('📤 Step 1 data prepared:', step1Data);
                return step1Data;

            case 2:
                const step2Data = {
                    incomeSource: cleanData.incomeSource || '',
                    occupation: cleanData.occupation || '',
                    incomeProof: cleanData.incomeProof || '',
                    ownershipProof: cleanData.ownershipProof || '',
                };
                console.log('📤 Step 2 data prepared:', step2Data);
                return step2Data;

            case 3:
                const step3Data = {
                    termsAccepted: cleanData.termsAccepted || false,
                    privacyAccepted: cleanData.privacyAccepted || false,
                    marketingAccepted: cleanData.marketingAccepted || false,
                };
                console.log('📤 Step 3 data prepared:', step3Data);
                return step3Data;

            default:
                return {};
        }
    };

    const saveStepData = async (stepNumber, data) => {
        try {
            const stepData = getStepData(stepNumber, data);

            console.log('📤 Saving step data:', {
                step: stepNumber,
                data: stepData,
                originalData: data
            });

            const response = await updateProfileStep({
                step: stepNumber,
                data: stepData
            }).unwrap();

            console.log('✅ Step saved successfully:', response);
            await refetch();
            return response;
        } catch (error) {
            console.error('❌ Erreur sauvegarde étape:', error);

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
            await saveStepData(3, data);
            await acceptTerms('1.0').unwrap();
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
            if (!isValid) {
                console.log('❌ Validation échouée:', errors);
                return;
            }

            const currentFormData = getValues();
            console.log('📋 Current form data before save:', currentFormData);

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
            console.error('❌ Erreur nextStep:', error);
            Alert.alert(
                t('common.error'),
                error?.message || t('onboarding.errors.saveStep')
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

    // ✅ AJOUT: Debug des données actuelles
    const currentFormData = watch();
    useEffect(() => {
        console.log('🔍 Current form data changed:', {
            step,
            identityCardType: currentFormData.identityCardType,
            phoneNumberIndicatif: currentFormData.phoneNumberIndicatif,
            allData: currentFormData
        });
    }, [currentFormData, step]);

    if (isLoadingUser) {
        return (
            <LoadingScreen />
        );
    }

    return (
        <LinearGradient colors={[colors.background, colors.surface]} style={styles.container}>
            <KeyboardAvoidingView
                style={styles.keyboardView}
                behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
            >
                <View style={styles.header}>
                    <Text variant="bodyMedium" color="textSecondary" style={styles.stepIndicator}>
                        {t('onboarding.stepProgress', { current: step + 1, total: 4 })}
                    </Text>
                </View>

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
                            user={user}
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
                        <View style={[styles.infoContainer, { backgroundColor: colors.surfaceVariant + '40' }]}>
                            <Text variant="bodySmall" color="textSecondary" style={styles.infoText}>
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
        paddingHorizontal: SPACING.xl,
        paddingBottom: SPACING.lg,
        alignItems: 'center',
    },
    stepIndicator: {
        textAlign: 'center',
    },
    scrollContent: {
        flexGrow: 1,
        paddingHorizontal: SPACING.xl,
        paddingBottom: SPACING.xxl,
    },
    progressBar: {
        height: 8,
        marginHorizontal: SPACING.xl,
        marginBottom: SPACING.xl,
        borderRadius: BORDER_RADIUS.xs,
    },
    footerButtons: {
        marginTop: SPACING.xxxl,
        flexDirection: 'row',
        justifyContent: 'space-between',
        gap: SPACING.md,
    },
    backButton: {
        flex: 1,
        borderRadius: BORDER_RADIUS.md,
    },
    nextButton: {
        flex: 1,
        borderRadius: BORDER_RADIUS.md,
    },
    infoContainer: {
        marginTop: SPACING.xl,
        padding: SPACING.lg,
        borderRadius: BORDER_RADIUS.md,
    },
    infoText: {
        textAlign: 'center',
        lineHeight: 18,
    },
});