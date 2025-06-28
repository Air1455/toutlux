import React, { useState } from 'react';
import { View, StyleSheet, ScrollView, Image, TouchableOpacity, Alert } from 'react-native';
import { IconButton, useTheme, ActivityIndicator } from 'react-native-paper';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { useTranslation } from 'react-i18next';

import Text from '@/components/typography/Text';
import { useDocumentUpload } from '@/hooks/useDocumentUpload';
import { SPACING, BORDER_RADIUS, ELEVATION } from '@/constants/spacing';

// Types de documents constants
const DOCUMENT_TYPES = {
    LISTING_MAIN: 'house_main_image',
    LISTING_OTHER: 'house_additional_image',
    PROFILE_PHOTO: 'profile_photo',
    ID_CARD: 'id_card',
    SELFIE_WITH_ID: 'selfie_with_id',
    PROOF_OF_ADDRESS: 'proof_of_address',
};

const ImageUploadSection = ({
                                firstImage,
                                otherImages = [],
                                onFirstImageChange,
                                onOtherImagesChange,
                                error,
                                maxOtherImages = 10
                            }) => {
    const { colors } = useTheme();
    const { t } = useTranslation();
    const { isUploading, uploadingType, handleImagePicker, getImageUrl } = useDocumentUpload();

    const [uploadingIndex, setUploadingIndex] = useState(null);

    const handleFirstImageUpload = async () => {
        try {
            setUploadingIndex('first');
            const url = await handleImagePicker(DOCUMENT_TYPES.LISTING_MAIN, false);
            if (url) {
                onFirstImageChange(url);
            }
        } catch (error) {
            console.error('Error uploading first image:', error);
            Alert.alert(
                t('common.error'),
                t('listings.imageUploadError')
            );
        } finally {
            setUploadingIndex(null);
        }
    };

    const handleOtherImageUpload = async () => {
        if (otherImages.length >= maxOtherImages) {
            Alert.alert(
                t('common.error'),
                t('listings.maxImagesReached', { max: maxOtherImages })
            );
            return;
        }

        try {
            const nextIndex = otherImages.length;
            setUploadingIndex(nextIndex);
            const url = await handleImagePicker(DOCUMENT_TYPES.LISTING_OTHER, false);
            if (url) {
                const newOtherImages = [...otherImages, url];
                onOtherImagesChange(newOtherImages);
            }
        } catch (error) {
            console.error('Error uploading other image:', error);
            Alert.alert(
                t('common.error'),
                t('listings.imageUploadError')
            );
        } finally {
            setUploadingIndex(null);
        }
    };

    const removeOtherImage = (indexToRemove) => {
        Alert.alert(
            t('listings.removeImage'),
            t('listings.removeImageConfirmation'),
            [
                { text: t('common.cancel'), style: 'cancel' },
                {
                    text: t('common.remove'),
                    style: 'destructive',
                    onPress: () => {
                        const newOtherImages = otherImages.filter((_, index) => index !== indexToRemove);
                        onOtherImagesChange(newOtherImages);
                    }
                }
            ]
        );
    };

    const replaceFirstImage = () => {
        Alert.alert(
            t('listings.replaceImage'),
            t('listings.replaceImageConfirmation'),
            [
                { text: t('common.cancel'), style: 'cancel' },
                {
                    text: t('common.replace'),
                    onPress: handleFirstImageUpload
                }
            ]
        );
    };

    const renderFirstImageUpload = () => (
        <View style={styles.firstImageSection}>
            <View style={styles.sectionHeader}>
                <Text variant="cardTitle" color="textPrimary">
                    {t('listings.form.mainImage')} *
                </Text>
                {firstImage && (
                    <IconButton
                        icon="image-edit"
                        size={20}
                        onPress={replaceFirstImage}
                        iconColor={colors.primary}
                    />
                )}
            </View>

            <TouchableOpacity
                style={[
                    styles.firstImageContainer,
                    {
                        borderColor: error ? colors.error : colors.outline,
                        backgroundColor: colors.surface
                    }
                ]}
                onPress={handleFirstImageUpload}
                disabled={uploadingIndex === 'first'}
            >
                {uploadingIndex === 'first' ? (
                    <View style={styles.uploadingContainer}>
                        <ActivityIndicator size="large" color={colors.primary} />
                        <Text variant="bodyMedium" color="textSecondary" style={styles.uploadingText}>
                            {t('common.uploading')}
                        </Text>
                    </View>
                ) : firstImage ? (
                    <View style={styles.imagePreview}>
                        <Image
                            source={{ uri: getImageUrl(firstImage) }}
                            style={styles.firstImage}
                            resizeMode="cover"
                        />
                        <View style={styles.imageOverlay}>
                            <IconButton
                                icon="pencil"
                                iconColor={colors.onPrimary}
                                containerColor={colors.primary}
                                size={20}
                                onPress={replaceFirstImage}
                            />
                        </View>
                    </View>
                ) : (
                    <View style={styles.uploadPlaceholder}>
                        <MaterialCommunityIcons
                            name="camera-plus"
                            size={48}
                            color={colors.textSecondary}
                        />
                        <Text variant="bodyLarge" color="textSecondary" style={styles.uploadText}>
                            {t('listings.form.addMainImage')}
                        </Text>
                        <Text variant="bodyMedium" color="textHint" style={styles.uploadSubtext}>
                            {t('listings.form.mainImageDescription')}
                        </Text>
                    </View>
                )}
            </TouchableOpacity>

            {error && (
                <Text variant="labelMedium" color="error" style={styles.errorText}>
                    {error}
                </Text>
            )}
        </View>
    );

    const renderOtherImagesUpload = () => (
        <View style={styles.otherImagesSection}>
            <View style={styles.sectionHeader}>
                <Text variant="cardTitle" color="textPrimary">
                    {t('listings.form.otherImages')}
                </Text>
                <Text variant="labelMedium" color="textSecondary" style={styles.imageCount}>
                    {otherImages.length}/{maxOtherImages}
                </Text>
            </View>

            <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.otherImagesContainer}
            >
                {/* Images existantes */}
                {otherImages.map((imageUrl, index) => (
                    <View key={`image-${index}`} style={styles.otherImageItem}>
                        <Image
                            source={{ uri: getImageUrl(imageUrl) }}
                            style={styles.otherImage}
                            resizeMode="cover"
                        />
                        <TouchableOpacity
                            style={[styles.removeButton, { backgroundColor: colors.error }]}
                            onPress={() => removeOtherImage(index)}
                        >
                            <MaterialCommunityIcons
                                name="close"
                                size={16}
                                color={colors.onError}
                            />
                        </TouchableOpacity>
                        <View style={[styles.imageNumber, { backgroundColor: colors.primary }]}>
                            <Text variant="labelSmall" style={styles.imageNumberText}>{index + 1}</Text>
                        </View>
                    </View>
                ))}

                {/* Bouton d'ajout */}
                {otherImages.length < maxOtherImages && (
                    <TouchableOpacity
                        style={[
                            styles.addImageButton,
                            {
                                borderColor: colors.outline,
                                backgroundColor: colors.surface
                            }
                        ]}
                        onPress={handleOtherImageUpload}
                        disabled={uploadingIndex !== null}
                    >
                        {uploadingIndex !== null && uploadingIndex !== 'first' ? (
                            <ActivityIndicator size="small" color={colors.primary} />
                        ) : (
                            <>
                                <MaterialCommunityIcons
                                    name="plus"
                                    size={32}
                                    color={colors.textSecondary}
                                />
                                <Text variant="labelMedium" color="textSecondary" style={styles.addButtonText}>
                                    {t('listings.form.addImage')}
                                </Text>
                            </>
                        )}
                    </TouchableOpacity>
                )}
            </ScrollView>

            <Text variant="labelMedium" color="textHint" style={styles.helperText}>
                {t('listings.form.otherImagesDescription')}
            </Text>
        </View>
    );

    return (
        <View style={styles.container}>
            {renderFirstImageUpload()}
            {renderOtherImagesUpload()}
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        gap: SPACING.xl,
    },
    firstImageSection: {
        gap: SPACING.md,
    },
    sectionHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
    },
    imageCount: {
        // Typography géré par le composant Text
    },
    firstImageContainer: {
        height: 200,
        borderRadius: BORDER_RADIUS.lg,
        borderWidth: 2,
        borderStyle: 'dashed',
        overflow: 'hidden',
        elevation: ELEVATION.low,
    },
    uploadPlaceholder: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
        gap: SPACING.sm,
        padding: SPACING.lg,
    },
    uploadText: {
        textAlign: 'center',
    },
    uploadSubtext: {
        textAlign: 'center',
        paddingHorizontal: SPACING.lg,
    },
    uploadingContainer: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
        gap: SPACING.md,
    },
    uploadingText: {
        // Typography géré par le composant Text
    },
    imagePreview: {
        flex: 1,
        position: 'relative',
    },
    firstImage: {
        width: '100%',
        height: '100%',
        backgroundColor: '#f0f0f0',
    },
    imageOverlay: {
        position: 'absolute',
        top: SPACING.md,
        right: SPACING.md,
        backgroundColor: 'rgba(0,0,0,0.3)',
        borderRadius: BORDER_RADIUS.lg,
    },
    otherImagesSection: {
        gap: SPACING.md,
    },
    otherImagesContainer: {
        paddingRight: SPACING.lg,
        gap: SPACING.md,
    },
    otherImageItem: {
        position: 'relative',
    },
    otherImage: {
        width: 100,
        height: 100,
        borderRadius: BORDER_RADIUS.md,
        backgroundColor: '#f0f0f0',
    },
    removeButton: {
        position: 'absolute',
        top: -SPACING.sm,
        right: -SPACING.sm,
        width: 24,
        height: 24,
        borderRadius: BORDER_RADIUS.md,
        justifyContent: 'center',
        alignItems: 'center',
        elevation: ELEVATION.medium,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.2,
        shadowRadius: 2,
    },
    imageNumber: {
        position: 'absolute',
        bottom: SPACING.xs,
        left: SPACING.xs,
        width: 24,
        height: 24,
        borderRadius: BORDER_RADIUS.md,
        justifyContent: 'center',
        alignItems: 'center',
    },
    imageNumberText: {
        color: '#fff',
    },
    addImageButton: {
        width: 100,
        height: 100,
        borderRadius: BORDER_RADIUS.md,
        borderWidth: 2,
        borderStyle: 'dashed',
        justifyContent: 'center',
        alignItems: 'center',
        gap: SPACING.xs,
        elevation: ELEVATION.low,
    },
    addButtonText: {
        textAlign: 'center',
    },
    helperText: {
        paddingHorizontal: SPACING.xs,
        fontStyle: 'italic',
    },
    errorText: {
        paddingHorizontal: SPACING.xs,
    },
});

export default ImageUploadSection;