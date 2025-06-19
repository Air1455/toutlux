import React, { useState, useEffect } from 'react';
import { View, StyleSheet, TouchableOpacity, Alert, Image, TextInput as RNTextInput } from 'react-native';
import { Text, useTheme, TextInput, ActivityIndicator } from 'react-native-paper';
import { Controller } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import CountryPicker from 'react-native-country-picker-modal';
import { useDocumentUpload } from '@/hooks/useDocumentUpload';

const StepOne = ({ control, errors, user, setValue, watch }) => {
    const { colors } = useTheme();
    const { t } = useTranslation();
    const [localProfilePicture, setLocalProfilePicture] = useState(null);
    const [country, setCountry] = useState({ cca2: 'TG', callingCode: ['228'] });
    const [rawPhone, setRawPhone] = useState('');
    const [showCountryPicker, setShowCountryPicker] = useState(false);
    const [isInitialized, setIsInitialized] = useState(false);

    // Utilisation du hook pour l'upload
    const { isUploading, uploadingType, openCamera, openGallery, getImageUrl } = useDocumentUpload();

    const formData = watch();

    // ✅ CORRECTION: Fonction getValue améliorée qui prend en compte toutes les sources
    const getValue = (fieldName) => {
        // Priorité : 1. Valeur du formulaire, 2. Valeur de l'utilisateur, 3. Valeur par défaut
        const formValue = formData[fieldName];
        const userValue = user?.[fieldName];

        // Si la valeur du formulaire existe et n'est pas vide, l'utiliser
        if (formValue !== undefined && formValue !== null && formValue !== '') {
            return formValue;
        }

        // Sinon utiliser la valeur de l'utilisateur
        if (userValue !== undefined && userValue !== null && userValue !== '') {
            return userValue;
        }

        // Sinon retourner une chaîne vide
        return '';
    };

    useEffect(() => {
        const currentValue = watch('phoneNumberIndicatif');
        if (!currentValue) {
            setValue('phoneNumberIndicatif', '228');
        }
    }, []);

    // ✅ CORRECTION: Fonction pour trouver le pays par indicatif
    const findCountryByCallingCode = (callingCode) => {
        // Mapping des indicatifs courants vers les codes pays
        const countryMapping = {
            '1': 'US',
            '33': 'FR',
            '44': 'GB',
            '49': 'DE',
            '228': 'TG',
            '225': 'CI',
            '221': 'SN',
            '226': 'BF',
            '227': 'NE',
            '229': 'BJ',
            '230': 'MU',
            '231': 'LR',
            '232': 'SL',
            '233': 'GH',
            '234': 'NG',
            '235': 'TD',
            '236': 'CF',
            '237': 'CM',
            '238': 'CV',
            '239': 'ST',
            '240': 'GQ',
            '241': 'GA',
            '242': 'CG',
            '243': 'CD',
            '244': 'AO',
            '245': 'GW',
            '246': 'IO',
            '248': 'SC',
            '249': 'SD',
            '250': 'RW',
            '251': 'ET',
            '252': 'SO',
            '253': 'DJ',
            '254': 'KE',
            '255': 'TZ',
            '256': 'UG',
            '257': 'BI',
            '258': 'MZ',
            '260': 'ZM',
            '261': 'MG',
            '262': 'YT',
            '263': 'ZW',
            '264': 'NA',
            '265': 'MW',
            '266': 'LS',
            '267': 'BW',
            '268': 'SZ',
            '269': 'KM',
        };

        return countryMapping[callingCode] || 'TG';
    };

    useEffect(() => {
        // Ne s'exécuter que si user existe et qu'on n'est pas déjà initialisé
        if (user && !isInitialized) {
            console.log('🔄 Initializing StepOne with user data:', {
                user: user,
                phoneNumber: user.phoneNumber,
                phoneNumberIndicatif: user.phoneNumberIndicatif || user.phoneIndicatif || user.phone_number_indicatif,
                profilePicture: user.profilePicture
            });

            // Initialiser tous les champs de base
            if (user.firstName) {
                setValue('firstName', user.firstName);
            }
            if (user.lastName) {
                setValue('lastName', user.lastName);
            }
            if (user.email) {
                setValue('email', user.email);
            }

            // Initialiser le numéro de téléphone
            if (user.phoneNumber) {
                setRawPhone(user.phoneNumber);
                setValue('phoneNumber', user.phoneNumber);
            }

            // ✅ IMPORTANT: Gérer les différents formats possibles de l'indicatif
            const indicatif = user.phoneNumberIndicatif ||
                user.phoneIndicatif ||
                user.phone_number_indicatif ||
                user.phoneNumberIndicative;

            if (indicatif) {
                console.log('📞 Found indicatif:', indicatif);
                const countryCode = findCountryByCallingCode(indicatif);
                setCountry({
                    cca2: countryCode,
                    callingCode: [indicatif]
                });
                setValue('phoneNumberIndicatif', indicatif);
            }

            // Initialiser la photo de profil
            if (user.profilePicture && user.profilePicture !== 'yes') {
                setValue('profilePicture', user.profilePicture);
                const imageUrl = getImageUrl(user.profilePicture);
                setLocalProfilePicture(imageUrl);
                console.log('🖼️ Profile picture set:', imageUrl);
            }

            setIsInitialized(true);
            console.log('✅ Initialization complete');
        }
    }, [user, setValue, getImageUrl, isInitialized]);

    // ✅ AJOUT: Effet pour forcer la mise à jour si les données changent après l'initialisation
    useEffect(() => {
        if (user && isInitialized) {
            // Si les données de l'utilisateur changent après l'initialisation
            const currentPhone = getValue('phoneNumber');
            const currentIndicatif = getValue('phoneNumberIndicatif');

            if (!currentPhone && user.phoneNumber) {
                console.log('📱 Updating phone from user data');
                setRawPhone(user.phoneNumber);
                setValue('phoneNumber', user.phoneNumber);
            }

            const userIndicatif = user.phoneNumberIndicatif ||
                user.phoneIndicatif ||
                user.phone_number_indicatif;

            if (!currentIndicatif && userIndicatif) {
                console.log('🌍 Updating indicatif from user data');
                setValue('phoneNumberIndicatif', userIndicatif);
                const countryCode = findCountryByCallingCode(userIndicatif);
                setCountry({
                    cca2: countryCode,
                    callingCode: [userIndicatif]
                });
            }
        }
    }, [user, isInitialized]);

    // Gestionnaire pour la sélection d'image
    const handleImagePicker = () => {
        Alert.alert(
            t('form.addPhoto'),
            t('form.photoSource'),
            [
                { text: t('common.cancel'), style: 'cancel' },
                { text: t('form.camera'), onPress: () => handleCamera() },
                { text: t('form.gallery'), onPress: () => handleGallery() },
            ]
        );
    };

    const handleCamera = async () => {
        try {
            const url = await openCamera('profile');
            if (url) {
                console.log('📸 Camera result:', url);
                setValue('profilePicture', url);
                setLocalProfilePicture(getImageUrl(url));
            }
        } catch (error) {
            console.error('Camera error:', error);
        }
    };

    const handleGallery = async () => {
        try {
            const url = await openGallery('profile');
            if (url) {
                console.log('🖼️ Gallery result:', url);
                setValue('profilePicture', url);
                setLocalProfilePicture(getImageUrl(url));
            }
        } catch (error) {
            console.error('Gallery error:', error);
        }
    };

    const getProfileImageUri = () => {
        if (localProfilePicture) return localProfilePicture;
        const currentPicture = getValue('profilePicture');
        return currentPicture ? getImageUrl(currentPicture) : null;
    };

    const getVerificationIcon = (field) => {
        if (field === 'email' && user?.isEmailVerified) {
            return <TextInput.Icon icon="check-circle" iconColor={colors.primary} />;
        }
        if (field === 'phone' && user?.isPhoneVerified) {
            return <TextInput.Icon icon="check-circle" iconColor={colors.primary} />;
        }
        return null;
    };

    const profileImageUri = getProfileImageUri();

    const handlePhoneNumberChange = (text, onChange, callingCode) => {
        // Nettoyer le texte (garder seulement les chiffres)
        const cleanedText = text.replace(/\D/g, '');
        console.log('📱 Phone number changed:', cleanedText);

        setRawPhone(cleanedText);
        onChange(cleanedText);
    };

    const handleCountrySelect = (selectedCountry) => {
        console.log('🌍 Country selected:', selectedCountry);
        setCountry(selectedCountry);
        setValue('phoneNumberIndicatif', selectedCountry.callingCode[0]);
    };

    // ✅ AJOUT: Debug amélioré
    console.log('🔍 StepOne state:', {
        user: !!user,
        rawPhone,
        country: country.callingCode[0],
        formPhone: getValue('phoneNumber'),
        formIndicatif: getValue('phoneNumberIndicatif'),
        isInitialized,
        userPhone: user?.phoneNumber,
        userIndicatif: user?.phoneNumberIndicatif || user?.phoneIndicatif,
        formData: formData
    });

    return (
        <View style={styles.container}>
            <View style={styles.header}>
                <Text style={[styles.subtitle, { color: colors.onSurfaceVariant }]}>
                    {t('onboarding.step1.subtitle')}
                </Text>
            </View>

            <View style={styles.form}>
                {/* Photo de profil */}
                <View style={styles.fieldContainer}>
                    <Text style={[styles.label, { color: colors.onSurface, textAlign: 'center' }]}>
                        {t('form.profilePicture')} *
                    </Text>
                    <TouchableOpacity
                        style={[styles.photoContainer, { borderColor: colors.outline }]}
                        onPress={handleImagePicker}
                        disabled={isUploading && uploadingType === 'profile'}
                    >
                        {isUploading && uploadingType === 'profile' ? (
                            <View style={styles.uploadingContainer}>
                                <ActivityIndicator size="large" color={colors.primary} />
                                <Text style={[styles.uploadingText, { color: colors.onSurfaceVariant }]}>
                                    {t('form.uploading')}
                                </Text>
                            </View>
                        ) : profileImageUri ? (
                            <Image source={{ uri: profileImageUri }} style={styles.profileImage} />
                        ) : (
                            <View style={styles.photoPlaceholder}>
                                <MaterialCommunityIcons name="camera-plus" size={40} color={colors.onSurfaceVariant} />
                                <Text style={[styles.photoText, { color: colors.onSurfaceVariant }]}>
                                    {t('form.addPhoto')}
                                </Text>
                            </View>
                        )}
                    </TouchableOpacity>
                    {errors.profilePicture && (
                        <Text style={[styles.errorText, { color: colors.error }]}>
                            {errors.profilePicture.message}
                        </Text>
                    )}
                </View>

                {/* Email (lecture seule) */}
                <View style={styles.fieldContainer}>
                    <Text style={[styles.label, { color: colors.onSurface }]}>
                        {t('form.email')} *
                    </Text>
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
                    <Text style={[styles.helpText, { color: colors.onSurfaceVariant }]}>
                        {t('form.emailHelp')}
                    </Text>
                </View>

                {/* Prénom */}
                <View style={styles.fieldContainer}>
                    <Text style={[styles.label, { color: colors.onSurface }]}>
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
                        <Text style={[styles.errorText, { color: colors.error }]}>
                            {errors.firstName.message}
                        </Text>
                    )}
                </View>

                {/* Nom */}
                <View style={styles.fieldContainer}>
                    <Text style={[styles.label, { color: colors.onSurface }]}>
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
                        <Text style={[styles.errorText, { color: colors.error }]}>
                            {errors.lastName.message}
                        </Text>
                    )}
                </View>

                {/* Numéro de téléphone */}
                <View style={styles.fieldContainer}>
                    <Text style={[styles.label, { color: colors.onSurface }]}>
                        {t('form.phoneNumber')} *
                    </Text>
                    <Controller
                        control={control}
                        name="phoneNumber"
                        render={({ field: { onChange, value } }) => (
                            <View style={styles.phoneContainer}>
                                {/* Sélecteur de pays */}
                                <TouchableOpacity
                                    style={[styles.countryButton, { borderColor: colors.outline }]}
                                    onPress={() => setShowCountryPicker(true)}
                                >
                                    <Text style={[styles.countryCode, { color: colors.onSurface }]}>
                                        +{country.callingCode[0]}
                                    </Text>
                                    <MaterialCommunityIcons
                                        name="chevron-down"
                                        size={20}
                                        color={colors.onSurfaceVariant}
                                    />
                                </TouchableOpacity>

                                {/* Champ de numéro */}
                                <View style={styles.phoneInputContainer}>
                                    <TextInput
                                        mode="outlined"
                                        keyboardType="phone-pad"
                                        value={value || rawPhone}
                                        onChangeText={(text) => handlePhoneNumberChange(text, onChange, country.callingCode[0])}
                                        placeholder={t('form.phoneNumberPlaceholder')}
                                        style={styles.phoneInput}
                                        left={<TextInput.Icon icon="phone" />}
                                        right={getVerificationIcon('phone')}
                                        error={!!errors.phoneNumber}
                                    />
                                </View>

                                {/* Country Picker Modal */}
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
                        )}
                    />

                    {/* Controller caché pour l'indicatif */}
                    <Controller
                        control={control}
                        name="phoneNumberIndicatif"
                        render={() => null}
                    />

                    {errors.phoneNumber && (
                        <Text style={[styles.errorText, { color: colors.error }]}>
                            {errors.phoneNumber.message}
                        </Text>
                    )}
                    <Text style={[styles.helpText, { color: colors.onSurfaceVariant }]}>
                        {t('form.phoneNumberHelp')}
                    </Text>
                </View>
            </View>
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1
    },
    header: {
        marginBottom: 32,
        alignItems: 'center'
    },
    subtitle: {
        fontSize: 20,
        fontWeight: 'bold',
        textAlign: 'center',
        lineHeight: 22
    },
    form: {
        gap: 24
    },
    fieldContainer: {
        gap: 8
    },
    label: {
        fontSize: 16,
        fontWeight: '600',
        marginBottom: 4
    },
    input: {
        fontSize: 16
    },
    disabledInput: {
        opacity: 0.7
    },
    errorText: {
        fontSize: 14,
        marginTop: 4
    },
    helpText: {
        fontSize: 12,
        fontStyle: 'italic'
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
        overflow: 'hidden'
    },
    photoPlaceholder: {
        alignItems: 'center',
        gap: 8
    },
    photoText: {
        fontSize: 12,
        textAlign: 'center'
    },
    profileImage: {
        width: '100%',
        height: '100%',
        borderRadius: 60
    },
    uploadingContainer: {
        alignItems: 'center',
        gap: 8
    },
    uploadingText: {
        fontSize: 12
    },
    phoneContainer: {
        flexDirection: 'row',
        gap: 8,
        alignItems: 'flex-start',
    },
    countryButton: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        borderWidth: 1,
        borderRadius: 4,
        paddingHorizontal: 12,
        paddingVertical: 16,
        minWidth: 80,
        gap: 4,
    },
    countryCode: {
        fontSize: 16,
        fontWeight: '500',
    },
    phoneInputContainer: {
        flex: 1,
    },
    phoneInput: {
        fontSize: 16,
    },
    hiddenPicker: {
        height: 0,
        width: 0,
    },
});

export default StepOne;