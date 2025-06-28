import React, { useEffect, useState } from 'react';
import {
    Alert,
    KeyboardAvoidingView,
    Platform,
    ScrollView,
    StyleSheet,
    View
} from 'react-native';
import { useRouter, useLocalSearchParams } from 'expo-router';
import { useDispatch } from 'react-redux';
import { useForm, Controller } from 'react-hook-form';
import { yupResolver } from '@hookform/resolvers/yup';
import * as yup from 'yup';
import { useTranslation } from 'react-i18next';
import { useTheme } from 'react-native-paper';
import { GoogleSignin, statusCodes } from '@react-native-google-signin/google-signin';
import { MaterialCommunityIcons } from '@expo/vector-icons';

import {
    useLoginMutation,
    useRegisterMutation,
    useGoogleAuthMutation,
} from '@/redux/api/authApi';
import { setAuth } from '@/redux/authSlice';
import { GoogleButton } from "@components/form/GoogleButton";
import { Button } from "@components/form/Button";
import { useHeaderOptions } from "@/hooks/useHeaderOptions";
import Text from '@/components/typography/Text';
import { SPACING, BORDER_RADIUS, ELEVATION } from '@/constants/spacing';
import { TextInput } from "@components/form/TextInput";
import { LinearGradient } from "expo-linear-gradient";

