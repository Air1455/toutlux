import React, { useEffect, useState } from 'react';
import { View, StyleSheet, ScrollView, KeyboardAvoidingView, Platform, Dimensions, Alert } from 'react-native';
import {
    Button,
    TextInput,
    useTheme,
    Card,
    Avatar,
} from 'react-native-paper';
import { LinearGradient } from 'expo-linear-gradient';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { useTranslation } from 'react-i18next';
import { useForm, Controller } from 'react-hook-form';
import { yupResolver } from '@hookform/resolvers/yup';
import * as yup from 'yup';
import { useLocalSearchParams, useRouter } from 'expo-router';
import Animated, {
    useSharedValue,
    useAnimatedStyle,
    withTiming,
    withSpring,
    withDelay,
} from 'react-native-reanimated';

import Text from '@/components/typography/Text';
import { useContactSellerMutation } from '@/redux/api/contactApi';
import { useGetUserByIdQuery } from '@/redux/api/userApi';
import { useGetHouseQuery } from '@/redux/api/houseApi';
import { SPACING, BORDER_RADIUS, ELEVATION } from '@/constants/spacing';
import { addOpacity } from '@/utils/colorUtils';
import { useHeaderOptions } from '@/hooks/useHeaderOptions';
import {LoadingScreen} from "@components/Loading";

const MESSAGE_TYPES = [
    {
        value: 'visit_request',
        label: 'contact.types.visit_request',
        icon: 'home-search',
        color: '#4CAF50',
    },
    {
        value: 'info_request',
        label: 'contact.types.info_request',
        icon: 'information-outline',
        color: '#2196F3',
    },
    {
        value: 'price_negotiation',
        label: 'contact.types.price_negotiation',
        icon: 'handshake-outline',
        color: '#FF9800',
    },
    {
        value: 'other',
        label: 'contact.types.other',
        icon: 'message-text-outline',
        color: '#9C27B0',
    },
];

const createContactSchema = (t) => yup.object({
    subject: yup.string()
        .required(t('validation.subject.required'))
        .min(5, t('validation.subject.min'))
        .max(100, t('validation.subject.max')),
    message: yup.string()
        .required(t('validation.message.required'))
        .min(10, t('validation.message.min'))
        .max(1000, t('validation.message.max')),
    messageType: yup.string()
        .required(t('validation.messageType.required'))
        .oneOf(['visit_request', 'info_request', 'price_negotiation', 'other']),
});

