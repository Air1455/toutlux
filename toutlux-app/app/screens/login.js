import React, { useEffect, useState } from 'react';
import {
    Alert,
    KeyboardAvoidingView,
    Platform,
    ScrollView,
    StyleSheet,
    Text,
    TextInput,
    View
} from 'react-native';
import { useRouter, useLocalSearchParams } from 'expo-router';
import { useDispatch } from 'react-redux';
import { useForm, Controller } from 'react-hook-form';
import { yupResolver } from '@hookform/resolvers/yup';
import * as yup from 'yup';
import { useTranslation } from 'react-i18next';
import { useTheme } from 'react-native-paper';
import { LinearGradient } from 'expo-linear-gradient';
import { GoogleSignin, statusCodes } from '@react-native-google-signin/google-signin';

// ✅ IMPORTS CORRIGÉS
import {
    useLoginMutation,
    useRegisterMutation,
    useGoogleAuthMutation,
} from '@/redux/api/authApi';
import { setAuth } from '@/redux/authSlice'; // ✅ CORRECTION: Import depuis authSlice
import { GoogleButton } from "@components/form/GoogleButton";
import { Button } from "@components/form/Button";
import { useHeaderOptions } from "@/hooks/useHeaderOptions";

export default function Login() {
    const { colors } = useTheme();
    const { t } = useTranslation();
    const dispatch = useDispatch();
    const router = useRouter();
    const params = useLocalSearchParams();

    const isSignup = params.signup === 'true';
    const [signupMode, setSignupMode] = useState(isSignup);

    // ✅ HOOKS API
    const [login] = useLoginMutation();
    const [register] = useRegisterMutation();
    const [googleAuth] = useGoogleAuthMutation();

    const [connexionLoading, setConnexionLoading] = useState(false);
    const [googleLoading, setGoogleLoading] = useState(false);

    const headerTitle = signupMode ? t('login.welcome.signup') : t('login.welcome.signin');
    useHeaderOptions(headerTitle, [signupMode, t]);

    useEffect(() => {
        GoogleSignin.configure({
            webClientId: process.env.EXPO_PUBLIC_GOOGLE_WEB_CLIENT_ID,
            scopes: ['email', 'profile'],
        });
    }, []);

    const schema = yup.object().shape({
        email: yup.string().email(t('validation.email.invalid')).required(t('validation.email.required')),
        password: yup.string().required(t('validation.password.required')),
    });

    const { control, handleSubmit, formState: { errors } } = useForm({
        resolver: yupResolver(schema),
        defaultValues: { email: '', password: '' },
    });

    const handlePostAuthNavigation = (authData, isNewUser = false) => {
        dispatch(setAuth({
            token: authData.token,
            refresh_token: authData.refresh_token,
            user: authData.user
        }));

        console.log('🔍 Navigation decision:', {
            isNewUser,
            isProfileComplete: authData.user?.isProfileComplete,
            signupMode,
            user: authData.user
        });

        if (isNewUser || (authData.user && !authData.user.isProfileComplete)) {
            router.replace('/screens/on_boarding?step=0');
        } else {
            if (signupMode) {
                router.replace('/(tabs)/profile');
            } else {
                router.back();
            }
        }
    };

    const onSubmit = async (formData) => {
        const { email, password } = formData;
        setConnexionLoading(true);

        try {
            if (!signupMode) {
                // MODE CONNEXION
                try {
                    const loginData = await login({ email, password }).unwrap();
                    handlePostAuthNavigation(loginData, false);
                } catch (loginError) {
                    if (loginError?.status === 401) {
                        // Utilisateur n'existe pas, proposer inscription
                        const shouldSignup = await confirm(
                            t('login.alert.user_not_found_title'),
                            t('login.alert.user_not_found_message')
                        );

                        if (shouldSignup) {
                            setSignupMode(true);
                        }
                    } else {
                        throw loginError;
                    }
                }
            } else {
                // MODE INSCRIPTION
                const registrationData = await register({ email, password }).unwrap();

                if (registrationData.needsLogin) {
                    const loginData = await login({ email, password }).unwrap();
                    handlePostAuthNavigation(loginData, true);
                } else {
                    // Inscription avec token directement
                    // ✅ CORRECTION: Passer registrationData directement
                    handlePostAuthNavigation(registrationData, true);
                }
            }
        } catch (err) {
            console.error('Auth error:', err);

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

    const handleGoogleLogin = async (idToken, googleEmail) => {
        try {
            const data = await googleAuth({ id_token: idToken }).unwrap();
            handlePostAuthNavigation(data, data.user?.isNewUser || false);
        } catch (error) {
            console.error('❌ Google login error:', error);
            Alert.alert(
                t('common.error'),
                __DEV__ ? `Debug: ${error.message}` : t('login.alert.googleFail')
            );
        }
    };

    const signInWithGoogle = async () => {
        setGoogleLoading(true);
        try {
            await GoogleSignin.hasPlayServices({ showPlayServicesUpdateDialog: true });
            await GoogleSignin.signOut();

            const userInfo = await GoogleSignin.signIn();
            console.log('🔍 Google Sign-In Response:', userInfo);

            let idToken, googleEmail;

            if (userInfo.data) {
                idToken = userInfo.data.idToken;
                googleEmail = userInfo.data.user?.email;
            } else {
                idToken = userInfo.idToken;
                googleEmail = userInfo.user?.email;
            }

            if (!idToken || !googleEmail) {
                throw new Error('Missing Google authentication data');
            }

            await handleGoogleLogin(idToken, googleEmail);

        } catch (error) {
            console.error('❌ Google Sign-In Error:', error);

            if (error.code === statusCodes.SIGN_IN_CANCELLED) {
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
        } finally {
            setGoogleLoading(false);
        }
    };

    const confirm = (title, message) => new Promise((resolve) => {
        Alert.alert(title, message, [
            { text: t('common.no'), style: 'cancel', onPress: () => resolve(false) },
            { text: t('common.yes'), onPress: () => resolve(true) },
        ], { cancelable: false });
    });

    const renderInput = (name, placeholder, secure = false) => (
        <Controller
            control={control}
            name={name}
            render={({ field: { onChange, value } }) => (
                <>
                    <TextInput
                        style={[styles.input, {
                            color: colors.onSurface,
                            borderColor: colors.outline,
                            backgroundColor: colors.surface
                        }]}
                        placeholder={placeholder}
                        placeholderTextColor={colors.onSurfaceVariant}
                        value={value}
                        onChangeText={onChange}
                        autoCapitalize="none"
                        secureTextEntry={secure}
                        keyboardType={name === 'email' ? 'email-address' : 'default'}
                    />
                    {errors[name] && (
                        <Text style={[styles.errorText, { color: colors.error }]}>
                            {errors[name].message}
                        </Text>
                    )}
                </>
            )}
        />
    );

    return (
        <KeyboardAvoidingView
            behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
            style={{ flex: 1 }}
            keyboardVerticalOffset={Platform.OS === 'ios' ? 100 : 0}
        >
            <LinearGradient colors={[colors.surface, colors.background]} style={{ flex: 1 }}>
                <ScrollView
                    contentContainerStyle={styles.container}
                    keyboardShouldPersistTaps="handled"
                >
                    <View style={{ padding: 20 }}>
                        <View style={styles.headerSection}>
                            <Text style={[styles.welcomeTitle, { color: colors.onBackground }]}>
                                {signupMode ? t('login.welcome.signup') : t('login.welcome.signin')}
                            </Text>
                        </View>

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

                        <Text style={[styles.or, { color: colors.onSurfaceVariant }]}>
                            {t('login.or')}
                        </Text>

                        <GoogleButton
                            onPress={signInWithGoogle}
                            loading={googleLoading}
                            disabled={connexionLoading || googleLoading}
                        />

                        <Text
                            style={[styles.switchMode, { color: colors.primary }]}
                            onPress={() => setSignupMode(!signupMode)}
                        >
                            {signupMode ? t('login.already_have_account') : t('login.no_account')}
                        </Text>
                    </View>
                </ScrollView>
            </LinearGradient>
        </KeyboardAvoidingView>
    );
}

const styles = StyleSheet.create({
    container: {
        flexGrow: 1,
        padding: 24,
        justifyContent: 'center',
    },
    headerSection: {
        marginBottom: 32,
        alignItems: 'center',
    },
    welcomeTitle: {
        fontSize: 28,
        fontWeight: 'bold',
        textAlign: 'center',
        marginBottom: 8,
    },
    input: {
        borderWidth: 1.5,
        padding: 16,
        borderRadius: 12,
        marginBottom: 12,
        fontSize: 16,
    },
    errorText: {
        fontSize: 14,
        marginBottom: 15,
        marginTop: -8,
        marginLeft: 4,
    },
    mainButton: {
        marginVertical: 16,
        paddingVertical: 4,
    },
    or: {
        textAlign: 'center',
        marginVertical: 16,
        fontSize: 14,
    },
    switchMode: {
        textAlign: 'center',
        marginVertical: 20,
        textDecorationLine: 'underline',
        fontSize: 16,
    },
});