export default function Login() {
    const { colors } = useTheme();
    const { t } = useTranslation();
    const dispatch = useDispatch();
    const router = useRouter();
    const params = useLocalSearchParams();

    const isSignup = params.signup === 'true';
    const [signupMode, setSignupMode] = useState(isSignup);

    const [login] = useLoginMutation();
    const [register] = useRegisterMutation();
    const [googleAuth] = useGoogleAuthMutation();

    const [connexionLoading, setConnexionLoading] = useState(false);
    const [googleLoading, setGoogleLoading] = useState(false);
    const [pendingSignupData, setPendingSignupData] = useState(null);

    // ✅ AJOUT: État pour les données Google en attente
    const [pendingGoogleData, setPendingGoogleData] = useState(null);

    const headerTitle = signupMode ? t('login.welcome.signup') : t('login.welcome.signin');
    useHeaderOptions(headerTitle, [signupMode, t]);

    useEffect(() => {
        GoogleSignin.configure({
            webClientId: process.env.EXPO_PUBLIC_GOOGLE_WEB_CLIENT_ID,
            scopes: ['email', 'profile'],
        });
    }, []);

    const schema = yup.object().shape({
        email: yup
            .string()
            .email(t('validation.email.invalid'))
            .test('no-gmail', t('validation.email.use_google_button'), function(value) {
                if (!value) return true;
                const googleDomains = ['@gmail.com', '@googlemail.com', '@google.com'];
                const isGoogleEmail = googleDomains.some(domain => value.toLowerCase().endsWith(domain));
                if (isGoogleEmail) {return false;}
                return true;
            })
            .required(t('validation.email.required')),

        password: yup.string().required(t('validation.password.required')),
    });

    const { control, handleSubmit, formState: { errors }, watch } = useForm({
        resolver: yupResolver(schema),
        defaultValues: { email: '', password: '' },
    });

    const watchedEmail = watch('email');

    const handlePostAuthNavigation = (authData, isNewUser = false) => {
        dispatch(setAuth({
            token: authData.token,
            refresh_token: authData.refresh_token,
            user: authData.user
        }));

        if (isNewUser) {
            router.replace('/screens/on_boarding?step=0');
        } else {
            if (signupMode) {
                router.replace('/(tabs)/profile');
            } else {
                router.back();
            }
        }
    };

    const showConfirmDialog = (title, message) => new Promise((resolve) => {
        Alert.alert(title, message, [
            { text: t('common.no'), style: 'cancel', onPress: () => resolve(false) },
            { text: t('common.yes'), onPress: () => resolve(true) },
        ], { cancelable: false });
    });

    const handleAutoSignup = async (email, password) => {
        try {
            console.log('🔄 Auto-signup triggered for:', email);
            const registrationData = await register({ email, password }).unwrap();
            handlePostAuthNavigation(registrationData, true);
            setPendingSignupData(null);

        } catch (registrationError) {
            console.error('❌ Auto-signup failed:', registrationError);
            let errorMessage;

            if (registrationError?.status === 409 || registrationError?.data?.code === 'USER_EXISTS') {
                errorMessage = t('login.alert.userAlreadyExists');
            } else if (registrationError?.status === 400) {
                errorMessage = t('login.alert.invalidData');
            } else {
                errorMessage = t('login.alert.creationError');
            }

            Alert.alert(t('common.error'), errorMessage);
            setPendingSignupData(null);
        }
    };

    // ✅ NOUVELLE FONCTION: Gérer l'inscription Google
// ✅ FONCTION CORRIGÉE: handleGoogleSignup
    const handleGoogleSignup = async (idToken = null) => {
        try {
            setGoogleLoading(true);
            console.log('🔄 Google signup with confirmation');

            // Utiliser l'idToken fourni ou celui en attente
            const tokenToUse = idToken || pendingGoogleData?.idToken;

            if (!tokenToUse) {
                throw new Error('No Google token available');
            }

            console.log('📤 Sending Google signup request with auto_register: true');

            // Appeler l'API avec auto_register = true
            const data = await googleAuth({
                id_token: tokenToUse,
                auto_register: true
            }).unwrap();

            console.log('✅ Google signup successful:', data);

            handlePostAuthNavigation(data, data.user?.isNewUser || true);
            setPendingGoogleData(null);

        } catch (error) {
            console.error('❌ Google signup error:', error);

            // Gestion d'erreur plus détaillée
            let errorMessage = t('login.alert.googleSignupFail');

            if (error?.data?.code === 'USER_EXISTS') {
                errorMessage = t('login.alert.userAlreadyExists');
            } else if (error?.status === 400) {
                errorMessage = t('login.alert.invalidData');
            }

            Alert.alert(t('common.error'), errorMessage);
            setPendingGoogleData(null);
        } finally {
            setGoogleLoading(false);
        }
    };

// ✅ FONCTION CORRIGÉE: handleGoogleLogin
    const handleGoogleLogin = async (idToken, googleEmail) => {
        try {
            console.log('🔄 Starting Google login process');

            const data = await googleAuth({
                id_token: idToken,
                auto_register: false // ✅ Ne pas créer automatiquement
            }).unwrap();

            console.log('📥 Google auth response:', data);

            // ✅ NOUVEAU: Vérifier si l'inscription est requise
            if (data.requires_registration) {
                console.log('📝 Google account needs registration', data.google_data);

                // Stocker les données Google
                setPendingGoogleData({
                    idToken,
                    googleData: data.google_data
                });

                // Demander confirmation
                const shouldRegister = await showConfirmDialog(
                    t('login.alert.google_account_not_found_title'),
                    t('login.alert.google_account_not_found_message', {
                        email: data.google_data.email
                    })
                );

                if (shouldRegister) {
                    await handleGoogleSignup(idToken); // ✅ CORRECTION: passer l'idToken
                } else {
                    // L'utilisateur ne veut pas s'inscrire
                    setPendingGoogleData(null);
                }
            } else {
                // Connexion normale
                console.log('✅ Google login successful');
                handlePostAuthNavigation(data, data.user?.isNewUser || false);
            }
        } catch (error) {
            console.error('❌ Google login error:', error);

            // Gestion d'erreur améliorée
            let errorMessage = t('login.alert.googleFail');

            if (__DEV__) {
                errorMessage += ` (Debug: ${error.message || error.status})`;
            }

            Alert.alert(t('common.error'), errorMessage);
        }
    };

// ✅ FONCTION CORRIGÉE: signInWithGoogle
    const signInWithGoogle = async () => {
        setGoogleLoading(true);
        try {
            console.log('🔄 Starting Google Sign-In process');

            await GoogleSignin.hasPlayServices({ showPlayServicesUpdateDialog: true });
            await GoogleSignin.signOut(); // Nettoyer les sessions précédentes

            const userInfo = await GoogleSignin.signIn();
            console.log('🔍 Google Sign-In Response:', userInfo);

            let idToken, googleEmail;

            // Gestion des différents formats de réponse Google
            if (userInfo.data) {
                idToken = userInfo.data.idToken;
                googleEmail = userInfo.data.user?.email;
            } else {
                idToken = userInfo.idToken;
                googleEmail = userInfo.user?.email;
            }

            console.log('📋 Extracted data:', {
                hasIdToken: !!idToken,
                email: googleEmail,
                signupMode
            });

            if (!idToken || !googleEmail) {
                throw new Error('Missing Google authentication data');
            }

            // ✅ CORRECTION: Gestion selon le mode
            if (signupMode) {
                console.log('📝 Direct signup mode');
                // Mode inscription : créer directement le compte
                await handleGoogleSignup(idToken);
            } else {
                console.log('🔑 Login mode');
                // Mode connexion : vérifier si le compte existe
                await handleGoogleLogin(idToken, googleEmail);
            }

        } catch (error) {
            console.error('❌ Google Sign-In Error:', error);
            setGoogleLoading(false);

            if (error.code === statusCodes.SIGN_IN_CANCELLED) {
                console.log('🚫 User cancelled Google sign-in');
                return;
            } else if (error.code === statusCodes.IN_PROGRESS) {
                Alert.alert(
                    t('login.alert.google_in_progress_title'),
                    t('login.alert.google_in_progress_message')
                );
            } else if (error.code === statusCodes.PLAY_SERVICES_NOT_AVAILABLE) {
                Alert.alert(
                    t('login.alert.google_unavailable_title'),
                    t('login.alert.google_unavailable_message')
                );
            } else {
                Alert.alert(
                    t('login.alert.google_error_title'),
                    __DEV__ ? `Debug: ${error.message}` : t('login.alert.google_error_message')
                );
            }
        }
    };

    const onSubmit = async (formData) => {
        const { email, password } = formData;
        setConnexionLoading(true);

        try {
            if (!signupMode) {
                try {
                    const loginData = await login({ email, password }).unwrap();
                    console.log('✅ Login successful');
                    handlePostAuthNavigation(loginData, false);
                    return;

                } catch (loginError) {
                    console.log('❌ Login failed:', loginError);

                    if (loginError?.status === 401) {
                        const errorData = loginError?.data;
                        const errorMessage = errorData?.message || errorData?.error || '';

                        if (errorMessage.toLowerCase().includes('user not found') ||
                            errorMessage.toLowerCase().includes('utilisateur non trouvé') ||
                            errorData?.code === 'USER_NOT_FOUND') {

                            const shouldSignup = await showConfirmDialog(
                                t('login.alert.user_not_found_title'),
                                t('login.alert.user_not_found_message', { email })
                            );

                            if (shouldSignup) {
                                setPendingSignupData({ email, password });
                                await handleAutoSignup(email, password);
                                return;
                            }
                        } else {
                            throw loginError;
                        }
                    } else {
                        throw loginError;
                    }
                }
            } else {
                const registrationData = await register({ email, password }).unwrap();
                handlePostAuthNavigation(registrationData, true);
            }

        } catch (err) {
            console.error('❌ Auth error:', err);

            let errorMessage;
            if (signupMode) {
                if (err?.status === 409 || err?.data?.code === 'USER_EXISTS') {
                    errorMessage = t('login.alert.userAlreadyExists');
                } else if (err?.status === 400) {
                    errorMessage = t('login.alert.invalidData');
                } else {
                    errorMessage = t('login.alert.creationError');
                }
            } else {
                if (err?.status === 401 || err?.status === 403) {
                    errorMessage = t('login.alert.invalidCredentials');
                } else {
                    errorMessage = t('login.alert.connectionError');
                }
            }

            Alert.alert(t('common.error'), errorMessage);
        } finally {
            setConnexionLoading(false);
        }
    };

    const handleSwitchMode = () => {
        setPendingSignupData(null);
        setPendingGoogleData(null); // ✅ Nettoyer aussi les données Google
        setSignupMode(!signupMode);
    };

    const renderInput = (name, placeholder, secure = false) => (
        <Controller
            control={control}
            name={name}
            render={({ field: { onChange, value } }) => (
                <TextInput
                    variant="login"
                    placeholder={placeholder}
                    value={value}
                    onChangeText={onChange}
                    autoCapitalize="none"
                    secureTextEntry={secure}
                    keyboardType={name === 'email' ? 'email-address' : 'default'}
                    error={errors[name]?.message}
                />
            )}
        />
    );

    return (
        <KeyboardAvoidingView
            behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
            style={styles.keyboardView}
            keyboardVerticalOffset={Platform.OS === 'ios' ? 100 : 0}
        >
            <LinearGradient colors={[colors.background, colors.surface]} style={styles.container}>
                <ScrollView
                    contentContainerStyle={styles.scrollContent}
                    keyboardShouldPersistTaps="handled"
                    showsVerticalScrollIndicator={false}
                >
                    <View style={styles.content}>
                        <View style={styles.header}>
                            <View style={[styles.iconContainer, { backgroundColor: colors.primary }]}>
                                <MaterialCommunityIcons
                                    name={signupMode ? "account-plus" : "login"}
                                    size={24}
                                    color="#fff"
                                />
                            </View>
                            <Text variant="cardTitle" color="textSecondary" style={styles.subtitle}>
                                {signupMode
                                    ? t('login.subtitle.signup')
                                    : t('login.subtitle.signin')
                                }
                            </Text>
                        </View>

                        {/* Indicateur visuel si inscription en attente */}
                        {pendingSignupData && (
                            <View style={[styles.pendingContainer, { backgroundColor: colors.primaryContainer }]}>
                                <MaterialCommunityIcons name="information" size={16} color={colors.primary} />
                                <Text variant="bodySmall" color="primary" style={styles.pendingText}>
                                    {t('login.pending_signup', { email: pendingSignupData.email })}
                                </Text>
                            </View>
                        )}

                        {/* ✅ AJOUT: Indicateur pour Google en attente */}
                        {pendingGoogleData && (
                            <View style={[styles.pendingContainer, { backgroundColor: colors.primaryContainer }]}>
                                <MaterialCommunityIcons name="google" size={16} color={colors.primary} />
                                <Text variant="bodySmall" color="primary" style={styles.pendingText}>
                                    {t('login.pending_google_signup', {
                                        email: pendingGoogleData.googleData.email
                                    })}
                                </Text>
                            </View>
                        )}

                        <View style={styles.form}>
                            {renderInput('email', t('form.emailPlaceholder'))}
                            {renderInput('password', t('form.passwordPlaceholder'), true)}

                            <Button
                                mode="contained"
                                onPress={handleSubmit(onSubmit)}
                                loading={connexionLoading}
                                disabled={connexionLoading || googleLoading}
                                style={styles.mainButton}
                            >
                                {signupMode ? t('login.create') : t('login.submit')}
                            </Button>
                        </View>

                        <View style={styles.dividerContainer}>
                            <View style={[styles.dividerLine, { backgroundColor: colors.outline }]} />
                            <Text variant="subtitle" color="textSecondary" style={styles.orText}>
                                {t('login.or')}
                            </Text>
                            <View style={[styles.dividerLine, { backgroundColor: colors.outline }]} />
                        </View>

                        <GoogleButton
                            onPress={signInWithGoogle}
                            loading={googleLoading}
                            disabled={connexionLoading || googleLoading}
                        />

                        <View style={styles.switchContainer}>
                            <Text
                                variant="bodyMedium"
                                color="primary"
                                style={styles.switchMode}
                                onPress={handleSwitchMode}
                            >
                                {signupMode ? t('login.already_have_account') : t('login.no_account')}
                            </Text>
                        </View>
                    </View>
                </ScrollView>
            </LinearGradient>
        </KeyboardAvoidingView>
    );
}

