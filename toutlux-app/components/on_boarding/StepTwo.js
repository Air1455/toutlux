import React, { useState, useEffect } from 'react';
import { View, StyleSheet, TouchableOpacity, Image, Alert  } from 'react-native';
import { Text, useTheme, ActivityIndicator, Card, Chip } from 'react-native-paper';
import { Controller } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import DropDownPicker from 'react-native-dropdown-picker';
import { useDocumentUpload } from '@/hooks/useDocumentUpload';

const StepTwo = ({ control, errors, setValue, watch }) => {
    const { colors } = useTheme();
    const { t } = useTranslation();

    const { isUploading, uploadingType, openCamera, openGallery, getImageUrl } = useDocumentUpload();
    const formData = watch();

    // État local pour le dropdown
    const [open, setOpen] = useState(false);
    const [dropdownValue, setDropdownValue] = useState(formData.identityCardType || null);
    const [items, setItems] = useState([
        {
            label: t('documents.nationalId'),
            value: 'national_id',
            icon: () => <MaterialCommunityIcons name="card-account-details" size={18} color={colors.onSurface} />
        },
        {
            label: t('documents.passport'),
            value: 'passport',
            icon: () => <MaterialCommunityIcons name="passport" size={18} color={colors.onSurface} />
        },
        {
            label: t('documents.drivingLicense'),
            value: 'driving_license',
            icon: () => <MaterialCommunityIcons name="car" size={18} color={colors.onSurface} />
        },
    ]);

    // Synchronisation avec react-hook-form
    useEffect(() => {
        setValue('identityCardType', dropdownValue);
    }, [dropdownValue, setValue]);

    const getValue = (fieldName) => formData[fieldName] || '';

    // ✅ CORRECTION: Logique identique à Step1
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
            // ✅ CORRECTION: Utiliser fieldContainer comme Step1
            <View style={styles.fieldContainer}>
                <Text style={[styles.label, { color: colors.onSurface }]}>
                    {title} *
                </Text>

                <Card style={[styles.documentCard, { backgroundColor: colors.surface }]}>
                    <Card.Content>
                        <View style={styles.documentHeader}>
                            <View style={styles.documentInfo}>
                                <View style={styles.documentTitle}>
                                    <MaterialCommunityIcons name={icon} size={20} color={colors.primary} />
                                    <Text style={[styles.documentDescription, { color: colors.onSurfaceVariant }]}>
                                        {description}
                                    </Text>
                                </View>
                            </View>
                            {hasDocument && (
                                <Chip
                                    icon="check"
                                    style={{ backgroundColor: colors.primaryContainer }}
                                    textStyle={{ color: colors.onPrimaryContainer }}
                                >
                                    {t('form.documents.uploaded')}
                                </Chip>
                            )}
                        </View>

                        <TouchableOpacity
                            style={[
                                styles.documentUploadArea,
                                {
                                    borderColor: hasDocument ? colors.primary : colors.outline,
                                    backgroundColor: hasDocument ? colors.primaryContainer + '20' : 'transparent',
                                },
                            ]}
                            onPress={() => handleDocumentUpload(documentType)}
                            disabled={isUploading}
                        >
                            {isCurrentlyUploading ? (
                                <View style={styles.uploadingContainer}>
                                    <ActivityIndicator size="large" color={colors.primary} />
                                    <Text style={[styles.uploadingText, { color: colors.onSurfaceVariant }]}>
                                        {t('form.uploading')}
                                    </Text>
                                </View>
                            ) : hasDocument ? (
                                <View style={styles.documentPreview}>
                                    <Image source={{ uri: fullImageUrl }} style={styles.documentImage} />
                                    <View style={styles.documentOverlay}>
                                        <MaterialCommunityIcons name="pencil" size={20} color="white" />
                                        <Text style={styles.overlayText}>{t('form.documents.change')}</Text>
                                    </View>
                                </View>
                            ) : (
                                <View style={styles.uploadPlaceholder}>
                                    <MaterialCommunityIcons name="camera-plus" size={32} color={colors.onSurfaceVariant} />
                                    <Text style={[styles.uploadText, { color: colors.onSurfaceVariant }]}>
                                        {t('form.documents.addPhoto')}
                                    </Text>
                                    <Text style={[styles.uploadSubtext, { color: colors.onSurfaceVariant }]}>
                                        {t('form.documents.tapToAdd')}
                                    </Text>
                                </View>
                            )}
                        </TouchableOpacity>
                    </Card.Content>
                </Card>

                {/* ✅ CORRECTION: Gestion des erreurs comme Step1 */}
                {errors[documentType] && (
                    <Text style={[styles.errorText, { color: colors.error }]}>
                        {errors[documentType].message}
                    </Text>
                )}
            </View>
        );
    };

    return (
        // ✅ CORRECTION: Structure identique à Step1
        <View style={styles.container}>
            {/* ✅ AJOUT: Header comme Step1 */}
            <View style={styles.header}>
                <Text style={[styles.subtitle, { color: colors.onSurfaceVariant }]}>
                    {t('onboarding.step2.subtitle')}
                </Text>
            </View>

            {/* ✅ CORRECTION: Form container comme Step1 */}
            <View style={styles.form}>
                {/* Sélection du type de document */}
                <View style={styles.fieldContainer}>
                    <Text style={[styles.label, { color: colors.onSurface }]}>
                        {t('form.documents.selectType')} *
                    </Text>

                    <Controller
                        control={control}
                        name="identityCardType"
                        render={({ field }) => (
                            <DropDownPicker
                                open={open}
                                value={dropdownValue}
                                items={items}
                                setOpen={setOpen}
                                setValue={setDropdownValue}
                                setItems={setItems}
                                placeholder={t('form.documents.selectType')}
                                style={[
                                    styles.dropdown,
                                    {
                                        backgroundColor: colors.surface,
                                        borderColor: errors.identityCardType ? colors.error : colors.outline
                                    }
                                ]}
                                dropDownContainerStyle={{
                                    backgroundColor: colors.surface,
                                    borderColor: colors.outline
                                }}
                                textStyle={{ color: colors.onSurface }}
                                listMode="SCROLLVIEW"
                                ArrowDownIconComponent={({ style }) =>
                                    <MaterialCommunityIcons name="chevron-down" size={20} color={colors.onSurface} style={style} />
                                }
                                ArrowUpIconComponent={({ style }) =>
                                    <MaterialCommunityIcons name="chevron-up" size={20} color={colors.onSurface} style={style} />
                                }
                                IconContainerStyle={{ marginRight: 10 }}
                            />
                        )}
                    />

                    {errors.identityCardType && (
                        <Text style={[styles.errorText, { color: colors.error }]}>
                            {errors.identityCardType.message}
                        </Text>
                    )}

                    {/* ✅ AJOUT: Texte d'aide comme Step1 */}
                    <Text style={[styles.helpText, { color: colors.onSurfaceVariant }]}>
                        {t('form.documents.typeHelp')}
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
            </View>
        </View>
    );
};

