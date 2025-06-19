import React from 'react';
import {TouchableOpacity, Text, StyleSheet, ActivityIndicator, View, Image} from 'react-native';
import {useTranslation} from "react-i18next";
import {useTheme} from "react-native-paper";

export function GoogleButton({ onPress, loading= false }) {
    const { t } = useTranslation();
    const {colors}= useTheme()

    return (
        <TouchableOpacity onPress={onPress} disabled={loading}>
            <View style={styles.content}>
                {loading ? <ActivityIndicator size="large" color={colors.primary} />
                    : <Image source={require('@/assets/images/google-icon.png')} style={styles.icon} />
                }
                <Text style={styles.text}>{t('login.google')}</Text>
            </View>
        </TouchableOpacity>
    );
}

const styles = StyleSheet.create({
    button: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        paddingVertical: 12,
        paddingHorizontal: 16,
        borderRadius: 4,
        elevation: 2,
    },
    content: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: '#FFFFFF',
        borderRadius: 8,
        paddingVertical: 12,
        paddingHorizontal: 20,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 5,
        elevation: 3,
        marginTop: 20,
    },
    icon: {
        width: 35,
        height: 35,
        marginRight: 10,
    },
    text: {
        fontSize: 16,
        color: '#333',
        fontWeight: '600',
        textAlign: 'center',
        flex: 1,
    },
});