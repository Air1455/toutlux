import { useState } from 'react';
import { Alert } from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import * as DocumentPicker from 'expo-document-picker';
import { useUploadFileMutation } from '@/redux/api/userApi';
import { useTranslation } from 'react-i18next';

// Types de documents constants
export const DOCUMENT_TYPES = {
    // Images de listings
    HOUSE_MAIN_IMAGE: 'house_main_image',
    HOUSE_ADDITIONAL_IMAGE: 'house_additional_image',

    // Documents de profil
    PROFILE_PHOTO: 'profile_photo',
    ID_CARD: 'id_card',
    SELFIE_WITH_ID: 'selfie_with_id',
    PROOF_OF_ADDRESS: 'proof_of_address',

    // Autres documents
    DOCUMENT: 'document',
    OTHER: 'other',
};

export const useDocumentUpload = () => {
    const { t } = useTranslation();
    const [uploadFile, { isLoading: isUploading }] = useUploadFileMutation();
    const [uploadingType, setUploadingType] = useState(null);

    const getImageUrl = (imageUri) => {
        if (!imageUri) return null;

        // Si c'est déjà une URL complète
        if (imageUri.startsWith('http://') || imageUri.startsWith('https://')) {
            return imageUri;
        }

        // Si c'est un chemin local (pour les images non uploadées)
        if (imageUri.startsWith('file://')) {
            return imageUri;
        }

        // Construire l'URL complète pour les chemins relatifs
        const baseUrl = process.env.EXPO_PUBLIC_API_URL;
        const cleanBaseUrl = baseUrl.endsWith('/') ? baseUrl.slice(0, -1) : baseUrl;
        const cleanPath = imageUri.startsWith('/') ? imageUri : `/${imageUri}`;

        return `${cleanBaseUrl}${cleanPath}`;
    };

    const validateDocumentType = (documentType) => {
        const validTypes = Object.values(DOCUMENT_TYPES);
        if (!validTypes.includes(documentType)) {
            console.warn(`Invalid document type: ${documentType}. Using 'other' as fallback.`);
            return DOCUMENT_TYPES.OTHER;
        }
        return documentType;
    };

    const getAspectRatio = (documentType) => {
        switch (documentType) {
            case DOCUMENT_TYPES.SELFIE_WITH_ID:
            case DOCUMENT_TYPES.PROFILE_PHOTO:
                return [3, 4]; // Portrait
            case DOCUMENT_TYPES.ID_CARD:
            case DOCUMENT_TYPES.PROOF_OF_ADDRESS:
                return [4, 3]; // Paysage
            default:
                return [4, 3]; // Par défaut paysage
        }
    };

    const handleImagePicker = async (documentType, allowPdf = false) => {
        const validatedType = validateDocumentType(documentType);

        return new Promise((resolve) => {
            const options = [
                { text: t('common.cancel'), style: 'cancel', onPress: () => resolve(null) },
                {
                    text: t('form.camera'),
                    onPress: async () => {
                        try {
                            const url = await openCamera(validatedType);
                            resolve(url);
                        } catch (error) {
                            resolve(null);
                        }
                    }
                },
                {
                    text: t('form.gallery'),
                    onPress: async () => {
                        try {
                            const url = await openGallery(validatedType);
                            resolve(url);
                        } catch (error) {
                            resolve(null);
                        }
                    }
                },
            ];

            if (allowPdf) {
                options.push({
                    text: t('documents.pdf'),
                    onPress: async () => {
                        try {
                            const url = await openDocumentPicker(validatedType);
                            resolve(url);
                        } catch (error) {
                            resolve(null);
                        }
                    }
                });
            }

            Alert.alert(
                t('documents.addDocument'),
                t('documents.sourceChoice'),
                options
            );
        });
    };

    const openCamera = async (documentType) => {
        try {
            const { status } = await ImagePicker.requestCameraPermissionsAsync();
            if (status !== 'granted') {
                Alert.alert(t('common.permissionDenied'), t('form.cameraPermission'));
                return null;
            }

            const result = await ImagePicker.launchCameraAsync({
                mediaTypes: ImagePicker.MediaTypeOptions.Images,
                allowsEditing: true,
                aspect: getAspectRatio(documentType),
                quality: 0.9,
            });

            if (!result.canceled && result.assets[0]) {
                return await uploadDocument(result.assets[0], documentType);
            }
            return null;
        } catch (error) {
            console.error('Erreur caméra:', error);
            Alert.alert(t('common.error'), t('form.cameraError'));
            throw error;
        }
    };

    const openGallery = async (documentType) => {
        try {
            const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
            if (status !== 'granted') {
                Alert.alert(t('common.permissionDenied'), t('form.galleryPermission'));
                return null;
            }

            const result = await ImagePicker.launchImageLibraryAsync({
                mediaTypes: ImagePicker.MediaTypeOptions.Images,
                allowsEditing: true,
                aspect: getAspectRatio(documentType),
                quality: 0.9,
            });

            if (!result.canceled && result.assets[0]) {
                return await uploadDocument(result.assets[0], documentType);
            }
            return null;
        } catch (error) {
            console.error('Erreur galerie:', error);
            Alert.alert(t('common.error'), t('form.galleryError'));
            throw error;
        }
    };

    const openDocumentPicker = async (documentType) => {
        try {
            const result = await DocumentPicker.getDocumentAsync({
                type: ['application/pdf', 'image/*'],
                copyToCacheDirectory: true,
            });

            if (!result.canceled && result.assets[0]) {
                return await uploadDocument(result.assets[0], documentType);
            }
            return null;
        } catch (error) {
            console.error('Erreur document picker:', error);
            Alert.alert(t('common.error'), t('documents.pickerError'));
            throw error;
        }
    };

    const uploadDocument = async (documentAsset, documentType) => {
        try {
            setUploadingType(documentType);

            // Validation de la taille du fichier (max 10MB)
            const maxSize = 10 * 1024 * 1024; // 10MB
            if (documentAsset.fileSize && documentAsset.fileSize > maxSize) {
                Alert.alert(
                    t('common.error'),
                    t('documents.fileTooLarge', { size: '10MB' })
                );
                return null;
            }

            let fileData;
            if (documentAsset.mimeType?.startsWith('image/') ||
                documentAsset.uri.toLowerCase().match(/\.(jpg|jpeg|png)$/)) {
                fileData = {
                    uri: documentAsset.uri,
                    type: documentAsset.mimeType || 'image/jpeg',
                    name: documentAsset.name || `${documentType}_${Date.now()}.jpg`,
                };
            } else {
                fileData = {
                    uri: documentAsset.uri,
                    type: documentAsset.mimeType || 'application/pdf',
                    name: documentAsset.name || `${documentType}_${Date.now()}.pdf`,
                };
            }

            console.log('Uploading document:', {
                type: documentType,
                fileName: fileData.name,
                mimeType: fileData.type
            });

            const response = await uploadFile({
                file: fileData,
                type: documentType
            }).unwrap();

            Alert.alert(t('common.success'), t('documents.uploadSuccess'));
            return response.url || response.fullUrl;
        } catch (error) {
            console.error('Erreur upload:', error);

            let errorMessage = t('documents.uploadError');
            if (error?.data?.violations) {
                // Gérer les erreurs de validation API Platform
                errorMessage = error.data.violations
                    .map(v => v.message)
                    .join('\n');
            } else if (error?.message) {
                errorMessage = error.message;
            }

            Alert.alert(
                t('common.error'),
                __DEV__ ? `Debug: ${errorMessage}` : errorMessage
            );
            throw error;
        } finally {
            setUploadingType(null);
        }
    };

    return {
        isUploading,
        uploadingType,
        handleImagePicker,
        uploadDocument,
        getImageUrl,
        openCamera,
        openGallery,
        openDocumentPicker,
        DOCUMENT_TYPES,
    };
};