import React, { useState } from 'react';
import { View, StyleSheet, ScrollView, Alert, KeyboardAvoidingView, Platform } from 'react-native';
import {
    TextInput,
    useTheme,
} from 'react-native-paper';
import { useForm, Controller } from 'react-hook-form';
import { yupResolver } from '@hookform/resolvers/yup';
import * as yup from 'yup';
import { useTranslation } from 'react-i18next';
import { useRouter } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { Button } from '@/components/form/Button';
import { useChangePasswordMutation } from '@/redux/api/userApi';
import { useHeaderOptions } from '@/hooks/useHeaderOptions';
import { useCurrentUser } from "@/hooks/useIsCurrentUser";
import Text from '@/components/typography/Text';
import { SPACING, BORDER_RADIUS, ELEVATION } from '@/constants/spacing';

// Schéma de validation
const getValidationSchema = (t) => yup.object({
    currentPassword: yup.string()
        .required(t('validation.currentPassword.required')),
    newPassword: yup.string()
        .min(8, t('validation.password.minLength'))
        .matches(
            /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/,
            t('validation.password.complexity')
        )
        .required(t('validation.password.required')),
    confirmPassword: yup.string()
        .oneOf([yup.ref('newPassword')], t('validation.confirmPassword.mustMatch'))
        .required(t('validation.confirmPassword.required')),
});

