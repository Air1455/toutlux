import React from 'react';
import { TouchableOpacity, View, Text, Image, Alert, StyleSheet } from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import { MaterialIcons } from '@expo/vector-icons';
import {useTheme} from "react-native-paper";

const ImagePickerField = ({ label, fieldName, control, errors, setValue }) => {
    const {colors}= useTheme()
    const handleImagePick = async () => {
        try {
            const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
            if (status !== 'granted') {
                Alert.alert('Permission refusée', 'Accès aux photos nécessaire');
                return;
            }

            const result = await ImagePicker.launchImageLibraryAsync({
                mediaTypes: ImagePicker.MediaTypeOptions.Images,
                allowsEditing: true,
                aspect: [4, 3],
                quality: 0.7,
            });

            if (result.canceled) {
                return;
            }

            if (result.assets && result.assets[0] && result.assets[0].uri) {
                setValue(fieldName, result.assets[0].uri);
            } else {
                Alert.alert('Erreur', "Impossible de récupérer l'image sélectionnée.");
            }
        } catch (error) {
            console.error('ImagePicker error:', error);
            Alert.alert('Erreur', "Échec de la sélection de l’image.");
        }
    };

    return (
        <View style={styles.wrapper}>
            <Text style={styles.label}>{label}</Text>
            <TouchableOpacity
                style={[styles.imageContainer, errors[fieldName] && styles.imageError]}
                onPress={handleImagePick}
            >
                {control._formValues[fieldName] ? (
                    <Image source={{ uri: control._formValues[fieldName] }} style={styles.image} />
                ) : (
                    <View style={[styles.imagePlaceholder, { backgroundColor: colors.primary }]}>
                        <MaterialIcons name="add-a-photo" size={36} color="white" />
                        <Text style={styles.imagePlaceholderText}>Ajouter</Text>
                    </View>
                )}
            </TouchableOpacity>
            {errors[fieldName] && <Text style={styles.errorText}>{errors[fieldName].message}</Text>}
        </View>
    );
};

const styles = StyleSheet.create({
    wrapper: {
        marginBottom: 24,
        alignItems: 'center',
    },
    label: {
        fontSize: 16,
        fontWeight: '600',
        color: '#333',
        marginBottom: 8,
        alignSelf: 'flex-start',
    },
    imageContainer: {
        width: 200,
        height: 200,
        borderRadius: 16,
        overflow: 'hidden',
        backgroundColor: '#f0f0f0',
        justifyContent: 'center',
        alignItems: 'center',
        elevation: 3,
    },
    imageError: {
        borderWidth: 2,
        borderColor: '#ff4d4d',
    },
    image: {
        width: '100%',
        height: '100%',
        resizeMode: 'cover',
    },
    imagePlaceholder: {
        justifyContent: 'center',
        alignItems: 'center',
        width: '100%',
        height: '100%',
    },
    imagePlaceholderText: {
        color: 'white',
        fontSize: 14,
        marginTop: 6,
    },
    errorText: {
        marginTop: 8,
        fontSize: 12,
        color: '#ff4d4d',
    },
});

export default ImagePickerField;