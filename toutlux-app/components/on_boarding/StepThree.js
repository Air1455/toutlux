import React, {useEffect, useState} from 'react';
import { View, StyleSheet, TouchableOpacity, ScrollView } from 'react-native';
import { Text, useTheme, ActivityIndicator, Card, Chip, RadioButton, TextInput } from 'react-native-paper';
import { Controller } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { useDocumentUpload } from '@/hooks/useDocumentUpload';
import DropDownPicker from "react-native-dropdown-picker";

const StepThree = ({ control, errors, setValue, watch }) => {
    const { colors } = useTheme();
    const { t } = useTranslation();
    const formData = watch(); // Move this up
    const [open, setOpen] = useState(false);
    const [dropdownValue, setDropdownValue] = useState(formData?.incomeSource || null); // Add optional chaining

    // Utilisation du hook pour gérer l'upload
    const { isUploading, uploadingType, handleImagePicker, getImageUrl } = useDocumentUpload();

    const [items, setItems] = useState([
        {
            label: t('income.salary'),
            value: 'salary',
            icon: () => <MaterialCommunityIcons name="cash" size={18} color={colors.onSurface} />
        },
        {
            label: t('income.business'),
            value: 'business',
            icon: () => <MaterialCommunityIcons name="briefcase" size={18} color={colors.onSurface} />
        },
        {
            label: t('income.investment'),
            value: 'investment',
            icon: () => <MaterialCommunityIcons name="chart-line" size={18} color={colors.onSurface} />
        },
        {
            label: t('income.pension'),
            value: 'pension',
            icon: () => <MaterialCommunityIcons name="account-clock" size={18} color={colors.onSurface} />
        },
        {
            label: t('income.rental'),
            value: 'rental',
            icon: () => <MaterialCommunityIcons name="home" size={18} color={colors.onSurface} />
        },
        {
            label: t('income.other'),
            value: 'other',
            icon: () => <MaterialCommunityIcons name="dots-horizontal" size={18} color={colors.onSurface} />
        }
    ]);

    const getValue = (fieldName) => {
        return formData[fieldName] || '';
    };

    // Fonction pour gérer l'upload de documents
    const handleDocumentUpload = async (documentType) => {
        try {
            const url = await handleImagePicker(documentType, true); // true = avec support PDF
            if (url) {
                setValue(documentType, url);
            }
        } catch (error) {
            // Erreur déjà gérée dans le hook
        }
    };

    useEffect(() => {
        setValue('incomeSource', dropdownValue);
    }, [dropdownValue, setValue]);

    const FinancialDocumentCard = ({ title, description, documentType, icon, isRequired = false }) => {
        const isCurrentlyUploading = uploadingType === documentType;
        const documentUri = getValue(documentType);
        const hasDocument = !!documentUri;

        return (
            <Card style={[styles.documentCard, { backgroundColor: colors.surface }]}>
                <Card.Content>
                    <View style={styles.documentHeader}>
                        <View style={styles.documentInfo}>
                            <View style={styles.documentTitle}>
                                <MaterialCommunityIcons
                                    name={icon}
                                    size={24}
                                    color={colors.primary}
                                />
                                <Text style={[styles.documentTitleText, { color: colors.onSurface }]}>
                                    {title} {isRequired && '*'}
                                </Text>
                            </View>
                            <Text style={[styles.documentDescription, { color: colors.onSurfaceVariant }]}>
                                {description}
                            </Text>
                        </View>

                        {hasDocument && (
                            <Chip
                                icon="check"
                                mode="flat"
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
                                backgroundColor: hasDocument ? colors.primaryContainer + '20' : 'transparent'
                            }
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
                                <MaterialCommunityIcons
                                    name="file-check"
                                    size={48}
                                    color={colors.primary}
                                />
                                <Text style={[styles.documentName, { color: colors.onSurface }]}>
                                    {t('form.documents.fileUploaded')}
                                </Text>
                                <View style={styles.changeButton}>
                                    <MaterialCommunityIcons
                                        name="pencil"
                                        size={16}
                                        color={colors.primary}
                                    />
                                    <Text style={[styles.changeText, { color: colors.primary }]}>
                                        {t('form.documents.change')}
                                    </Text>
                                </View>
                            </View>
                        ) : (
                            <View style={styles.uploadPlaceholder}>
                                <MaterialCommunityIcons
                                    name="file-plus"
                                    size={32}
                                    color={colors.onSurfaceVariant}
                                />
                                <Text style={[styles.uploadText, { color: colors.onSurfaceVariant }]}>
                                    {t('form.documents.addDocument')}
                                </Text>
                                <Text style={[styles.uploadSubtext, { color: colors.onSurfaceVariant }]}>
                                    {t('form.documents.supportedFormats', 'PDF, JPG, PNG')}
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
            <View style={styles.header}>
                <Text style={[styles.subtitle, { color: colors.onSurfaceVariant }]}>
                    {t('onboarding.step3.subtitle')}
                </Text>
            </View>

            <View style={styles.form}>
                <View style={styles.fieldContainer}>
                    <Text style={[styles.label, { color: colors.onSurface }]}>
                        {t('form.income.source')} *
                    </Text>

                    <Controller
                        control={control}
                        name="incomeSource"
                        render={({ field }) => (
                            <DropDownPicker
                                open={open}
                                value={dropdownValue}
                                items={items}
                                setOpen={setOpen}
                                setValue={setDropdownValue}
                                setItems={setItems}
                                placeholder={t('form.income.selectSource')}
                                style={[
                                    styles.dropdown,
                                    {
                                        backgroundColor: colors.surface,
                                        borderColor: errors.incomeSource ? colors.error : colors.outline
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
                                zIndex={3000}
                                zIndexInverse={1000}
                            />
                        )}
                    />

                    {errors.incomeSource && (
                        <Text style={[styles.errorText, { color: colors.error }]}>
                            {errors.incomeSource.message}
                        </Text>
                    )}
                </View>

                {/* Profession */}
                <View style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: colors.onSurface }]}>
                        {t('form.occupation', 'Profession')} ({t('form.optional', 'optionnel')})
                    </Text>

                    <Controller
                        control={control}
                        name="occupation"
                        render={({ field: { onChange, value } }) => (
                            <TextInput
                                mode="outlined"
                                value={value || ''}
                                onChangeText={onChange}
                                placeholder={t('form.occupationPlaceholder', 'Ex: Développeur, Enseignant, Commerçant...')}
                                style={styles.occupationInput}
                                left={
                                    <TextInput.Icon
                                        icon="briefcase-outline"
                                        iconColor={colors.onSurfaceVariant}
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
                    <Text style={[styles.sectionTitle, { color: colors.onSurface }]}>
                        {t('form.documents.financialDocuments', 'Justificatifs financiers')}
                    </Text>

                    <Text style={[styles.sectionSubtitle, { color: colors.onSurfaceVariant }]}>
                        {t('form.documents.financialNote', 'Au moins un document est requis pour compléter votre profil')}
                    </Text>

                    <View style={styles.documentsContainer}>
                        <FinancialDocumentCard
                            title={t('form.documents.incomeProof', 'Justificatif de revenus')}
                            description={t('form.documents.incomeProofDesc', 'Bulletin de salaire, attestation employeur, bilan comptable...')}
                            documentType="incomeProof"
                            icon="receipt"
                            isRequired={!getValue('ownershipProof')}
                        />

                        <FinancialDocumentCard
                            title={t('form.documents.ownershipProof', 'Justificatif de propriété')}
                            description={t('form.documents.ownershipProofDesc', 'Titre de propriété, acte notarié, contrat de vente...')}
                            documentType="ownershipProof"
                            icon="home-account"
                            isRequired={!getValue('incomeProof')}
                        />
                    </View>

                    {/* Erreurs */}
                    {(errors.incomeProof || errors.ownershipProof) && (
                        <View style={styles.errorsContainer}>
                            {errors.incomeProof && (
                                <Text style={[styles.errorText, { color: colors.error }]}>
                                    • {errors.incomeProof.message}
                                </Text>
                            )}
                            {errors.ownershipProof && (
                                <Text style={[styles.errorText, { color: colors.error }]}>
                                    • {errors.ownershipProof.message}
                                </Text>
                            )}
                        </View>
                    )}
                </View>

                {/* Information importante */}
                <View style={styles.section}>
                    <LinearGradient
                        colors={[colors.secondaryContainer + '40', colors.secondaryContainer + '20']}
                        style={styles.infoContainer}
                    >
                        <View style={styles.infoHeader}>
                            <MaterialCommunityIcons
                                name="information"
                                size={24}
                                color={colors.secondary}
                            />
                            <Text style={[styles.infoTitle, { color: colors.onSecondaryContainer }]}>
                                {t('form.documents.financialInfo.title', 'Pourquoi ces documents ?')}
                            </Text>
                        </View>
                        <Text style={[styles.infoText, { color: colors.onSecondaryContainer }]}>
                            {t('form.documents.financialInfo.message', 'Ces justificatifs permettent aux propriétaires d\'évaluer votre solvabilité et d\'accélérer vos démarches de location.')}
                        </Text>
                    </LinearGradient>
                </View>

                {/* Sécurité */}
                <View style={styles.section}>
                    <LinearGradient
                        colors={[colors.primaryContainer + '20', colors.primaryContainer + '10']}
                        style={styles.securityInfo}
                    >
                        <View style={styles.securityHeader}>
                            <MaterialCommunityIcons
                                name="shield-lock"
                                size={24}
                                color={colors.primary}
                            />
                            <Text style={[styles.securityTitle, { color: colors.onPrimaryContainer }]}>
                                {t('security.financial.title', 'Protection de vos données financières')}
                            </Text>
                        </View>
                        <Text style={[styles.securityText, { color: colors.onPrimaryContainer }]}>
                            {t('security.financial.message', 'Vos documents financiers sont chiffrés avec les plus hauts standards de sécurité.')}
                        </Text>
                    </LinearGradient>
                </View>
            </View>
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    header: {
        marginBottom: 32,
        alignItems: 'center',
    },
    title: {
        fontSize: 24,
        fontWeight: 'bold',
        textAlign: 'center',
        marginBottom: 8,
    },
    subtitle: {
        fontSize: 16,
        textAlign: 'center',
        lineHeight: 22,
    },
    section: {
        marginBottom: 24,
    },
    sectionTitle: {
        fontSize: 18,
        fontWeight: '600',
        marginBottom: 8,
    },
    sectionSubtitle: {
        fontSize: 14,
        marginBottom: 16,
        lineHeight: 20,
    },
    dropdown: {
        fontSize: 16,
        borderRadius: 8,
        borderWidth: 1.5,
        minHeight: 56,
    },
    incomeSourceContainer: {
        gap: 8,
    },
    incomeSourceCard: {
        padding: 16,
        borderRadius: 12,
        borderWidth: 2,
    },
    incomeSourceHeader: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 12,
        marginBottom: 4,
    },
    incomeSourceLabel: {
        fontSize: 16,
        flex: 1,
    },
    incomeSourceDescription: {
        fontSize: 14,
        lineHeight: 18,
        marginLeft: 36,
    },
    occupationInput: {
        fontSize: 16,
        marginTop: 8,
    },
    documentsContainer: {
        gap: 16,
    },
    documentCard: {
        borderRadius: 12,
        elevation: 2,
    },
    documentHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'flex-start',
        marginBottom: 12,
    },
    documentInfo: {
        flex: 1,
    },
    documentTitle: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 8,
        marginBottom: 4,
    },
    documentTitleText: {
        fontSize: 16,
        fontWeight: '600',
    },
    documentDescription: {
        fontSize: 14,
        lineHeight: 20,
    },
    documentUploadArea: {
        borderRadius: 8,
        borderWidth: 2,
        borderStyle: 'dashed',
        minHeight: 100,
        justifyContent: 'center',
        alignItems: 'center',
    },
    uploadPlaceholder: {
        alignItems: 'center',
        gap: 8,
        padding: 16,
    },
    uploadText: {
        fontSize: 14,
        fontWeight: '500',
    },
    uploadSubtext: {
        fontSize: 12,
    },
    uploadingContainer: {
        alignItems: 'center',
        gap: 8,
        padding: 16,
    },
    uploadingText: {
        fontSize: 14,
    },
    documentPreview: {
        alignItems: 'center',
        gap: 8,
        padding: 16,
    },
    documentName: {
        fontSize: 14,
        fontWeight: '500',
    },
    changeButton: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 4,
        marginTop: 4,
    },
    changeText: {
        fontSize: 12,
        textDecorationLine: 'underline',
    },
    errorsContainer: {
        marginTop: 12,
        gap: 4,
    },
    errorText: {
        fontSize: 14,
    },
    infoContainer: {
        padding: 16,
        borderRadius: 12,
        borderWidth: 1,
        borderColor: 'rgba(0,0,0,0.1)',
    },
    infoHeader: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 12,
        marginBottom: 8,
    },
    infoTitle: {
        fontSize: 16,
        fontWeight: '600',
    },
    infoText: {
        fontSize: 14,
        lineHeight: 20,
    },
    securityInfo: {
        padding: 16,
        borderRadius: 12,
        borderWidth: 1,
        borderColor: 'rgba(0,0,0,0.1)',
    },
    securityHeader: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 12,
        marginBottom: 8,
    },
    securityTitle: {
        fontSize: 16,
        fontWeight: '600',
    },
    securityText: {
        fontSize: 14,
        lineHeight: 20,
    },
});

export default StepThree;