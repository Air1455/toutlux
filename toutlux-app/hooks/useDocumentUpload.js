import { useState } from 'react';
import { Alert } from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import * as DocumentPicker from 'expo-document-picker';
import { useUploadFileMutation } from '@/redux/api/userApi';
import { useTranslation } from 'react-i18next';

export const useDocumentUpload = () => {
    const { t } = useTranslation();
    const [uploadFile, { isLoading: isUploading }] = useUploadFileMutation();
    const [uploadingType, setUploadingType] = useState(null);

    const handleImagePicker = async (documentType, allowPdf = false) => {
        return new Promise((resolve) => {
            const options = [
                { text: t('common.cancel'), style: 'cancel', onPress: () => resolve(null) },
                {
                    text: t('form.camera'),
                    onPress: async () => {
                        try {
                            const url = await openCamera(documentType);
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
                            const url = await openGallery(documentType);
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
                            const url = await openDocumentPicker(documentType);
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
                aspect: documentType === 'selfieWithId' ? [3, 4] : [4, 3],
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
                aspect: documentType === 'selfieWithId' ? [3, 4] : [4, 3],
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

            let fileData;
            if (documentAsset.mimeType?.startsWith('image/') ||
                documentAsset.uri.toLowerCase().match(/\.(jpg|jpeg|png)$/)) {
                fileData = {
                    uri: documentAsset.uri,
                    type: 'image/jpeg',
                    name: `${documentType}_${Date.now()}.jpg`,
                };
            } else {
                fileData = {
                    uri: documentAsset.uri,
                    type: documentAsset.mimeType || 'application/pdf',
                    name: documentAsset.name || `${documentType}_${Date.now()}.pdf`,
                };
            }

            const response = await uploadFile({
                file: fileData,
                type: documentType
            }).unwrap();

            Alert.alert(t('common.success'), t('documents.uploadSuccess'));

            return response.url;
        } catch (error) {
            console.error('Erreur upload:', error);
            Alert.alert(
                t('common.error'),
                __DEV__
                    ? `Debug: ${error?.message || 'Upload failed'}`
                    : t('documents.uploadError')
            );
            throw error;
        } finally {
            setUploadingType(null);
        }
    };

    const getImageUrl = (imageUri) => {
        if (!imageUri) return null;
        return imageUri.startsWith('http')
            ? imageUri
            : `${process.env.EXPO_PUBLIC_API_URL}${imageUri}`;
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
    };
};