const styles = StyleSheet.create({
    keyboardView: {
        flex: 1,
    },
    container: {
        flex: 1,
    },
    scrollContent: {
        flexGrow: 1,
        padding: SPACING.xl,
        justifyContent: 'center',
    },
    content: {
        maxWidth: 400,
        width: '100%',
        alignSelf: 'center',
    },
    header: {
        alignItems: 'center',
        marginBottom: SPACING.xxxl,
    },
    iconContainer: {
        width: 50,
        height: 50,
        borderRadius: 25,
        justifyContent: 'center',
        alignItems: 'center',
        marginBottom: SPACING.lg,
    },
    subtitle: {
        textAlign: 'center',
    },
    pendingContainer: {
        flexDirection: 'row',
        alignItems: 'center',
        padding: SPACING.sm,
        borderRadius: BORDER_RADIUS.md,
        marginBottom: SPACING.lg,
    },
    pendingText: {
        marginLeft: SPACING.xs,
        flex: 1,
    },
    form: {
        marginBottom: SPACING.xl,
    },
    mainButton: {
        marginTop: SPACING.xs,
        borderRadius: BORDER_RADIUS.lg,
    },
    dividerContainer: {
        flexDirection: 'row',
        alignItems: 'center',
        marginVertical: SPACING.xl,
    },
    dividerLine: {
        flex: 1,
        height: 1,
        opacity: 0.3,
    },
    orText: {
        paddingHorizontal: SPACING.lg,
    },
    switchContainer: {
        alignItems: 'center',
        flexDirection: 'row',
        justifyContent: 'center',
        marginTop: SPACING.xl,
    },
    switchMode: {
        fontWeight: '600',
        textDecorationLine: 'underline',
    },
});