export default function ContactSellerScreen() {
    const { colors } = useTheme();
    const { t } = useTranslation();
    const router = useRouter();
    const params = useLocalSearchParams();

    // Parse params properly to avoid iterator issues
    const houseId = params.houseId;
    const sellerId = params.sellerId;

    const [sendMessage, { isLoading: isSending }] = useContactSellerMutation();

    // Animations
    const sellerCardY = useSharedValue(50);
    const sellerCardOpacity = useSharedValue(0);
    const propertyCardY = useSharedValue(50);
    const propertyCardOpacity = useSharedValue(0);
    const formY = useSharedValue(50);
    const formOpacity = useSharedValue(0);
    const buttonScale = useSharedValue(0.8);
    const buttonOpacity = useSharedValue(0);

    // Récupération des données
    const {
        data: seller,
        isLoading: isLoadingSeller,
        error: sellerError
    } = useGetUserByIdQuery(sellerId, {
        skip: !sellerId,
    });

    const {
        data: house,
        isLoading: isLoadingHouse,
        error: houseError
    } = useGetHouseQuery(houseId, {
        skip: !houseId,
    });

    const isDataLoading = isLoadingSeller || isLoadingHouse;

    const schema = createContactSchema(t);

    const {
        control,
        handleSubmit,
        reset,
        formState: { errors, isDirty },
    } = useForm({
        resolver: yupResolver(schema),
        defaultValues: {
            messageType: 'info_request',
            subject: '',
            message: '',
        },
    });


    useHeaderOptions(t('contact.title'), undefined, {headerTitleAlign: 'center'});

    // Animations d'entrée - déclenchées seulement quand tout est prêt
    useEffect(() => {
        if (!isDataLoading) {
            // Animation séquentielle des éléments
            sellerCardY.value = withSpring(0, { damping: 15, stiffness: 100 });
            sellerCardOpacity.value = withTiming(1, { duration: 400 });

            propertyCardY.value = withDelay(100, withSpring(0, { damping: 15, stiffness: 100 }));
            propertyCardOpacity.value = withDelay(100, withTiming(1, { duration: 400 }));

            formY.value = withDelay(200, withSpring(0, { damping: 15, stiffness: 100 }));
            formOpacity.value = withDelay(200, withTiming(1, { duration: 400 }));

            buttonScale.value = withDelay(300, withSpring(1, { damping: 12, stiffness: 120 }));
            buttonOpacity.value = withDelay(300, withTiming(1, { duration: 300 }));
        }
    }, [isDataLoading, seller, house]);

    useEffect(() => {
        if (sellerError || houseError) {
            Alert.alert(
                t('common.error'),
                t('contact.dataLoadError'),
                [{ text: t('common.ok'), onPress: () => router.back() }]
            );
        }
    }, [sellerError, houseError]);

    // Styles animés - TOUJOURS définis (avant les conditions)
    const sellerCardStyle = useAnimatedStyle(() => ({
        opacity: sellerCardOpacity.value,
        transform: [{ translateY: sellerCardY.value }],
    }));

    const propertyCardStyle = useAnimatedStyle(() => ({
        opacity: propertyCardOpacity.value,
        transform: [{ translateY: propertyCardY.value }],
    }));

    const formStyle = useAnimatedStyle(() => ({
        opacity: formOpacity.value,
        transform: [{ translateY: formY.value }],
    }));

    const buttonStyle = useAnimatedStyle(() => ({
        opacity: buttonOpacity.value,
        transform: [{ scale: buttonScale.value }],
    }));

    const onSubmit = async (data) => {
        try {
            await sendMessage({houseId, sellerId, ...data}).unwrap();

            Alert.alert(
                t('contact.success'),
                t('contact.messageSent'),
                [{ text: t('common.ok'), onPress: () => router.back() }]
            );

            reset();
        } catch (error) {
            console.error('Error sending message:', error);
            Alert.alert(
                t('common.error'),
                t('contact.sendError')
            );
        }
    };

    const renderSellerInfo = () => {
        if (!seller) return null;

        return (
            <Animated.View style={sellerCardStyle}>
                <Card style={[styles.sellerCard, { backgroundColor: colors.surface }]} elevation={1}>
                    <Card.Content style={styles.sellerContent}>
                        <View style={styles.sellerHeader}>
                            <View style={styles.avatarContainer}>
                                <Avatar.Text
                                    size={56}
                                    label={seller.firstName?.charAt(0) || 'U'}
                                    style={[styles.avatar, { backgroundColor: colors.primary }]}
                                    labelStyle={{ color: '#fff', fontSize: 20 }}
                                />
                                {seller.isVerified && (
                                    <View style={[styles.verifiedBadge, { backgroundColor: '#4CAF50' }]}>
                                        <MaterialCommunityIcons
                                            name="check"
                                            size={12}
                                            color="#fff"
                                        />
                                    </View>
                                )}
                            </View>
                            <View style={styles.sellerDetails}>
                                <Text variant="cardTitle" color="textPrimary">
                                    {seller.firstName} {seller.lastName}
                                </Text>
                                <View style={styles.verificationContainer}>
                                    <MaterialCommunityIcons
                                        name={seller.isVerified ? 'shield-check-outline' : 'shield-outline'}
                                        size={16}
                                        color={seller.isVerified ? '#4CAF50' : colors.onSurfaceVariant}
                                    />
                                    <Text variant="bodySmall" color={seller.isVerified ? 'primary' : 'textSecondary'}>
                                        {seller.isVerified ? t('contact.verified') : t('contact.unverified')}
                                    </Text>
                                </View>
                            </View>
                        </View>
                    </Card.Content>
                </Card>
            </Animated.View>
        );
    };

    const renderPropertyInfo = () => {
        if (!house) return null;

        return (
            <Animated.View style={propertyCardStyle}>
                <Card style={[styles.propertyCard, { backgroundColor: colors.surface }]} elevation={1}>
                    <Card.Content style={styles.propertyContent}>
                        <View style={styles.propertyHeader}>
                            <MaterialCommunityIcons
                                name="home"
                                size={20}
                                color={colors.primary}
                                style={styles.propertyIcon}
                            />
                            <Text variant="labelLarge" color="textPrimary" style={{ flex: 1 }}>
                                {t('contact.aboutProperty')}
                            </Text>
                        </View>
                        <Text variant="bodyMedium" color="textPrimary" numberOfLines={2}>
                            {house.shortDescription}
                        </Text>
                        <View style={styles.locationContainer}>
                            <MaterialCommunityIcons
                                name="map-marker-outline"
                                size={14}
                                color={colors.onSurfaceVariant}
                            />
                            <Text variant="bodySmall" color="textSecondary">
                                {house.address}, {house.city}
                            </Text>
                        </View>
                    </Card.Content>
                </Card>
            </Animated.View>
        );
    };

    const renderMessageTypeSelector = () => (
        <View style={styles.messageTypesContainer}>
            <Text variant="labelLarge" color="textPrimary" style={styles.fieldLabel}>
                {t('contact.messageType')} *
            </Text>
            <Controller
                control={control}
                name="messageType"
                render={({ field: { onChange, value } }) => (
                    <View style={styles.messageTypeGrid}>
                        {MESSAGE_TYPES.map((type) => {
                            const isSelected = value === type.value;
                            return (
                                <View key={type.value} style={styles.messageTypeOption}>
                                    <Card
                                        style={[
                                            styles.messageTypeCard,
                                            {
                                                backgroundColor: isSelected
                                                    ? addOpacity(type.color, 0.1)
                                                    : colors.surface,
                                                borderColor: isSelected ? type.color : colors.outline,
                                                borderWidth: isSelected ? 2 : 1,
                                            }
                                        ]}
                                        onPress={() => onChange(type.value)}
                                        elevation={isSelected ? 2 : 0}
                                    >
                                        <Card.Content style={styles.messageTypeContent}>
                                            <View style={[
                                                styles.messageTypeIconContainer,
                                                { backgroundColor: isSelected ? type.color : colors.surfaceVariant }
                                            ]}>
                                                <MaterialCommunityIcons
                                                    name={type.icon}
                                                    size={20}
                                                    color={isSelected ? '#fff' : colors.onSurfaceVariant}
                                                />
                                            </View>
                                            <Text
                                                variant="labelSmall"
                                                color={isSelected ? 'primary' : 'textPrimary'}
                                                style={styles.messageTypeLabel}
                                            >
                                                {t(type.label)}
                                            </Text>
                                        </Card.Content>
                                    </Card>
                                </View>
                            );
                        })}
                    </View>
                )}
            />
            {errors.messageType && (
                <Text variant="labelSmall" color="error" style={styles.errorText}>
                    {errors.messageType.message}
                </Text>
            )}
        </View>
    );

    const renderFormField = (name, label, placeholder, multiline = false) => (
        <View style={styles.formField}>
            <Text variant="labelLarge" color="textPrimary" style={styles.fieldLabel}>
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
                        error={!!errors[name]}
                        disabled={isSending}
                        multiline={multiline}
                        numberOfLines={multiline ? 4 : 1}
                        maxLength={name === 'subject' ? 100 : 1000}
                        contentStyle={multiline ? styles.textAreaContent : undefined}
                        style={[
                            styles.input,
                            multiline && styles.textAreaInput,
                            { backgroundColor: colors.surface }
                        ]}
                        outlineStyle={{
                            borderRadius: BORDER_RADIUS.md,
                            borderColor: errors[name] ? colors.error : colors.outline
                        }}
                    />
                )}
            />
            {errors[name] && (
                <Text variant="labelSmall" color="error" style={styles.errorText}>
                    {errors[name].message}
                </Text>
            )}
        </View>
    );

    const renderPrivacyNote = () => (
        <View style={[styles.privacyNote, { backgroundColor: addOpacity(colors.primary, 0.08) }]}>
            <MaterialCommunityIcons
                name="shield-check-outline"
                size={18}
                color={colors.primary}
            />
            <Text variant="bodySmall" color="textSecondary" style={{ flex: 1 }}>
                {t('contact.privacyNote')}
            </Text>
        </View>
    );

    const renderSendButton = () => (
        <Animated.View style={[styles.sendButtonContainer, { backgroundColor: colors.background }, buttonStyle]}>
            <Button
                mode="contained"
                onPress={handleSubmit(onSubmit)}
                loading={isSending}
                disabled={isSending || isDataLoading}
                style={[styles.sendButton, { backgroundColor: colors.primary }]}
                contentStyle={styles.sendButtonContent}
                icon="send"
            >
                {t('contact.send')}
            </Button>
        </Animated.View>
    );

    const containerStyle = {flex: 1, backgroundColor: colors.background};

    if(isDataLoading) {
        return <LoadingScreen />
    }


    // État d'erreur
    if (sellerError || houseError || !seller || !house) {
        return (
            <LinearGradient colors={[colors.background, colors.surface]} style={[containerStyle, styles.centered]}>
                <MaterialCommunityIcons
                    name="alert-circle-outline"
                    size={64}
                    color={colors.error}
                />
                <Text variant="bodyLarge" color="error" style={styles.errorText}>
                    {t('contact.dataLoadError')}
                </Text>
                <Button
                    mode="outlined"
                    onPress={() => router.back()}
                    style={styles.backButton}
                >
                    {t('common.goBack')}
                </Button>
            </LinearGradient>
        );
    }

    return (
        <LinearGradient colors={[colors.background, colors.surface]} style={containerStyle}>
            <KeyboardAvoidingView
                style={styles.keyboardView}
                behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
            >
                <ScrollView
                    style={styles.scrollContent}
                    contentContainerStyle={styles.scrollContainer}
                    showsVerticalScrollIndicator={false}
                    keyboardShouldPersistTaps="handled"
                >
                    <View style={styles.content}>
                        {renderSellerInfo()}
                        {renderPropertyInfo()}

                        <Animated.View style={[styles.formContainer, formStyle]}>
                            <Text variant="sectionTitle" color="textPrimary" style={styles.formTitle}>
                                {t('contact.sendMessage')}
                            </Text>

                            {renderMessageTypeSelector()}

                            {renderFormField(
                                'subject',
                                t('contact.subject'),
                                t('contact.subjectPlaceholder')
                            )}

                            {renderFormField(
                                'message',
                                t('contact.message'),
                                t('contact.messagePlaceholder'),
                                true
                            )}

                            {renderPrivacyNote()}
                        </Animated.View>
                    </View>
                </ScrollView>

                {renderSendButton()}
            </KeyboardAvoidingView>
        </LinearGradient>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    centered: {
        justifyContent: 'center',
        alignItems: 'center',
    },
    keyboardView: {
        flex: 1,
    },
    scrollContent: {
        flex: 1,
    },
    scrollContainer: {
        paddingBottom: 100, // Espace pour le bouton fixe
    },
    content: {
        padding: SPACING.lg,
        gap: SPACING.lg,
    },
    loadingIndicatorContainer: {
        position: 'absolute',
        top: '50%',
        left: '50%',
        transform: [{ translateX: -50 }, { translateY: -50 }],
        alignItems: 'center',
        gap: SPACING.md,
    },
    loadingText: {
        textAlign: 'center',
        marginTop: SPACING.sm,
    },

    // Seller card styles
    sellerCard: {
        borderRadius: BORDER_RADIUS.lg,
        overflow: 'hidden',
    },
    sellerContent: {
        padding: SPACING.lg,
    },
    sellerHeader: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.md,
    },
    avatarContainer: {
        position: 'relative',
    },
    avatar: {
        elevation: 1,
    },
    verifiedBadge: {
        position: 'absolute',
        bottom: -2,
        right: -2,
        width: 20,
        height: 20,
        borderRadius: 10,
        justifyContent: 'center',
        alignItems: 'center',
        elevation: 2,
        borderWidth: 2,
        borderColor: '#fff',
    },
    sellerDetails: {
        flex: 1,
        gap: SPACING.xs,
    },
    verificationContainer: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.xs,
    },

    // Property card styles
    propertyCard: {
        borderRadius: BORDER_RADIUS.lg,
        overflow: 'hidden',
    },
    propertyContent: {
        padding: SPACING.lg,
        gap: SPACING.sm,
    },
    propertyHeader: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.sm,
    },
    propertyIcon: {
        width: 24,
    },
    locationContainer: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.xs,
        marginTop: SPACING.xs,
    },

    // Form styles
    formContainer: {
        gap: SPACING.lg,
    },
    formTitle: {
        marginBottom: SPACING.md,
    },
    textAreaContent: {
        textAlignVertical: 'top',
        paddingTop: SPACING.md,
        paddingBottom: SPACING.md,
        minHeight: 100,
    },
    messageTypesContainer: {
        gap: SPACING.sm,
    },
    fieldLabel: {
        marginBottom: SPACING.sm,
    },
    messageTypeGrid: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: SPACING.sm,
    },
    messageTypeOption: {
        width: '48%', // Important pour éviter l'overflow
    },
    messageTypeCard: {
        borderRadius: BORDER_RADIUS.md,
        overflow: 'hidden',
    },
    messageTypeContent: {
        alignItems: 'center',
        padding: SPACING.md,
        gap: SPACING.sm,
    },
    messageTypeIconContainer: {
        width: 40,
        height: 40,
        borderRadius: BORDER_RADIUS.md,
        justifyContent: 'center',
        alignItems: 'center',
    },
    messageTypeLabel: {
        textAlign: 'center',
        lineHeight: 16,
    },
    formField: {
        gap: SPACING.xs,
    },
    input: {
        fontSize: 14,
    },
    textAreaInput: {
        minHeight: 100,
    },
    errorText: {
        marginTop: SPACING.xs,
    },
    privacyNote: {
        flexDirection: 'row',
        alignItems: 'flex-start',
        gap: SPACING.sm,
        padding: SPACING.md,
        borderRadius: BORDER_RADIUS.md,
        marginTop: SPACING.sm,
    },

    // Send button styles
    sendButtonContainer: {
        position: 'absolute',
        bottom: 0,
        left: 0,
        right: 0,
        padding: SPACING.lg,
        borderTopWidth: 1,
        borderTopColor: 'rgba(0, 0, 0, 0.1)',
        elevation: ELEVATION.medium,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: -2 },
        shadowOpacity: 0.1,
        shadowRadius: 8,
    },
    sendButton: {
        borderRadius: BORDER_RADIUS.md,
        elevation: 2,
    },
    sendButtonContent: {
        height: 48,
        paddingHorizontal: SPACING.lg,
    },

    // Loading and error states
    backButton: {
        marginTop: SPACING.lg,
        borderRadius: BORDER_RADIUS.md,
    },
});