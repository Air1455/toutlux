import React, { useState } from 'react';
import { View, StyleSheet, ScrollView, TouchableOpacity } from 'react-native';
import { useTheme, Checkbox, Card, Divider } from 'react-native-paper';
import { Controller } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';

import Text from '@/components/typography/Text';
import { SPACING, BORDER_RADIUS, ELEVATION } from '@/constants/spacing';

const StepFour = ({ control, errors, watch }) => {
    const { colors } = useTheme();
    const { t } = useTranslation();
    const [expandedSection, setExpandedSection] = useState(null);

    const formData = watch();

    const getValue = (fieldName) => {
        return formData[fieldName] || false;
    };

    const termsSections = [
        {
            id: 'service',
            title: t('terms.service.title'),
            icon: 'cog',
            content: t('terms.service.content')
        },
        {
            id: 'data',
            title: t('terms.data.title'),
            icon: 'shield-account',
            content: t('terms.data.content')
        },
        {
            id: 'verification',
            title: t('terms.verification.title'),
            icon: 'certificate',
            content: t('terms.verification.content')
        },
        {
            id: 'responsibilities',
            title: t('terms.responsibilities.title'),
            icon: 'handshake',
            content: t('terms.responsibilities.content')
        },
    ];

    const toggleSection = (sectionId) => {
        setExpandedSection(expandedSection === sectionId ? null : sectionId);
    };

    const renderTermsSection = (section) => (
        <Card
            key={section.id}
            style={[
                styles.sectionCard,
                {
                    backgroundColor: colors.surface,
                    borderRadius: BORDER_RADIUS.lg
                }
            ]}
            elevation={ELEVATION.low}
        >
            <TouchableOpacity
                onPress={() => toggleSection(section.id)}
                style={styles.sectionHeader}
            >
                <View style={styles.sectionHeaderContent}>
                    <MaterialCommunityIcons
                        name={section.icon}
                        size={24}
                        color={colors.primary}
                    />
                    <Text variant="labelLarge" color="textPrimary" style={styles.sectionTitle}>
                        {section.title}
                    </Text>
                </View>
                <MaterialCommunityIcons
                    name={expandedSection === section.id ? 'chevron-up' : 'chevron-down'}
                    size={24}
                    color={colors.textSecondary}
                />
            </TouchableOpacity>

            {expandedSection === section.id && (
                <View style={styles.sectionContent}>
                    <Divider style={styles.sectionDivider} />
                    <Text variant="bodyMedium" color="textPrimary" style={styles.sectionText}>
                        {section.content}
                    </Text>
                </View>
            )}
        </Card>
    );

    return (
        <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
            <View style={styles.header}>
                <Text variant="pageTitle" color="textPrimary" style={styles.title}>
                    {t('onboarding.step4.title')}
                </Text>
                <Text variant="bodyLarge" color="textSecondary" style={styles.subtitle}>
                    {t('onboarding.step4.subtitle')}
                </Text>
            </View>

            <View style={styles.section}>
                <Text variant="sectionTitle" color="textPrimary" style={styles.sectionMainTitle}>
                    {t('terms.title')}
                </Text>

                <View style={styles.termsContainer}>
                    {termsSections.map(renderTermsSection)}
                </View>
            </View>

            <View style={styles.section}>
                <Text variant="sectionTitle" color="textPrimary" style={styles.sectionMainTitle}>
                    {t('terms.acceptance.title')}
                </Text>

                <Controller
                    control={control}
                    name="termsAccepted"
                    render={({ field: { onChange, value } }) => (
                        <TouchableOpacity
                            style={styles.checkboxContainer}
                            onPress={() => onChange(!value)}
                        >
                            <Checkbox
                                status={value ? 'checked' : 'unchecked'}
                                onPress={() => onChange(!value)}
                                color={colors.primary}
                            />
                            <Text variant="bodyLarge" color="textPrimary" style={styles.checkboxText}>
                                {t('terms.acceptance.terms')} *
                            </Text>
                        </TouchableOpacity>
                    )}
                />

                {errors.termsAccepted && (
                    <Text variant="labelMedium" color="error" style={styles.errorText}>
                        {errors.termsAccepted.message}
                    </Text>
                )}

                <Controller
                    control={control}
                    name="privacyAccepted"
                    render={({ field: { onChange, value } }) => (
                        <TouchableOpacity
                            style={styles.checkboxContainer}
                            onPress={() => onChange(!value)}
                        >
                            <Checkbox
                                status={value ? 'checked' : 'unchecked'}
                                onPress={() => onChange(!value)}
                                color={colors.primary}
                            />
                            <Text variant="bodyLarge" color="textPrimary" style={styles.checkboxText}>
                                {t('terms.acceptance.privacy')} *
                            </Text>
                        </TouchableOpacity>
                    )}
                />

                {errors.privacyAccepted && (
                    <Text variant="labelMedium" color="error" style={styles.errorText}>
                        {errors.privacyAccepted.message}
                    </Text>
                )}

                <Controller
                    control={control}
                    name="marketingAccepted"
                    render={({ field: { onChange, value } }) => (
                        <TouchableOpacity
                            style={styles.checkboxContainer}
                            onPress={() => onChange(!value)}
                        >
                            <Checkbox
                                status={value ? 'checked' : 'unchecked'}
                                onPress={() => onChange(!value)}
                                color={colors.primary}
                            />
                            <Text variant="bodyLarge" color="textPrimary" style={styles.checkboxText}>
                                {t('terms.acceptance.marketing')} ({t('form.optional')})
                            </Text>
                        </TouchableOpacity>
                    )}
                />

                <Text variant="labelMedium" color="textHint" style={styles.optionalNote}>
                    {t('terms.acceptance.marketingNote')}
                </Text>
            </View>

            <View style={styles.section}>
                <LinearGradient
                    colors={[colors.primaryContainer + '30', colors.primaryContainer + '15']}
                    style={[styles.rightsContainer, { borderRadius: BORDER_RADIUS.lg }]}
                >
                    <View style={styles.rightsHeader}>
                        <MaterialCommunityIcons
                            name="account-check"
                            size={24}
                            color={colors.primary}
                        />
                        <Text variant="labelLarge" color="textPrimary" style={styles.rightsTitle}>
                            {t('terms.rights.title')}
                        </Text>
                    </View>

                    <View style={styles.rightsList}>
                        <View style={styles.rightItem}>
                            <MaterialCommunityIcons name="eye" size={16} color={colors.primary} />
                            <Text variant="bodyMedium" color="textPrimary" style={styles.rightText}>
                                {t('terms.rights.access')}
                            </Text>
                        </View>

                        <View style={styles.rightItem}>
                            <MaterialCommunityIcons name="pencil" size={16} color={colors.primary} />
                            <Text variant="bodyMedium" color="textPrimary" style={styles.rightText}>
                                {t('terms.rights.rectification')}
                            </Text>
                        </View>

                        <View style={styles.rightItem}>
                            <MaterialCommunityIcons name="delete" size={16} color={colors.primary} />
                            <Text variant="bodyMedium" color="textPrimary" style={styles.rightText}>
                                {t('terms.rights.deletion')}
                            </Text>
                        </View>

                        <View style={styles.rightItem}>
                            <MaterialCommunityIcons name="download" size={16} color={colors.primary} />
                            <Text variant="bodyMedium" color="textPrimary" style={styles.rightText}>
                                {t('terms.rights.portability')}
                            </Text>
                        </View>
                    </View>

                    <Text variant="labelMedium" color="textSecondary" style={styles.rightsNote}>
                        {t('terms.rights.contact')}
                    </Text>
                </LinearGradient>
            </View>

            <View style={styles.section}>
                <LinearGradient
                    colors={[colors.secondaryContainer + '25', colors.secondaryContainer + '10']}
                    style={[styles.finalInfo, { borderRadius: BORDER_RADIUS.lg }]}
                >
                    <View style={styles.finalHeader}>
                        <MaterialCommunityIcons
                            name="check-circle"
                            size={24}
                            color={colors.secondary}
                        />
                        <Text variant="labelLarge" color="textPrimary" style={styles.finalTitle}>
                            {t('terms.final.title')}
                        </Text>
                    </View>
                    <Text variant="bodyMedium" color="textSecondary" style={styles.finalText}>
                        {t('terms.final.message')}
                    </Text>
                </LinearGradient>
            </View>

            <View style={styles.bottomSpace} />
        </ScrollView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    header: {
        marginBottom: SPACING.xxxl,
        alignItems: 'center',
    },
    title: {
        textAlign: 'center',
        marginBottom: SPACING.sm,
    },
    subtitle: {
        textAlign: 'center',
        lineHeight: 22,
    },
    section: {
        marginBottom: SPACING.xl,
    },
    sectionMainTitle: {
        marginBottom: SPACING.lg,
    },
    termsContainer: {
        gap: SPACING.sm,
    },
    sectionCard: {
        overflow: 'hidden',
    },
    sectionHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        padding: SPACING.lg,
    },
    sectionHeaderContent: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.md,
        flex: 1,
    },
    sectionTitle: {
        flex: 1,
    },
    sectionContent: {
        paddingHorizontal: SPACING.lg,
        paddingBottom: SPACING.lg,
    },
    sectionDivider: {
        marginBottom: SPACING.md,
    },
    sectionText: {
        lineHeight: 22,
    },
    checkboxContainer: {
        flexDirection: 'row',
        alignItems: 'flex-start',
        marginBottom: SPACING.md,
        gap: SPACING.sm,
    },
    checkboxText: {
        lineHeight: 22,
        flex: 1,
        marginTop: 2,
    },
    optionalNote: {
        fontStyle: 'italic',
        marginTop: SPACING.sm,
        marginLeft: 40,
    },
    errorText: {
        marginLeft: 40,
        marginTop: -SPACING.sm,
        marginBottom: SPACING.md,
    },
    rightsContainer: {
        padding: SPACING.lg,
        borderWidth: 1,
        borderColor: 'rgba(0,0,0,0.1)',
    },
    rightsHeader: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.md,
        marginBottom: SPACING.lg,
    },
    rightsTitle: {
        // Typography géré par le composant Text
    },
    rightsList: {
        gap: SPACING.sm,
        marginBottom: SPACING.md,
    },
    rightItem: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.sm,
    },
    rightText: {
        flex: 1,
    },
    rightsNote: {
        fontStyle: 'italic',
        lineHeight: 18,
    },
    finalInfo: {
        padding: SPACING.lg,
        borderWidth: 1,
        borderColor: 'rgba(0,0,0,0.1)',
    },
    finalHeader: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.md,
        marginBottom: SPACING.md,
    },
    finalTitle: {
        // Typography géré par le composant Text
    },
    finalText: {
        lineHeight: 20,
    },
    bottomSpace: {
        height: SPACING.huge,
    },
});

export default StepFour;