export default function ChangePasswordScreen() {
    const { t } = useTranslation();
    const { colors } = useTheme();
    const router = useRouter();
    const { user } = useCurrentUser();
    const [showCurrentPassword, setShowCurrentPassword] = useState(false);
    const [showNewPassword, setShowNewPassword] = useState(false);
    const [showConfirmPassword, setShowConfirmPassword] = useState(false);

    // Header cohérent avec le reste de l'app
    useHeaderOptions(t('settings.changePassword'), [t]);

    const [changePassword, { isLoading }] = useChangePasswordMutation();

    const isGoogleAccount = !!user?.googleId;

    const {
        control,
        handleSubmit,
        formState: { errors },
        reset,
    } = useForm({
        resolver: yupResolver(getValidationSchema(t)),
        defaultValues: {
            currentPassword: '',
            newPassword: '',
            confirmPassword: '',
        },
    });

    const onSubmit = async (data) => {
        try {
            await changePassword({
                currentPassword: data.currentPassword,
                newPassword: data.newPassword,
            }).unwrap();

            Alert.alert(
                t('common.success'),
                t('settings.password.changeSuccess'),
                [
                    {
                        text: 'OK',
                        onPress: () => {
                            reset();
                            router.back();
                        }
                    }
                ]
            );

        } catch (error) {
            console.error('❌ Password change error:', error);

            let errorMessage = t('settings.password.changeError');

            if (error?.data?.message) {
                // Messages spécifiques du serveur
                if (error.data.message.includes('current password')) {
                    errorMessage = t('settings.password.currentPasswordIncorrect');
                } else if (error.data.message.includes('same password')) {
                    errorMessage = t('settings.password.samePasswordError');
                } else {
                    errorMessage = error.data.message;
                }
            }

            Alert.alert(
                t('common.error'),
                errorMessage
            );
        }
    };

    // Fonction pour rendre les champs de mot de passe
    const renderPasswordInput = ({ name, label, placeholder, showPassword, setShowPassword, error, iconName }) => (
        <View style={styles.fieldContainer}>
            <Text variant="labelLarge" color="textPrimary" style={styles.label}>
                {label} *
            </Text>
            <Controller
                control={control}
                name={name}
                render={({ field: { onChange, value } }) => (
                    <TextInput
                        mode="outlined"
                        value={value}
                        onChangeText={onChange}
                        placeholder={placeholder}
                        placeholderTextColor={colors.textPlaceholder}
                        secureTextEntry={!showPassword}
                        error={!!error}
                        style={[styles.input, {
                            backgroundColor: colors.surface,
                            color: colors.textPrimary
                        }]}
                        left={
                            <TextInput.Icon
                                icon={iconName}
                                iconColor={colors.textSecondary}
                            />
                        }
                        right={
                            <TextInput.Icon
                                icon={showPassword ? "eye-off" : "eye"}
                                iconColor={colors.textSecondary}
                                onPress={() => setShowPassword(!showPassword)}
                            />
                        }
                        autoCapitalize="none"
                        autoCorrect={false}
                        outlineColor={colors.outline}
                        activeOutlineColor={colors.primary}
                    />
                )}
            />
            {error && (
                <Text variant="bodySmall" color="error" style={styles.errorText}>
                    {error.message}
                </Text>
            )}
        </View>
    );

    // Composant pour les comptes Google
    const GoogleAccountInfo = () => (
        <View style={styles.container}>
            <LinearGradient colors={[colors.background, colors.surface]} style={styles.container}>
                <ScrollView
                    style={styles.scrollView}
                    contentContainerStyle={styles.scrollContent}
                    showsVerticalScrollIndicator={false}
                >
                    {/* En-tête avec icône Google */}
                    <View style={styles.header}>
                        <View style={[styles.googleIconContainer, { backgroundColor: colors.surface }]}>
                            <MaterialCommunityIcons
                                name="google"
                                size={32}
                                color="#4285F4"
                            />
                        </View>
                        <Text variant="pageTitle" color="textPrimary" style={styles.title}>
                            {t('settings.password.googleAccount.title')}
                        </Text>
                        <Text variant="bodyLarge" color="textSecondary" style={styles.subtitle}>
                            {t('settings.password.googleAccount.subtitle')}
                        </Text>
                    </View>

                    {/* Message informatif */}
                    <View style={[styles.googleInfo, {
                        backgroundColor: colors.primaryContainer + '20',
                        borderColor: colors.primary + '30'
                    }]}>
                        <View style={styles.googleInfoHeader}>
                            <MaterialCommunityIcons
                                name="information-outline"
                                size={24}
                                color={colors.primary}
                            />
                            <Text variant="cardTitle" color="textPrimary" style={styles.googleInfoTitle}>
                                {t('settings.password.googleAccount.infoTitle')}
                            </Text>
                        </View>

                        <Text variant="bodyMedium" color="textSecondary" style={styles.googleInfoText}>
                            {t('settings.password.googleAccount.infoMessage')}
                        </Text>

                        {/* Étapes pour changer le mot de passe Google */}
                        <View style={styles.stepsContainer}>
                            <Text variant="labelLarge" color="textPrimary" style={styles.stepsTitle}>
                                {t('settings.password.googleAccount.stepsTitle')}
                            </Text>
                            <View style={styles.stepsList}>
                                <Text variant="bodySmall" color="textSecondary" style={styles.stepItem}>
                                    1. {t('settings.password.googleAccount.step1')}
                                </Text>
                                <Text variant="bodySmall" color="textSecondary" style={styles.stepItem}>
                                    2. {t('settings.password.googleAccount.step2')}
                                </Text>
                                <Text variant="bodySmall" color="textSecondary" style={styles.stepItem}>
                                    3. {t('settings.password.googleAccount.step3')}
                                </Text>
                            </View>
                        </View>
                    </View>

                    {/* Bouton de retour */}
                    <View style={styles.singleButtonContainer}>
                        <Button
                            mode="contained"
                            onPress={() => router.back()}
                            style={styles.backButton}
                            icon="arrow-left"
                        >
                            {t('common.back')}
                        </Button>
                    </View>

                    {/* Note de sécurité */}
                    <View style={[styles.securityNote, { backgroundColor: colors.surfaceVariant + '40' }]}>
                        <View style={styles.securityNoteHeader}>
                            <MaterialCommunityIcons
                                name="shield-check"
                                size={20}
                                color={colors.secondary}
                            />
                            <Text variant="labelLarge" color="textPrimary" style={styles.securityNoteTitle}>
                                {t('settings.password.googleAccount.securityTitle')}
                            </Text>
                        </View>

                        <Text variant="bodySmall" color="textSecondary" style={styles.securityNoteText}>
                            {t('settings.password.googleAccount.securityMessage')}
                        </Text>
                    </View>
                </ScrollView>
            </LinearGradient>
        </View>
    );

    // Afficher le bon composant selon le type de compte
    if (isGoogleAccount) {
        return <GoogleAccountInfo />;
    }

    return (
        <KeyboardAvoidingView
            behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
            style={{ flex: 1 }}
            keyboardVerticalOffset={Platform.OS === 'ios' ? 100 : 0}
        >
            <LinearGradient colors={[colors.background, colors.surface]} style={styles.container}>
                <ScrollView
                    style={styles.scrollView}
                    contentContainerStyle={styles.scrollContent}
                    showsVerticalScrollIndicator={false}
                    keyboardShouldPersistTaps="handled"
                >
                    {/* En-tête avec description */}
                    <View style={styles.header}>
                        <MaterialCommunityIcons
                            name="shield-lock"
                            size={48}
                            color={colors.primary}
                        />
                        <Text variant="bodyLarge" color="textSecondary" style={styles.subtitle}>
                            {t('settings.password.subtitle')}
                        </Text>
                    </View>

                    {/* Formulaire */}
                    <View style={styles.form}>
                        {renderPasswordInput({
                            name: "currentPassword",
                            label: t('settings.password.current'),
                            placeholder: t('settings.password.currentPlaceholder'),
                            showPassword: showCurrentPassword,
                            setShowPassword: setShowCurrentPassword,
                            error: errors.currentPassword,
                            iconName: "lock"
                        })}

                        {renderPasswordInput({
                            name: "newPassword",
                            label: t('settings.password.new'),
                            placeholder: t('settings.password.newPlaceholder'),
                            showPassword: showNewPassword,
                            setShowPassword: setShowNewPassword,
                            error: errors.newPassword,
                            iconName: "lock-plus"
                        })}

                        {renderPasswordInput({
                            name: "confirmPassword",
                            label: t('settings.password.confirm'),
                            placeholder: t('settings.password.confirmPlaceholder'),
                            showPassword: showConfirmPassword,
                            setShowPassword: setShowConfirmPassword,
                            error: errors.confirmPassword,
                            iconName: "lock-check"
                        })}
                    </View>

                    {/* Critères de sécurité */}
                    <View style={[styles.securityInfo, { backgroundColor: colors.surfaceVariant + '40' }]}>
                        <View style={styles.securityHeader}>
                            <MaterialCommunityIcons
                                name="information-outline"
                                size={20}
                                color={colors.primary}
                            />
                            <Text variant="labelLarge" color="textPrimary" style={styles.securityTitle}>
                                {t('settings.password.requirements')}
                            </Text>
                        </View>

                        <View style={styles.requirementsList}>
                            <Text variant="bodySmall" color="textSecondary" style={styles.requirementItem}>
                                • {t('settings.password.requirement1')}
                            </Text>
                            <Text variant="bodySmall" color="textSecondary" style={styles.requirementItem}>
                                • {t('settings.password.requirement2')}
                            </Text>
                            <Text variant="bodySmall" color="textSecondary" style={styles.requirementItem}>
                                • {t('settings.password.requirement3')}
                            </Text>
                        </View>
                    </View>

                    {/* Boutons */}
                    <View style={styles.buttons}>
                        <Button
                            mode="outlined"
                            onPress={() => router.back()}
                            style={styles.cancelButton}
                            disabled={isLoading}
                        >
                            {t('common.cancel')}
                        </Button>

                        <Button
                            mode="contained"
                            onPress={handleSubmit(onSubmit)}
                            loading={isLoading}
                            disabled={isLoading}
                            style={styles.submitButton}
                        >
                            {t('settings.password.change')}
                        </Button>
                    </View>
                </ScrollView>
            </LinearGradient>
        </KeyboardAvoidingView>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    scrollView: {
        flex: 1,
    },
    scrollContent: {
        padding: SPACING.xl,
        paddingBottom: SPACING.xxl,
    },
    header: {
        marginBottom: SPACING.xxxl,
        alignItems: 'center',
    },
    googleIconContainer: {
        width: 64,
        height: 64,
        borderRadius: 32,
        justifyContent: 'center',
        alignItems: 'center',
        elevation: ELEVATION.medium,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 4,
    },
    title: {
        marginTop: SPACING.lg,
        marginBottom: SPACING.sm,
        textAlign: 'center',
    },
    subtitle: {
        textAlign: 'center',
        lineHeight: 22,
    },
    form: {
        gap: SPACING.xl,
        marginBottom: SPACING.xl,
    },
    fieldContainer: {
        gap: SPACING.sm,
    },
    label: {
        marginBottom: SPACING.xs,
    },
    input: {
        fontSize: 16,
    },
    errorText: {
        marginTop: SPACING.xs,
    },
    // Styles pour les comptes Google
    googleInfo: {
        borderRadius: BORDER_RADIUS.lg,
        padding: SPACING.xl,
        marginBottom: SPACING.xl,
        borderWidth: 1,
    },
    googleInfoHeader: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.md,
        marginBottom: SPACING.md,
    },
    googleInfoTitle: {
        flex: 1,
    },
    googleInfoText: {
        lineHeight: 24,
        marginBottom: SPACING.xl,
    },
    stepsContainer: {
        gap: SPACING.md,
    },
    stepsTitle: {
        marginBottom: SPACING.sm,
    },
    stepsList: {
        gap: SPACING.sm,
    },
    stepItem: {
        lineHeight: 20,
    },
    singleButtonContainer: {
        marginBottom: SPACING.xl,
    },
    backButton: {
        paddingVertical: SPACING.xs,
        borderRadius: BORDER_RADIUS.md,
    },
    securityNote: {
        borderRadius: BORDER_RADIUS.lg,
        padding: SPACING.lg,
        borderWidth: 1,
        borderColor: 'rgba(0,0,0,0.05)',
    },
    securityNoteHeader: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.sm,
        marginBottom: SPACING.sm,
    },
    securityNoteTitle: {
        flex: 1,
    },
    securityNoteText: {
        lineHeight: 18,
    },
    // Styles existants pour le formulaire normal
    securityInfo: {
        borderRadius: BORDER_RADIUS.lg,
        padding: SPACING.lg,
        marginBottom: SPACING.xl,
        borderWidth: 1,
        borderColor: 'rgba(0,0,0,0.05)',
    },
    securityHeader: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.sm,
        marginBottom: SPACING.md,
    },
    securityTitle: {
        flex: 1,
    },
    requirementsList: {
        gap: SPACING.sm,
    },
    requirementItem: {
        lineHeight: 20,
    },
    buttons: {
        flexDirection: 'row',
        gap: SPACING.md,
        marginTop: SPACING.sm,
    },
    cancelButton: {
        flex: 1,
        borderRadius: BORDER_RADIUS.md,
    },
    submitButton: {
        flex: 1,
        borderRadius: BORDER_RADIUS.md,
    },
});