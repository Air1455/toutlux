import { StyleSheet } from 'react-native';

// Configuration des polices par usage
export const FONT_FAMILIES = {
    primary: {
        light: 'Inter_300Light',
        regular: 'Inter_400Regular',
        medium: 'Inter_500Medium',
        semiBold: 'Inter_600SemiBold',
        bold: 'Inter_700Bold',
        extraBold: 'Inter_800ExtraBold',
    },
    display: {
        light: 'Poppins_300Light',
        regular: 'Poppins_400Regular',
        medium: 'Poppins_500Medium',
        semiBold: 'Poppins_600SemiBold',
        bold: 'Poppins_700Bold',
        extraBold: 'Poppins_800ExtraBold',
    },
    corporate: {
        light: 'SourceSansPro_300Light',
        regular: 'SourceSansPro_400Regular',
        semiBold: 'SourceSansPro_600SemiBold',
        bold: 'SourceSansPro_700Bold',
    },
};

// Styles de texte de base (sans couleur)
export const TYPOGRAPHY = StyleSheet.create({
    // === TITRES ET HEADERS ===
    heroTitle: {
        fontFamily: FONT_FAMILIES.display.bold,
        fontSize: 32,
        lineHeight: 38,
        letterSpacing: -0.5,
    },
    pageTitle: {
        fontFamily: FONT_FAMILIES.display.semiBold,
        fontSize: 24,
        lineHeight: 30,
        letterSpacing: -0.25,
    },
    sectionTitle: {
        fontFamily: FONT_FAMILIES.primary.semiBold,
        fontSize: 20,
        lineHeight: 26,
    },
    cardTitle: {
        fontFamily: FONT_FAMILIES.primary.semiBold,
        fontSize: 18,
        lineHeight: 24,
    },

    // === PRIX ET VALEURS ===
    priceHero: {
        fontFamily: FONT_FAMILIES.display.bold,
        fontSize: 28,
        lineHeight: 34,
        letterSpacing: -0.5,
    },
    priceCard: {
        fontFamily: FONT_FAMILIES.primary.bold,
        fontSize: 22,
        lineHeight: 28,
    },
    priceSmall: {
        fontFamily: FONT_FAMILIES.primary.semiBold,
        fontSize: 16,
        lineHeight: 20,
    },

    // === CORPS DE TEXTE ===
    bodyLarge: {
        fontFamily: FONT_FAMILIES.primary.regular,
        fontSize: 16,
        lineHeight: 24,
    },
    bodyMedium: {
        fontFamily: FONT_FAMILIES.primary.regular,
        fontSize: 14,
        lineHeight: 20,
    },
    bodySmall: {
        fontFamily: FONT_FAMILIES.primary.regular,
        fontSize: 12,
        lineHeight: 16,
    },

    // === LABELS ET MÉTADONNÉES ===
    labelLarge: {
        fontFamily: FONT_FAMILIES.primary.medium,
        fontSize: 14,
        lineHeight: 18,
        letterSpacing: 0.1,
    },
    labelMedium: {
        fontFamily: FONT_FAMILIES.primary.medium,
        fontSize: 12,
        lineHeight: 16,
        letterSpacing: 0.5,
    },
    labelSmall: {
        fontFamily: FONT_FAMILIES.primary.medium,
        fontSize: 10,
        lineHeight: 14,
        letterSpacing: 0.5,
        textTransform: 'uppercase',
    },

    // === BOUTONS ===
    buttonLarge: {
        fontFamily: FONT_FAMILIES.primary.semiBold,
        fontSize: 16,
        lineHeight: 20,
        letterSpacing: 0.1,
    },
    buttonMedium: {
        fontFamily: FONT_FAMILIES.primary.semiBold,
        fontSize: 14,
        lineHeight: 18,
        letterSpacing: 0.25,
    },
    buttonSmall: {
        fontFamily: FONT_FAMILIES.primary.medium,
        fontSize: 12,
        lineHeight: 16,
        letterSpacing: 0.5,
    },

    // === NAVIGATION ===
    tabLabel: {
        fontFamily: FONT_FAMILIES.primary.medium,
        fontSize: 12,
        lineHeight: 16,
        letterSpacing: 0.5,
    },
    headerTitle: {
        fontFamily: FONT_FAMILIES.primary.semiBold,
        fontSize: 18,
        lineHeight: 22,
    },

    // === SPÉCIALISÉS ===
    propertySpecs: {
        fontFamily: FONT_FAMILIES.primary.regular,
        fontSize: 14,
        lineHeight: 18,
    },
    agencyName: {
        fontFamily: FONT_FAMILIES.display.semiBold,
        fontSize: 16,
        lineHeight: 20,
        letterSpacing: 0.25,
    },
    addressText: {
        fontFamily: FONT_FAMILIES.primary.regular,
        fontSize: 14,
        lineHeight: 20,
    },

    // === UTILITAIRES ===
    subtitle: {
        fontFamily: FONT_FAMILIES.primary.regular,
        fontSize: 16,
        lineHeight: 22,
    },
    caption: {
        fontFamily: FONT_FAMILIES.primary.regular,
        fontSize: 12,
        lineHeight: 16,
    },
    overline: {
        fontFamily: FONT_FAMILIES.primary.medium,
        fontSize: 10,
        lineHeight: 14,
        letterSpacing: 1.5,
        textTransform: 'uppercase',
    },
});