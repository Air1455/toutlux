import React, { useState, useEffect } from 'react';
import { View, StyleSheet, TouchableOpacity, Image, Alert } from 'react-native';
import { useTheme, ActivityIndicator, Card } from 'react-native-paper';
import { Controller } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import DropDownPicker from 'react-native-dropdown-picker';

import Text from '@/components/typography/Text';
import { useDocumentUpload } from '@/hooks/useDocumentUpload';
import { ValidationBadge } from '@/components/ValidationBadge';
import { SPACING, BORDER_RADIUS, ELEVATION } from '@/constants/spacing';

const StepTwo = ({ control, errors, setValue, watch, user }) => {
    const { colors } = useTheme();
    const { t } = useTranslation();

    const { isUploading, uploadingType, openCamera, openGallery, getImageUrl } = useDocumentUpload();
    const formData = watch();

    // État local pour le dropdown
    const [open, setOpen] = useState(false);
    const [dropdownValue, setDropdownValue] = useState(null);

    const [items, setItems] = useState([
        {
            label: t('documents.nationalId'),
            value: 'national_id',
            icon: () => <MaterialCommunityIcons name="card-account-details" size={18} color={colors.textPrimary} />
        },
        {
            label: t('documents.passport'),
            value: 'passport',
            icon: () => <MaterialCommunityIcons name="passport" size={18} color={colors.textPrimary} />
        },
        {
            label: t('documents.drivingLicense'),
            value: 'driving_license',
            icon: () => <MaterialCommunityIcons name="car" size={18} color={colors.textPrimary} />
        },
    ]);

    // Synchronisation bidirectionnelle
    useEffect(() => {
        const currentValue = formData.identityCardType;
        console.log('🔍 StepTwo - identityCardType changed:', {
            currentValue,
            dropdownValue,
            formData: formData
        });

        if (currentValue && currentValue !== dropdownValue) {
            console.log('🔄 Updating dropdown value from form data');
            setDropdownValue(currentValue);
        } else if (!currentValue && dropdownValue) {
            console.log('🔄 Form data empty but dropdown has value, syncing...');
            setValue('identityCardType', dropdownValue);
        }
    }, [formData.identityCardType, dropdownValue, setValue]);

    const handleDropdownChange = (value) => {
        console.log('📝 Dropdown changed to:', value);
        setDropdownValue(value);
        setValue('identityCardType', value);

        // Force trigger validation
        setTimeout(() => {
            const currentFormData = watch();
            console.log('✅ After dropdown change, form data:', {
                identityCardType: currentFormData.identityCardType,
                allData: currentFormData
            });
        }, 100);
    };

    const getValue = (fieldName) => formData[fieldName] || '';

    const handleDocumentUpload = (documentType) => {
        Alert.alert(
            t('form.addDocument'),
            t('form.documentSource'),
            [
                { text: t('common.cancel'), style: 'cancel' },
                { text: t('form.camera'), onPress: () => handleCamera(documentType) },
                { text: t('form.gallery'), onPress: () => handleGallery(documentType) },
            ]
        );
    };

    const handleCamera = async (documentType) => {
        try {
            const url = await openCamera(documentType);
            if (url) {
                setValue(documentType, url);
            }
        } catch (error) {
            // Erreur déjà gérée dans le hook
        }
    };

    const handleGallery = async (documentType) => {
        try {
            const url = await openGallery(documentType);
            if (url) {
                setValue(documentType, url);
            }
        } catch (error) {
            // Erreur déjà gérée dans le hook
        }
    };

    const DocumentCard = ({ title, description, documentType, icon }) => {
        const isCurrentlyUploading = isUploading && uploadingType === documentType;
        const imageUri = getValue(documentType);
        const hasDocument = !!imageUri;
        const fullImageUrl = hasDocument ? getImageUrl(imageUri) : null;

        return (
            <View style={styles.fieldContainer}>
                <Text variant="labelLarge" color="textPrimary" style={styles.label}>
                    {title} *
                </Text>

                <Card
                    style={[
                        styles.documentCard,
                        {
                            backgroundColor: colors.surface,
                            borderRadius: BORDER_RADIUS.lg
                        }
                    ]}
                    elevation={ELEVATION.low}
                >
                    <Card.Content>
                        <View style={styles.documentHeader}>
                            <View style={styles.documentInfo}>
                                <View style={styles.documentTitle}>
                                    <MaterialCommunityIcons name={icon} size={20} color={colors.primary} />
                                    <Text
                                        variant="bodyMedium"
                                        color="textSecondary"
                                        style={styles.documentDescription}
                                        numberOfLines={2}
                                    >
                                        {description}
                                    </Text>
                                </View>
                            </View>
                        </View>

                        <TouchableOpacity
                            style={[
                                styles.documentUploadArea,
                                {
                                    borderColor: hasDocument ? colors.primary : colors.outline,
                                    backgroundColor: hasDocument ? colors.primaryContainer + '20' : colors.surface,
                                },
                            ]}
                            onPress={() => handleDocumentUpload(documentType)}
                            disabled={isUploading}
                        >
                            {isCurrentlyUploading ? (
                                <View style={styles.uploadingContainer}>
                                    <ActivityIndicator size="large" color={colors.primary} />
                                    <Text variant="bodyMedium" color="textSecondary" style={styles.uploadingText}>
                                        {t('form.uploading')}
                                    </Text>
                                </View>
                            ) : hasDocument ? (
                                <View style={styles.documentPreview}>
                                    <Image source={{ uri: fullImageUrl }} style={styles.documentImage} />
                                    <View style={styles.documentOverlay}>
                                        <MaterialCommunityIcons name="pencil" size={20} color="white" />
                                        <Text variant="labelSmall" style={styles.overlayText}>
                                            {t('documents.change')}
                                        </Text>
                                    </View>
                                </View>
                            ) : (
                                <View style={styles.uploadPlaceholder}>
                                    <MaterialCommunityIcons
                                        name="camera-plus"
                                        size={32}
                                        color={colors.textSecondary}
                                    />
                                    <Text variant="bodyMedium" color="textSecondary" style={styles.uploadText}>
                                        {t('documents.addDocument')}
                                    </Text>
                                    <Text variant="labelMedium" color="textHint" style={styles.uploadSubtext}>
                                        {t('form.documents.tapToAdd')}
                                    </Text>
                                </View>
                            )}
                        </TouchableOpacity>
                    </Card.Content>
                </Card>

                {errors[documentType] && (
                    <Text variant="labelMedium" color="error" style={styles.errorText}>
                        {errors[documentType].message}
                    </Text>
                )}
            </View>
        );
    };

    return (
        <View style={styles.container}>
            <View style={styles.header}>
                <ValidationBadge
                    isVerified={user?.validationStatus.identity.isVerified}
                    type="identity"
                    size="medium"
                />
            </View>

            <View style={styles.form}>
                {/* Sélection du type de document */}
                <View style={styles.fieldContainer}>
                    <Text variant="labelLarge" color="textPrimary" style={styles.label}>
                        {t('form.documentType')} *
                    </Text>

                    <Controller
                        control={control}
                        name="identityCardType"
                        render={({ field: { onChange, value } }) => {
                            console.log('🔍 Controller render - identityCardType:', { value, dropdownValue });

                            return (
                                <DropDownPicker
                                    open={open}
                                    value={dropdownValue}
                                    items={items}
                                    setOpen={setOpen}
                                    setValue={handleDropdownChange}
                                    setItems={setItems}
                                    placeholder={t('form.documentType')}
                                    style={[
                                        styles.dropdown,
                                        {
                                            backgroundColor: colors.surface,
                                            borderColor: errors.identityCardType ? colors.error : colors.outline,
                                            borderRadius: BORDER_RADIUS.md,
                                        }
                                    ]}
                                    dropDownContainerStyle={{
                                        backgroundColor: colors.surface,
                                        borderColor: colors.outline,
                                        borderRadius: BORDER_RADIUS.md,
                                    }}
                                    textStyle={{ color: colors.textPrimary }}
                                    listMode="SCROLLVIEW"
                                    ArrowDownIconComponent={({ style }) =>
                                        <MaterialCommunityIcons
                                            name="chevron-down"
                                            size={20}
                                            color={colors.textPrimary}
                                            style={style}
                                        />
                                    }
                                    ArrowUpIconComponent={({ style }) =>
                                        <MaterialCommunityIcons
                                            name="chevron-up"
                                            size={20}
                                            color={colors.textPrimary}
                                            style={style}
                                        />
                                    }
                                    IconContainerStyle={{ marginRight: SPACING.sm }}
                                    onChangeValue={(val) => {
                                        console.log('🔄 DropDownPicker onChangeValue:', val);
                                        onChange(val);
                                        handleDropdownChange(val);
                                    }}
                                />
                            );
                        }}
                    />

                    {errors.identityCardType && (
                        <Text variant="labelMedium" color="error" style={styles.errorText}>
                            {errors.identityCardType.message}
                        </Text>
                    )}

                    <Text variant="labelMedium" color="textHint" style={styles.helpText}>
                        {t('form.documentTypeHelp')}
                    </Text>
                </View>

                {/* Documents à uploader */}
                <DocumentCard
                    title={t('documents.identityDocument')}
                    description={t('documents.identityDescription')}
                    documentType="identityCard"
                    icon="card-account-details-outline"
                />

                <DocumentCard
                    title={t('documents.selfieWithId')}
                    description={t('documents.selfieDescription')}
                    documentType="selfieWithId"
                    icon="account-box-outline"
                />

                {/* Information sur la vérification d'identité */}
                <View style={[styles.verificationInfo, { backgroundColor: colors.surfaceVariant }]}>
                    <View style={styles.verificationHeader}>
                        <MaterialCommunityIcons
                            name="shield-check-outline"
                            size={20}
                            color={colors.primary}
                        />
                        <Text variant="labelLarge" color="textPrimary" style={styles.verificationTitle}>
                            {t('documents.identity.verification.title')}
                        </Text>
                    </View>

                    <Text variant="bodyMedium" color="textSecondary" style={styles.verificationText}>
                        {user?.isIdentityVerified
                            ? t('documents.identity.verification.verified')
                            : t('documents.identity.verification.pending')
                        }
                    </Text>

                    {!user?.isIdentityVerified && (
                        <View style={styles.verificationSteps}>
                            <Text variant="labelLarge" color="textPrimary" style={styles.verificationStepsTitle}>
                                {t('documents.identity.verification.steps')}
                            </Text>
                            <View style={styles.stepsList}>
                                <Text variant="bodySmall" color="textSecondary" style={styles.stepItem}>
                                    • {t('documents.identity.verification.step1')}
                                </Text>
                                <Text variant="bodySmall" color="textSecondary" style={styles.stepItem}>
                                    • {t('documents.identity.verification.step2')}
                                </Text>
                                <Text variant="bodySmall" color="textSecondary" style={styles.stepItem}>
                                    • {t('documents.identity.verification.step3')}
                                </Text>
                            </View>
                        </View>
                    )}
                </View>

                {/* DEBUG: Affichage temporaire des valeurs */}
                {__DEV__ && (
                    <View style={{ padding: SPACING.sm, backgroundColor: 'rgba(255,0,0,0.1)', margin: SPACING.sm }}>
                        <Text variant="labelSmall" style={{ color: 'red' }}>
                            DEBUG - identityCardType: {formData.identityCardType || 'undefined'}
                        </Text>
                        <Text variant="labelSmall" style={{ color: 'red' }}>
                            DEBUG - dropdownValue: {dropdownValue || 'undefined'}
                        </Text>
                    </View>
                )}
            </View>
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1
    },
    header: {
        marginBottom: SPACING.xxxl,
        alignItems: 'center',
        gap: SPACING.md,
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
    errorText: {
        marginTop: SPACING.xs,
    },
    helpText: {
        fontStyle: 'italic'
    },
    dropdown: {
        borderWidth: 1.5,
        minHeight: 56,
        elevation: ELEVATION.low,
    },
    documentCard: {
        marginTop: SPACING.sm,
        overflow: 'hidden',
    },
    documentHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'flex-start',
        marginBottom: SPACING.md
    },
    documentInfo: {
        flex: 1
    },
    documentTitle: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.sm,
        marginBottom: SPACING.xs,
    },
    documentDescription: {
        lineHeight: 20,
        maxWidth: '85%',
    },
    documentUploadArea: {
        borderRadius: BORDER_RADIUS.md,
        borderWidth: 2,
        borderStyle: 'dashed',
        minHeight: 120,
        justifyContent: 'center',
        alignItems: 'center'
    },
    uploadPlaceholder: {
        alignItems: 'center',
        gap: SPACING.sm,
        padding: SPACING.lg,
    },
    uploadText: {
        // Typography géré par le composant Text
    },
    uploadSubtext: {
        textAlign: 'center'
    },
    uploadingContainer: {
        alignItems: 'center',
        gap: SPACING.sm,
        padding: SPACING.lg,
    },
    uploadingText: {
        // Typography géré par le composant Text
    },
    documentPreview: {
        position: 'relative',
        width: '100%',
        height: 120,
        borderRadius: BORDER_RADIUS.md,
        overflow: 'hidden'
    },
    documentImage: {
        width: '100%',
        height: '100%',
        borderRadius: BORDER_RADIUS.md,
    },
    documentOverlay: {
        position: 'absolute',
        bottom: SPACING.sm,
        right: SPACING.sm,
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: 'rgba(0,0,0,0.7)',
        paddingHorizontal: SPACING.sm,
        paddingVertical: SPACING.xs,
        borderRadius: BORDER_RADIUS.xs,
        gap: SPACING.xs,
    },
    overlayText: {
        color: 'white',
    },
    verificationInfo: {
        borderRadius: BORDER_RADIUS.lg,
        padding: SPACING.lg,
        gap: SPACING.md,
    },
    verificationHeader: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.sm,
    },
    verificationTitle: {
        // Typography géré par le composant Text
    },
    verificationText: {
        lineHeight: 20,
    },
    verificationSteps: {
        gap: SPACING.sm,
    },
    verificationStepsTitle: {
        // Typography géré par le composant Text
    },
    stepsList: {
        gap: SPACING.xs,
        paddingLeft: SPACING.sm,
    },
    stepItem: {
        lineHeight: 18,
    }
});

export default StepTwo;