import React, { useState } from 'react';
import { View, StyleSheet, ScrollView, TouchableOpacity } from 'react-native';
import { Text, useTheme, Checkbox, Card, Divider } from 'react-native-paper';
import { Controller } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';

const StepFour = ({ control, errors, watch }) => {
    const { colors } = useTheme();
    const { t } = useTranslation();
    const [expandedSection, setExpandedSection] = useState(null);

    const formData = watch();

    // Helper pour obtenir la valeur actuelle
    const getValue = (fieldName) => {
        return formData[fieldName] || false;
    };

    // Sections des termes et conditions
    const termsSections = [
        {
            id: 'service',
            title: t('terms.service.title', 'Utilisation du service'),
            icon: 'cog',
            content: t('terms.service.content', `Notre plateforme immobilière vous permet de rechercher, publier et gérer des biens immobiliers.

Les utilisateurs s'engagent à :
• Utiliser la plateforme de manière légale et éthique
• Fournir des informations véridiques et actualisées
• Respecter les autres utilisateurs et leurs biens
• Ne pas publier de contenu inapproprié ou frauduleux`)
        },
        {
            id: 'data',
            title: t('terms.data.title', 'Protection des données'),
            icon: 'shield-account',
            content: t('terms.data.content', `Nous nous engageons à protéger vos données personnelles conformément au RGPD.

Données collectées :
• Informations de profil (nom, email, téléphone)
• Documents d'identité et financiers pour la vérification
• Historique des recherches et interactions

Utilisation des données :
• Vérification d'identité et sécurisation des transactions
• Amélioration de nos services
• Communication liée à vos demandes`)
        },
        {
            id: 'verification',
            title: t('terms.verification.title', 'Vérification d\'identité'),
            icon: 'certificate',
            content: t('terms.verification.content', `Pour garantir la sécurité de tous les utilisateurs, nous procédons à des vérifications d'identité.

Processus de vérification :
• Validation de l'email et du numéro de téléphone
• Vérification des documents d'identité
• Contrôle des justificatifs financiers

Ces vérifications nous permettent de :
• Lutter contre la fraude
• Sécuriser les transactions
• Créer un environnement de confiance`)
        },
        {
            id: 'responsibilities',
            title: t('terms.responsibilities.title', 'Responsabilités'),
            icon: 'handshake',
            content: t('terms.responsibilities.content', `Responsabilités de la plateforme :
• Fournir un service accessible et sécurisé
• Protéger vos données personnelles
• Faciliter les mises en relation entre utilisateurs

Responsabilités des utilisateurs :
• Vérifier les informations des biens avant transaction
• Respecter les conditions des contrats de location/vente
• Signaler tout contenu inapproprié ou frauduleux`)
        },
    ];

    const toggleSection = (sectionId) => {
        setExpandedSection(expandedSection === sectionId ? null : sectionId);
    };

    const renderTermsSection = (section) => (
        <Card key={section.id} style={[styles.sectionCard, { backgroundColor: colors.surface }]}>
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
                    <Text style={[styles.sectionTitle, { color: colors.onSurface }]}>
                        {section.title}
                    </Text>
                </View>
                <MaterialCommunityIcons
                    name={expandedSection === section.id ? 'chevron-up' : 'chevron-down'}
                    size={24}
                    color={colors.onSurfaceVariant}
                />
            </TouchableOpacity>

            {expandedSection === section.id && (
                <View style={styles.sectionContent}>
                    <Divider style={styles.sectionDivider} />
                    <Text style={[styles.sectionText, { color: colors.onSurface }]}>
                        {section.content}
                    </Text>
                </View>
            )}
        </Card>
    );

    return (
        <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
            {/* En-tête */}
            <View style={styles.header}>
                <Text style={[styles.title, { color: colors.onBackground }]}>
                    {t('onboarding.step4.title', 'Termes et conditions')}
                </Text>
                <Text style={[styles.subtitle, { color: colors.onSurfaceVariant }]}>
                    {t('onboarding.step4.subtitle', 'Lisez et acceptez nos conditions pour finaliser votre inscription')}
                </Text>
            </View>

            {/* Sections des termes */}
            <View style={styles.section}>
                <Text style={[styles.sectionMainTitle, { color: colors.onSurface }]}>
                    {t('terms.title', 'Conditions d\'utilisation')}
                </Text>

                <View style={styles.termsContainer}>
                    {termsSections.map(renderTermsSection)}
                </View>
            </View>

            {/* Cases à cocher */}
            <View style={styles.section}>
                <Text style={[styles.sectionMainTitle, { color: colors.onSurface }]}>
                    {t('terms.acceptance.title', 'Acceptation des conditions')}
                </Text>

                {/* Termes et conditions obligatoires */}
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
                            <Text style={[styles.checkboxText, { color: colors.onSurface }]}>
                                {t('terms.acceptance.terms', 'J\'accepte les conditions d\'utilisation')} *
                            </Text>
                        </TouchableOpacity>
                    )}
                />

                {errors.termsAccepted && (
                    <Text style={[styles.errorText, { color: colors.error }]}>
                        {errors.termsAccepted.message}
                    </Text>
                )}

                {/* Politique de confidentialité obligatoire */}
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
                            <Text style={[styles.checkboxText, { color: colors.onSurface }]}>
                                {t('terms.acceptance.privacy', 'J\'accepte la politique de confidentialité')} *
                            </Text>
                        </TouchableOpacity>
                    )}
                />

                {errors.privacyAccepted && (
                    <Text style={[styles.errorText, { color: colors.error }]}>
                        {errors.privacyAccepted.message}
                    </Text>
                )}

                {/* Communications marketing optionnelles */}
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
                            <Text style={[styles.checkboxText, { color: colors.onSurface }]}>
                                {t('terms.acceptance.marketing', 'J\'accepte de recevoir des communications marketing')} ({t('form.optional', 'optionnel')})
                            </Text>
                        </TouchableOpacity>
                    )}
                />

                <Text style={[styles.optionalNote, { color: colors.onSurfaceVariant }]}>
                    {t('terms.acceptance.marketingNote', 'Vous pouvez modifier ce choix à tout moment dans vos paramètres')}
                </Text>
            </View>

            {/* Résumé des droits */}
            <View style={styles.section}>
                <LinearGradient
                    colors={[colors.primaryContainer + '30', colors.primaryContainer + '15']}
                    style={styles.rightsContainer}
                >
                    <View style={styles.rightsHeader}>
                        <MaterialCommunityIcons
                            name="account-check"
                            size={24}
                            color={colors.primary}
                        />
                        <Text style={[styles.rightsTitle, { color: colors.onPrimaryContainer }]}>
                            {t('terms.rights.title', 'Vos droits')}
                        </Text>
                    </View>

                    <View style={styles.rightsList}>
                        <View style={styles.rightItem}>
                            <MaterialCommunityIcons name="eye" size={16} color={colors.primary} />
                            <Text style={[styles.rightText, { color: colors.onPrimaryContainer }]}>
                                {t('terms.rights.access', 'Droit d\'accès à vos données')}
                            </Text>
                        </View>

                        <View style={styles.rightItem}>
                            <MaterialCommunityIcons name="pencil" size={16} color={colors.primary} />
                            <Text style={[styles.rightText, { color: colors.onPrimaryContainer }]}>
                                {t('terms.rights.rectification', 'Droit de rectification')}
                            </Text>
                        </View>

                        <View style={styles.rightItem}>
                            <MaterialCommunityIcons name="delete" size={16} color={colors.primary} />
                            <Text style={[styles.rightText, { color: colors.onPrimaryContainer }]}>
                                {t('terms.rights.deletion', 'Droit à l\'effacement')}
                            </Text>
                        </View>

                        <View style={styles.rightItem}>
                            <MaterialCommunityIcons name="download" size={16} color={colors.primary} />
                            <Text style={[styles.rightText, { color: colors.onPrimaryContainer }]}>
                                {t('terms.rights.portability', 'Droit à la portabilité')}
                            </Text>
                        </View>
                    </View>

                    <Text style={[styles.rightsNote, { color: colors.onPrimaryContainer }]}>
                        {t('terms.rights.contact', 'Contactez-nous à tout moment pour exercer vos droits')}
                    </Text>
                </LinearGradient>
            </View>

            {/* Information finale */}
            <View style={styles.section}>
                <LinearGradient
                    colors={[colors.secondaryContainer + '25', colors.secondaryContainer + '10']}
                    style={styles.finalInfo}
                >
                    <View style={styles.finalHeader}>
                        <MaterialCommunityIcons
                            name="check-circle"
                            size={24}
                            color={colors.secondary}
                        />
                        <Text style={[styles.finalTitle, { color: colors.onSecondaryContainer }]}>
                            {t('terms.final.title', 'Dernière étape')}
                        </Text>
                    </View>
                    <Text style={[styles.finalText, { color: colors.onSecondaryContainer }]}>
                        {t('terms.final.message', 'En acceptant ces conditions, vous finalisez la création de votre compte. Vous pourrez immédiatement commencer à utiliser tous nos services.')}
                    </Text>
                </LinearGradient>
            </View>

            {/* Espace pour les boutons */}
            <View style={styles.bottomSpace} />
        </ScrollView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    header: {
        marginBottom: 32,
        alignItems: 'center',
    },
    title: {
        fontSize: 24,
        fontWeight: 'bold',
        textAlign: 'center',
        marginBottom: 8,
    },
    subtitle: {
        fontSize: 16,
        textAlign: 'center',
        lineHeight: 22,
    },
    section: {
        marginBottom: 24,
    },
    sectionMainTitle: {
        fontSize: 20,
        fontWeight: '600',
        marginBottom: 16,
    },
    termsContainer: {
        gap: 8,
    },
    sectionCard: {
        borderRadius: 12,
        elevation: 1,
    },
    sectionHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        padding: 16,
    },
    sectionHeaderContent: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 12,
        flex: 1,
    },
    sectionTitle: {
        fontSize: 16,
        fontWeight: '600',
        flex: 1,
    },
    sectionContent: {
        paddingHorizontal: 16,
        paddingBottom: 16,
    },
    sectionDivider: {
        marginBottom: 12,
    },
    sectionText: {
        fontSize: 14,
        lineHeight: 22,
    },
    checkboxContainer: {
        flexDirection: 'row',
        alignItems: 'flex-start',
        marginBottom: 12,
        gap: 8,
    },
    checkboxText: {
        fontSize: 16,
        lineHeight: 22,
        flex: 1,
        marginTop: 2,
    },
    optionalNote: {
        fontSize: 12,
        fontStyle: 'italic',
        marginTop: 8,
        marginLeft: 40,
    },
    errorText: {
        fontSize: 14,
        marginLeft: 40,
        marginTop: -8,
        marginBottom: 12,
    },
    rightsContainer: {
        padding: 16,
        borderRadius: 12,
        borderWidth: 1,
        borderColor: 'rgba(0,0,0,0.1)',
    },
    rightsHeader: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 12,
        marginBottom: 16,
    },
    rightsTitle: {
        fontSize: 16,
        fontWeight: '600',
    },
    rightsList: {
        gap: 8,
        marginBottom: 12,
    },
    rightItem: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 8,
    },
    rightText: {
        fontSize: 14,
        flex: 1,
    },
    rightsNote: {
        fontSize: 12,
        fontStyle: 'italic',
        lineHeight: 18,
    },
    finalInfo: {
        padding: 16,
        borderRadius: 12,
        borderWidth: 1,
        borderColor: 'rgba(0,0,0,0.1)',
    },
    finalHeader: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 12,
        marginBottom: 12,
    },
    finalTitle: {
        fontSize: 16,
        fontWeight: '600',
    },
    finalText: {
        fontSize: 14,
        lineHeight: 20,
    },
    bottomSpace: {
        height: 40,
    },
});

export default StepFour;