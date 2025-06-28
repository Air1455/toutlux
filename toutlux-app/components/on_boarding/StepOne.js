import React, { useState, useEffect } from 'react';
import { View, StyleSheet, TouchableOpacity, Alert, Image } from 'react-native';
import { useTheme, TextInput, ActivityIndicator } from 'react-native-paper';
import { Controller } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import CountryPicker from 'react-native-country-picker-modal';

import Text from '@/components/typography/Text';
import { useDocumentUpload } from '@/hooks/useDocumentUpload';
import { COUNTRY_CODE } from "@/constants/countryCode";
import { ValidationBadge } from '@/components/ValidationBadge';
import { SPACING, BORDER_RADIUS, ELEVATION } from '@/constants/spacing';

const StepOne = ({ control, errors, user, setValue, watch }) => {
    const { colors } = useTheme();
    const { t } = useTranslation();

    useEffect(() => {
        console.log('🔍 DEBUG: Vérification des traductions');
        console.log('t function:', typeof t);

        // Vérifier les clés disponibles
        console.log('Test direct validation:', t('validation.email.invalid'));

        // Tester d'autres clés que vous savez qui fonctionnent
        console.log('Test common keys:', t('common.error'));
        console.log('Test login keys:', t('login.submit'));

        // Utiliser la bonne méthode pour obtenir les ressources
        try {
            const i18n = require('react-i18next').useTranslation().i18n;
            const currentResources = i18n.getResourceBundle(i18n.language, 'translation');
            console.log('🗂️ Structure des traductions:', Object.keys(currentResources || {}));
            console.log('🔍 Validation existe?', 'validation' in (currentResources || {}));

            if (currentResources?.validation) {
                console.log('📧 Email validations:', Object.keys(currentResources.validation.email || {}));
            }
        } catch (error) {
            console.log('❌ Erreur lors de la récupération des ressources:', error.message);
        }
    }, [t]);

    const { isUploading, uploadingType, openCamera, openGallery, getImageUrl } = useDocumentUpload();

    const [country, setCountry] = useState({ cca2: 'TG', callingCode: ['228'] });
    const [showCountryPicker, setShowCountryPicker] = useState(false);
    const [isInitialized, setIsInitialized] = useState(false);

    const formData = watch();

    const getValue = (fieldName) => {
        const formValue = formData[fieldName];
        const userValue = user?.[fieldName];

        if (formValue !== undefined && formValue !== null && formValue !== '') {
            return formValue;
        }

        return userValue || '';
    };

    const getCountryByIndicatif = (indicatif) => COUNTRY_CODE[indicatif] || 'TG';

    useEffect(() => {
        if (!user || isInitialized) return;

        console.log('🔄 Initializing StepOne with user data:', {
            firstName: user.firstName,
            lastName: user.lastName,
            email: user.email,
            phoneNumber: user.phoneNumber,
            phoneNumberIndicatif: user.phoneNumberIndicatif,
            profilePicture: user.profilePicture,
        });

        const fieldsToInit = ['firstName', 'lastName', 'email', 'phoneNumber'];
        fieldsToInit.forEach(field => {
            if (user[field]) {
                setValue(field, user[field]);
            }
        });

        const indicatif = user.phoneNumberIndicatif || user.phoneIndicatif || '228';
        setValue('phoneNumberIndicatif', indicatif);
        setCountry({
            cca2: getCountryByIndicatif(indicatif),
            callingCode: [indicatif]
        });

        if (user.profilePicture && user.profilePicture !== 'yes') {
            setValue('profilePicture', user.profilePicture);
        }

        setIsInitialized(true);
        console.log('✅ StepOne initialization complete');
    }, [user, setValue, isInitialized]);

    const handlePhotoSelection = () => {
        Alert.alert(
            t('form.addPhoto'),
            t('form.photoSource'),
            [
                { text: t('common.cancel'), style: 'cancel' },
                { text: t('form.camera'), onPress: handleCamera },
                { text: t('form.gallery'), onPress: handleGallery },
            ]
        );
    };

    const handleCamera = async () => {
        try {
            const url = await openCamera('profile');
            if (url) {
                setValue('profilePicture', url);
                console.log('📸 Profile picture updated via camera:', url);
            }
        } catch (error) {
            console.error('Camera error:', error);
        }
    };

    const handleGallery = async () => {
        try {
            const url = await openGallery('profile');
            if (url) {
                setValue('profilePicture', url);
                console.log('🖼️ Profile picture updated via gallery:', url);
            }
        } catch (error) {
            console.error('Gallery error:', error);
        }
    };

    const handlePhoneChange = (text, onChange) => {
        const cleanedText = text.replace(/\D/g, '');
        onChange(cleanedText);
    };

    const handleCountrySelect = (selectedCountry) => {
        console.log('🌍 Country selected:', selectedCountry.callingCode[0]);
        setCountry(selectedCountry);
        setValue('phoneNumberIndicatif', selectedCountry.callingCode[0]);
    };

    const getVerificationIcon = (field) => {
        if (field === 'email' && user.validationStatus.email.isVerified) {
            return <TextInput.Icon icon="check-circle" iconColor={colors.primary} />;
        }
        if (field === 'phone' && user.validationStatus.phone.isVerified) {
            return <TextInput.Icon icon="check-circle" iconColor={colors.primary} />;
        }
        return null;
    };

    const profileImageUri = getValue('profilePicture') ? getImageUrl(getValue('profilePicture')) : null;

    return (
        <View style={styles.container}>
            <View style={styles.form}>
                {/* Photo de profil */}
                <View style={styles.fieldContainer}>
                    <Text variant="labelLarge" color="textPrimary" style={styles.photoLabel}>
                        {t('form.profilePicture')} *
                    </Text>

                    <TouchableOpacity
                        style={[
                            styles.photoContainer,
                            {
                                borderColor: colors.outline,
                                backgroundColor: colors.surface
                            }
                        ]}
                        onPress={handlePhotoSelection}
                        disabled={isUploading && uploadingType === 'profile'}
                    >
                        {isUploading && uploadingType === 'profile' ? (
                            <View style={styles.uploadingContainer}>
                                <ActivityIndicator size="large" color={colors.primary} />
                                <Text variant="bodyMedium" color="textSecondary" style={styles.uploadingText}>
                                    {t('form.uploading')}
                                </Text>
                            </View>
                        ) : profileImageUri ? (
                            <Image source={{ uri: profileImageUri }} style={styles.profileImage} />
                        ) : (
                            <View style={styles.photoPlaceholder}>
                                <MaterialCommunityIcons
                                    name="camera-plus"
                                    size={40}
                                    color={colors.textSecondary}
                                />
                                <Text variant="bodyMedium" color="textSecondary" style={styles.photoText}>
                                    {t('form.addPhoto')}
                                </Text>
                            </View>
                        )}
                    </TouchableOpacity>

                    {errors.profilePicture && (
                        <Text variant="labelMedium" color="error" style={styles.errorText}>
                            {errors.profilePicture.message}
                        </Text>
                    )}
                </View>

                {/* Email avec badge de validation */}
                <View style={styles.fieldContainer}>
                    <View style={styles.labelWithBadge}>
                        <Text variant="labelLarge" color="textPrimary" style={styles.label}>
                            {t('form.email')} *
                        </Text>
                        {!user.validationStatus.email.isVerified && (
                            <ValidationBadge isVerified={false} type="email" size="small"/>
                        )}
                    </View>

                    <Controller
                        control={control}
                        name="email"
                        render={() => (
                            <TextInput
                                mode="outlined"
                                value={getValue('email')}
                                editable={false}
                                style={[styles.input, styles.disabledInput]}
                                left={<TextInput.Icon icon="email" />}
                                right={getVerificationIcon('email')}
                            />
                        )}
                    />

                    {!user?.isEmailVerified && (
                        <View style={styles.verificationHint}>
                            <MaterialCommunityIcons
                                name="information-outline"
                                size={16}
                                color={colors.textSecondary}
                            />
                            <Text variant="labelMedium" color="textHint" style={styles.verificationText}>
                                {t('form.emailVerificationPending')}
                            </Text>
                        </View>
                    )}
                </View>

                {/* Prénom */}
                <View style={styles.fieldContainer}>
                    <Text variant="labelLarge" color="textPrimary" style={styles.label}>
                        {t('form.firstName')} *
                    </Text>
                    <Controller
                        control={control}
                        name="firstName"
                        render={({ field: { onChange, value } }) => (
                            <TextInput
                                mode="outlined"
                                value={value || ''}
                                onChangeText={onChange}
                                placeholder={t('form.firstNamePlaceholder')}
                                style={styles.input}
                                left={<TextInput.Icon icon="account" />}
                                error={!!errors.firstName}
                            />
                        )}
                    />
                    {errors.firstName && (
                        <Text variant="labelMedium" color="error" style={styles.errorText}>
                            {errors.firstName.message}
                        </Text>
                    )}
                </View>

                {/* Nom */}
                <View style={styles.fieldContainer}>
                    <Text variant="labelLarge" color="textPrimary" style={styles.label}>
                        {t('form.lastName')} *
                    </Text>
                    <Controller
                        control={control}
                        name="lastName"
                        render={({ field: { onChange, value } }) => (
                            <TextInput
                                mode="outlined"
                                value={value || ''}
                                onChangeText={onChange}
                                placeholder={t('form.lastNamePlaceholder')}
                                style={styles.input}
                                left={<TextInput.Icon icon="account-outline" />}
                                error={!!errors.lastName}
                            />
                        )}
                    />
                    {errors.lastName && (
                        <Text variant="labelMedium" color="error" style={styles.errorText}>
                            {errors.lastName.message}
                        </Text>
                    )}
                </View>

                {/* Numéro de téléphone avec badge de validation */}
                <View style={styles.fieldContainer}>
                    <View style={styles.labelWithBadge}>
                        <Text variant="labelLarge" color="textPrimary" style={styles.label}>
                            {t('form.phoneNumber')} *
                        </Text>
                        {!user.validationStatus.phone.isVerified && (
                            <ValidationBadge isVerified={false} type="phone" size="small"/>
                        )}
                    </View>

                    <View style={styles.phoneContainer}>
                        <TouchableOpacity
                            style={[
                                styles.countryButton,
                                {
                                    borderColor: colors.outline,
                                    backgroundColor: colors.surface
                                }
                            ]}
                            onPress={() => setShowCountryPicker(true)}
                        >
                            <Text variant="bodyLarge" color="textPrimary" style={styles.countryCode}>
                                +{country.callingCode[0]}
                            </Text>
                            <MaterialCommunityIcons
                                name="chevron-down"
                                size={20}
                                color={colors.textSecondary}
                            />
                        </TouchableOpacity>

                        <View style={styles.phoneInputContainer}>
                            <Controller
                                control={control}
                                name="phoneNumber"
                                render={({ field: { onChange, value } }) => (
                                    <TextInput
                                        mode="outlined"
                                        keyboardType="phone-pad"
                                        value={value || ''}
                                        onChangeText={(text) => handlePhoneChange(text, onChange)}
                                        placeholder={t('form.phoneNumberPlaceholder')}
                                        style={styles.phoneInput}
                                        left={<TextInput.Icon icon="phone" />}
                                        right={getVerificationIcon('phone')}
                                        error={!!errors.phoneNumber}
                                    />
                                )}
                            />
                        </View>

                        <CountryPicker
                            countryCode={country.cca2}
                            withCallingCode
                            withFlag
                            withFilter
                            visible={showCountryPicker}
                            onSelect={handleCountrySelect}
                            onClose={() => setShowCountryPicker(false)}
                            containerButtonStyle={styles.hiddenPicker}
                        />
                    </View>

                    <Controller
                        control={control}
                        name="phoneNumberIndicatif"
                        render={() => null}
                    />

                    {errors.phoneNumber && (
                        <Text variant="labelMedium" color="error" style={styles.errorText}>
                            {errors.phoneNumber.message}
                        </Text>
                    )}

                    {!user?.isPhoneVerified && (
                        <View style={styles.verificationHint}>
                            <MaterialCommunityIcons
                                name="information-outline"
                                size={16}
                                color={colors.textSecondary}
                            />
                            <Text variant="labelMedium" color="textHint" style={styles.verificationText}>
                                {t('form.phoneVerificationPending')}
                            </Text>
                        </View>
                    )}
                </View>
            </View>
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1
    },
    form: {
        gap: SPACING.xl
    },
    fieldContainer: {
        gap: SPACING.sm
    },
    label: {
        marginBottom: SPACING.xs,
    },
    photoLabel: {
        textAlign: 'center',
    },
    labelWithBadge: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: SPACING.xs,
    },
    input: {
        borderRadius: BORDER_RADIUS.md,
    },
    disabledInput: {
        opacity: 0.7
    },
    errorText: {
        marginTop: SPACING.xs,
        textAlign: 'center',
    },
    verificationHint: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.xs,
        marginTop: SPACING.xs,
        paddingHorizontal: SPACING.xs,
    },
    verificationText: {
        flex: 1,
        fontStyle: 'italic',
    },
    photoContainer: {
        width: 120,
        height: 120,
        borderRadius: 60,
        borderWidth: 2,
        borderStyle: 'dashed',
        alignSelf: 'center',
        justifyContent: 'center',
        alignItems: 'center',
        overflow: 'hidden',
        marginTop: SPACING.sm,
        elevation: ELEVATION.low,
    },
    photoPlaceholder: {
        alignItems: 'center',
        gap: SPACING.sm,
    },
    photoText: {
        textAlign: 'center',
    },
    profileImage: {
        width: '100%',
        height: '100%',
        borderRadius: 60
    },
    uploadingContainer: {
        alignItems: 'center',
        gap: SPACING.sm,
        padding: SPACING.lg,
    },
    uploadingText: {
        // Typography géré par le composant Text
    },
    phoneContainer: {
        flexDirection: 'row',
        gap: SPACING.md,
        alignItems: 'flex-start',
        marginTop: SPACING.sm,
    },
    countryButton: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        borderWidth: 1,
        borderRadius: BORDER_RADIUS.md,
        paddingHorizontal: SPACING.md,
        paddingVertical: SPACING.lg,
        minWidth: 85,
        gap: SPACING.xs,
        elevation: ELEVATION.low,
    },
    countryCode: {
        // Typography géré par le composant Text
    },
    phoneInputContainer: {
        flex: 1,
    },
    phoneInput: {
        borderRadius: BORDER_RADIUS.md,
    },
    hiddenPicker: {
        height: 0,
        width: 0,
        opacity: 0
    },
});

export default StepOne;