// ✅ CORRECTION: Styles alignés avec Step1
const styles = StyleSheet.create({
    // Structure identique à Step1
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
    errorText: {
        fontSize: 14,
        marginTop: 4
    },
    helpText: {
        fontSize: 12,
        fontStyle: 'italic'
    },

    // Styles spécifiques à Step2
    dropdown: {
        fontSize: 16,
        borderRadius: 8,
        borderWidth: 1.5,
        minHeight: 56,
    },
    documentCard: {
        marginTop: 8,
        borderRadius: 12,
        elevation: 2,
    },
    documentHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'flex-start',
        marginBottom: 12
    },
    documentInfo: {
        flex: 1
    },
    documentTitle: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 8,
        marginBottom: 4,
    },
    documentDescription: {
        fontSize: 14,
        lineHeight: 20,
    },
    documentUploadArea: {
        borderRadius: 8,
        borderWidth: 2,
        borderStyle: 'dashed',
        minHeight: 120,
        justifyContent: 'center',
        alignItems: 'center'
    },
    uploadPlaceholder: {
        alignItems: 'center',
        gap: 8,
        padding: 16
    },
    uploadText: {
        fontSize: 14,
        fontWeight: '500'
    },
    uploadSubtext: {
        fontSize: 12,
        textAlign: 'center'
    },
    uploadingContainer: {
        alignItems: 'center',
        gap: 8,
        padding: 16
    },
    uploadingText: {
        fontSize: 12
    },
    documentPreview: {
        position: 'relative',
        width: '100%',
        height: 120,
        borderRadius: 6,
        overflow: 'hidden'
    },
    documentImage: {
        width: '100%',
        height: '100%',
        borderRadius: 6
    },
    documentOverlay: {
        position: 'absolute',
        bottom: 8,
        right: 8,
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: 'rgba(0,0,0,0.7)',
        paddingHorizontal: 8,
        paddingVertical: 4,
        borderRadius: 4,
        gap: 4,
    },
    overlayText: {
        color: 'white',
        fontSize: 12
    },
});

export default StepTwo;