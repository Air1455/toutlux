import React, { useEffect, useState } from 'react';
import { View, StyleSheet, TouchableOpacity, Image } from 'react-native';
import { useTheme, ActivityIndicator, Card, TextInput } from 'react-native-paper';
import { Controller } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import DropDownPicker from "react-native-dropdown-picker";

import Text from '@/components/typography/Text';
import { useDocumentUpload } from '@/hooks/useDocumentUpload';
import { SPACING, BORDER_RADIUS, ELEVATION } from '@/constants/spacing';

const StepThree = ({ control, errors, setValue, watch, user }) => {
    const { colors } = useTheme();
    const { t } = useTranslation();
    const formData = watch();

    // État local pour le dropdown avec synchronisation
    const [open, setOpen] = useState(false);
    const [dropdownValue, setDropdownValue] = useState(null);

    const { isUploading, uploadingType, handleImagePicker, getImageUrl } = useDocumentUpload();

    const [items, setItems] = useState([
        {
            label: t('income.salary'),
            value: 'salary',
            icon: () => <MaterialCommunityIcons name="cash" size={18} color={colors.textPrimary} />
        },
        {
            label: t('income.business'),
            value: 'business',
            icon: () => <MaterialCommunityIcons name="briefcase" size={18} color={colors.textPrimary} />
        },
        {
            label: t('income.investment'),
            value: 'investment',
            icon: () => <MaterialCommunityIcons name="chart-line" size={18} color={colors.textPrimary} />
        },
        {
            label: t('income.pension'),
            value: 'pension',
            icon: () => <MaterialCommunityIcons name="account-clock" size={18} color={colors.textPrimary} />
        },
        {
            label: t('income.rental'),
            value: 'rental',
            icon: () => <MaterialCommunityIcons name="home" size={18} color={colors.textPrimary} />
        },
        {
            label: t('income.other'),
            value: 'other',
            icon: () => <MaterialCommunityIcons name="dots-horizontal" size={18} color={colors.textPrimary} />
        }
    ]);

    // Synchronisation bidirectionnelle améliorée
    useEffect(() => {
        const currentValue = formData.incomeSource;
        console.log('🔍 StepThree - incomeSource changed:', {
            currentValue,
            dropdownValue,
            formData: formData
        });

        if (currentValue && currentValue !== dropdownValue) {
            console.log('🔄 Updating dropdown value from form data');
            setDropdownValue(currentValue);
        } else if (!currentValue && dropdownValue) {
            console.log('🔄 Form data empty but dropdown has value, syncing...');
            setValue('incomeSource', dropdownValue);
        }
    }, [formData.incomeSource, dropdownValue, setValue]);

    // Gestionnaire de changement du dropdown
    const handleDropdownChange = (value) => {
        console.log('📝 Income source dropdown changed to:', value);
        setDropdownValue(value);
        setValue('incomeSource', value);

        // Force trigger validation
        setTimeout(() => {
            const currentFormData = watch();
            console.log('✅ After income source change, form data:', {
                incomeSource: currentFormData.incomeSource,
                allData: currentFormData
            });
        }, 100);
    };

    const getValue = (fieldName) => {
        return formData[fieldName] || '';
    };

    const handleDocumentUpload = async (documentType) => {
        try {
            const url = await handleImagePicker(documentType, true);
            if (url) {
                setValue(documentType, url);
            }
        } catch (error) {
            // Erreur déjà gérée dans le hook
        }
    };

    // Vérifier si l'utilisateur a des documents financiers validés
    const hasValidatedFinancialDocs = () => {
        return !!(getValue('incomeProof') || getValue('ownershipProof'));
    };

    const FinancialDocumentCard = ({ title, description, documentType, icon, isRequired = false }) => {
        const isCurrentlyUploading = isUploading && uploadingType === documentType;
        const documentUri = getValue(documentType);
        const hasDocument = !!documentUri;
        const fullImageUrl = hasDocument ? getImageUrl(documentUri) : null;

        return (
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
                                <MaterialCommunityIcons
                                    name={icon}
                                    size={20}
                                    color={colors.primary}
                                />
                                <Text variant="labelLarge" color="textPrimary" style={styles.documentTitleText}>
                                    {title} {isRequired && '*'}
                                </Text>
                            </View>
                            <Text variant="bodyMedium" color="textSecondary" style={styles.documentDescription}>
                                {description}
                            </Text>
                        </View>
                    </View>

                    <TouchableOpacity
                        style={[
                            styles.documentUploadArea,
                            {
                                borderColor: hasDocument ? colors.primary : colors.outline,
                                backgroundColor: hasDocument ? colors.primaryContainer + '20' : colors.surface
                            }
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
        );
    };

    return (
        <View style={styles.container}>
            <View style={styles.form}>
                <View style={styles.fieldContainer}>
                    <Text variant="labelLarge" color="textPrimary" style={styles.label}>
                        {t('form.income.source')} *
                    </Text>

                    <Controller
                        control={control}
                        name="incomeSource"
                        render={({ field: { onChange, value } }) => {
                            console.log('🔍 Controller render - incomeSource:', { value, dropdownValue });

                            return (
                                <DropDownPicker
                                    open={open}
                                    value={dropdownValue}
                                    items={items}
                                    setOpen={setOpen}
                                    setValue={handleDropdownChange}
                                    setItems={setItems}
                                    placeholder={t('form.income.selectSource')}
                                    style={[
                                        styles.dropdown,
                                        {
                                            backgroundColor: colors.surface,
                                            borderColor: errors.incomeSource ? colors.error : colors.outline,
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
                                    zIndex={3000}
                                    zIndexInverse={1000}
                                    onChangeValue={(val) => {
                                        console.log('🔄 DropDownPicker onChangeValue (incomeSource):', val);
                                        onChange(val);
                                        handleDropdownChange(val);
                                    }}
                                />
                            );
                        }}
                    />

                    {errors.incomeSource && (
                        <Text variant="labelMedium" color="error" style={styles.errorText}>
                            {errors.incomeSource.message}
                        </Text>
                    )}
                </View>

                {/* Profession */}
                <View style={styles.section}>
                    <Text variant="cardTitle" color="textPrimary" style={styles.sectionTitle}>
                        {t('form.occupation')} ({t('form.optional')})
                    </Text>

                    <Controller
                        control={control}
                        name="occupation"
                        render={({ field: { onChange, value } }) => (
                            <TextInput
                                mode="outlined"
                                value={value || ''}
                                onChangeText={onChange}
                                placeholder={t('form.occupationPlaceholder')}
                                style={[styles.occupationInput, { borderRadius: BORDER_RADIUS.md }]}
                                left={
                                    <TextInput.Icon
                                        icon="briefcase-outline"
                                        iconColor={colors.textSecondary}
                                    />
                                }
                                multiline={false}
                                maxLength={100}
                            />
                        )}
                    />
                </View>

                {/* Documents financiers */}
                <View style={styles.section}>
                    <View style={styles.sectionHeaderWithStatus}>
                        <Text variant="cardTitle" color="textPrimary" style={styles.sectionTitle}>
                            {t('documents.financialDocuments')}
                        </Text>

                        {/* Indicateur de statut des documents */}
                        {hasValidatedFinancialDocs() && (
                            <View style={styles.documentStatusContainer}>
                                <MaterialCommunityIcons
                                    name="check-circle"
                                    size={20}
                                    color={colors.primary}
                                />
                            </View>
                        )}
                    </View>

                    <Text variant="bodyMedium" color="textSecondary" style={styles.sectionSubtitle}>
                        {t('documents.financialNote')}
                    </Text>

                    <View style={styles.documentsContainer}>
                        <FinancialDocumentCard
                            title={t('documents.incomeProof')}
                            description={t('documents.incomeProofDesc')}
                            documentType="incomeProof"
                            icon="receipt"
                            isRequired={!getValue('ownershipProof')}
                        />

                        <FinancialDocumentCard
                            title={t('documents.ownershipProof')}
                            description={t('documents.ownershipProofDesc')}
                            documentType="ownershipProof"
                            icon="home-account"
                            isRequired={!getValue('incomeProof')}
                        />
                    </View>

                    {(errors.incomeProof || errors.ownershipProof) && (
                        <View style={styles.errorsContainer}>
                            {errors.incomeProof && (
                                <Text variant="labelMedium" color="error" style={styles.errorText}>
                                    • {errors.incomeProof.message}
                                </Text>
                            )}
                            {errors.ownershipProof && (
                                <Text variant="labelMedium" color="error" style={styles.errorText}>
                                    • {errors.ownershipProof.message}
                                </Text>
                            )}
                        </View>
                    )}
                </View>

                {/* Information importante */}
                <View style={styles.section}>
                    <View style={styles.infoContainer}>
                        <LinearGradient
                            colors={[colors.secondaryContainer + '40', colors.secondaryContainer + '20']}
                            style={[styles.infoGradientBackground, { borderRadius: BORDER_RADIUS.lg }]}
                        >
                            <View style={styles.infoHeader}>
                                <MaterialCommunityIcons
                                    name="information"
                                    size={24}
                                    color={colors.secondary}
                                />
                                <Text variant="labelLarge" color="textPrimary" style={styles.infoTitle}>
                                    {t('documents.financialInfo.title')}
                                </Text>
                            </View>
                            <Text variant="bodyMedium" color="textSecondary" style={styles.infoText}>
                                {t('documents.financialInfo.message')}
                            </Text>

                            {/* Information sur l'état des documents */}
                            {hasValidatedFinancialDocs() && (
                                <View style={styles.statusUpdate}>
                                    <MaterialCommunityIcons
                                        name="check-circle"
                                        size={16}
                                        color={colors.secondary}
                                    />
                                    <Text variant="labelMedium" color="textSecondary" style={styles.statusText}>
                                        {t('documents.financial.uploaded')}
                                    </Text>
                                </View>
                            )}
                        </LinearGradient>
                    </View>
                </View>

                {/* Sécurité */}
                <View style={styles.section}>
                    <View style={styles.securityInfo}>
                        <LinearGradient
                            colors={[colors.primaryContainer + '30', colors.primaryContainer + '15']}
                            style={[styles.gradientBackground, { borderRadius: BORDER_RADIUS.lg }]}
                        >
                            <View style={styles.securityHeader}>
                                <MaterialCommunityIcons
                                    name="shield-lock"
                                    size={24}
                                    color={colors.primary}
                                />
                                <Text variant="labelLarge" color="textPrimary" style={styles.securityTitle}>
                                    {t('security.financial.title')}
                                </Text>
                            </View>
                            <Text variant="bodyMedium" color="textSecondary" style={styles.securityText}>
                                {t('security.financial.message')}
                            </Text>
                        </LinearGradient>
                    </View>
                </View>

                {/* DEBUG: Affichage temporaire des valeurs */}
                {__DEV__ && (
                    <View style={{ padding: SPACING.sm, backgroundColor: 'rgba(0,255,0,0.1)', margin: SPACING.sm }}>
                        <Text variant="labelSmall" style={{ color: 'green' }}>
                            DEBUG - incomeSource: {formData.incomeSource || 'undefined'}
                        </Text>
                        <Text variant="labelSmall" style={{ color: 'green' }}>
                            DEBUG - dropdownValue: {dropdownValue || 'undefined'}
                        </Text>
                        <Text variant="labelSmall" style={{ color: 'green' }}>
                            DEBUG - occupation: {formData.occupation || 'undefined'}
                        </Text>
                    </View>
                )}
            </View>
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    form: {
        gap: SPACING.xl
    },
    section: {
        marginBottom: SPACING.xl,
    },
    sectionTitle: {
        marginBottom: SPACING.sm,
    },
    sectionHeaderWithStatus: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: SPACING.sm,
    },
    documentStatusContainer: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.xs,
    },
    sectionSubtitle: {
        marginBottom: SPACING.lg,
        lineHeight: 20,
    },
    fieldContainer: {
        gap: SPACING.sm,
        marginBottom: SPACING.xl,
    },
    label: {
        marginBottom: SPACING.xs,
    },
    dropdown: {
        borderWidth: 1.5,
        minHeight: 56,
        elevation: ELEVATION.low,
    },
    occupationInput: {
        marginTop: SPACING.sm,
    },
    documentsContainer: {
        gap: SPACING.lg,
    },
    documentCard: {
        overflow: 'hidden',
    },
    documentHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'flex-start',
        marginBottom: SPACING.md,
    },
    documentInfo: {
        flex: 1,
    },
    documentTitle: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.sm,
        marginBottom: SPACING.xs,
    },
    documentTitleText: {
        // Typography géré par le composant Text
    },
    documentDescription: {
        lineHeight: 20,
    },
    documentUploadArea: {
        borderRadius: BORDER_RADIUS.md,
        borderWidth: 2,
        borderStyle: 'dashed',
        minHeight: 100,
        justifyContent: 'center',
        alignItems: 'center',
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
        // Typography géré par le composant Text
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
    errorsContainer: {
        marginTop: SPACING.md,
        gap: SPACING.xs,
    },
    errorText: {
        // Typography géré par le composant Text
    },
    infoContainer: {
        borderWidth: 1,
        borderColor: 'rgba(0,0,0,0.08)',
        overflow: 'hidden',
        borderRadius: BORDER_RADIUS.lg,
    },
    infoGradientBackground: {
        padding: SPACING.lg,
        width: '100%',
    },
    infoHeader: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.md,
        marginBottom: SPACING.sm,
        width: '100%',
    },
    infoTitle: {
        flex: 1,
        flexWrap: 'wrap',
    },
    infoText: {
        lineHeight: 20,
        width: '100%',
    },
    statusUpdate: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.xs,
        marginTop: SPACING.sm,
        paddingTop: SPACING.sm,
        borderTopWidth: 1,
        borderTopColor: 'rgba(0,0,0,0.1)',
        width: '100%',
    },
    statusText: {
        flex: 1,
        flexWrap: 'wrap',
    },
    securityInfo: {
        borderWidth: 1,
        borderColor: 'rgba(0,0,0,0.08)',
        overflow: 'hidden',
        borderRadius: BORDER_RADIUS.lg,
    },
    gradientBackground: {
        padding: SPACING.lg,
        width: '100%',
    },
    securityHeader: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.md,
        marginBottom: SPACING.sm,
        width: '100%',
    },
    securityTitle: {
        flex: 1,
        flexWrap: 'wrap',
    },
    securityText: {
        lineHeight: 20,
        width: '100%',
    },
});

export default StepThree;