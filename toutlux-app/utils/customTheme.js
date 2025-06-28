import { MD3DarkTheme, MD3LightTheme } from 'react-native-paper';

export const customLightTheme = {
    ...MD3LightTheme,
    colors: {
        ...MD3LightTheme.colors,
        "text": "#3a3a3a",
        "primary": "#bf8b19",
        "onPrimary": '#ffffff',
        "primaryContainer": '#ffe082',
        "onPrimaryContainer": '#3e2f00',
        "secondary": "rgb(89, 94, 114)",
        "onSecondary": "rgb(255, 255, 255)",
        "secondaryContainer": "rgb(221, 225, 249)",
        "onSecondaryContainer": "rgb(22, 27, 44)",
        "tertiary": "#084ca2",
        "onTertiary": "rgb(255, 255, 255)",
        "tertiaryContainer": "rgb(255, 214, 247)",
        "onTertiaryContainer": "rgb(43, 18, 42)",
        "error": "rgb(186, 26, 26)",
        "onError": "rgb(255, 255, 255)",
        "errorContainer": "rgb(255, 218, 214)",
        "onErrorContainer": "rgb(65, 0, 2)",

        // 🔧 COULEURS AMÉLIORÉES :
        "background": "rgb(248, 249, 250)",      // Gris très clair pour le fond général
        "onBackground": "rgb(27, 27, 31)",
        "surface": "#ffffff",                     // Blanc pur pour les cards/éléments
        "onSurface": "rgb(27, 27, 31)",

        "surfaceVariant": "rgb(226, 225, 236)",
        "onSurfaceVariant": "rgb(69, 70, 79)",
        "outline": "rgba(0, 0, 0, .1)",
        "outlineVariant": "rgb(198, 198, 208)",
        "shadow": "rgb(0, 0, 0)",
        "scrim": "rgb(0, 0, 0)",
        "inverseSurface": "rgb(48, 48, 52)",
        "inverseOnSurface": "rgb(242, 240, 244)",
        "inversePrimary": "rgb(180, 196, 255)",
        "elevation": {
            "level0": "transparent",
            "level1": "#ffffff",                   // Blanc pour les éléments flottants
            "level2": "rgb(250, 250, 252)",      // Très légèrement teinté
            "level3": "rgb(245, 245, 248)",      // Plus de contraste
            "level4": "rgb(242, 242, 246)",
            "level5": "rgb(238, 238, 243)"
        },
        "surfaceDisabled": "rgba(27, 27, 31, 0.12)",
        "onSurfaceDisabled": "rgba(27, 27, 31, 0.38)",
        "backdrop": "rgba(46, 48, 56, 0.4)",

        // 🎨 COULEURS DE TEXTE PERSONNALISÉES (LIGHT THEME)
        "textPrimary": "rgb(27, 27, 31)",        // Titres principaux, texte important
        "textSecondary": "rgb(69, 70, 79)",      // Descriptions, métadonnées
        "textDisabled": "rgba(27, 27, 31, 0.38)", // Texte désactivé
        "textPlaceholder": "rgb(69, 70, 79)",    // Placeholder dans les inputs
        "textSuccess": "#bf8b19",                // Texte de succès (couleur dorée)
        "textError": "rgb(186, 26, 26)",         // Texte d'erreur
        "textWarning": "#084ca2",                // Texte d'avertissement
        "textHint": "rgb(69, 70, 79)",           // Hints et descriptions subtiles
        "textPrice": "#bf8b19",                  // Prix et valeurs monétaires
        "textOnCard": "rgb(27, 27, 31)",         // Texte sur les cards/surfaces
        "textSubtle": "rgba(27, 27, 31, 0.6)",   // Texte très subtil
    }
}

export const customDarkTheme = {
    ...MD3DarkTheme,
    colors: {
        ...MD3DarkTheme.colors,
        "text": "#ffffff",
        "primary": "#bf8b19",
        "onPrimary": '#3e2f00',
        "primaryContainer": '#735e00',
        "onPrimaryContainer": '#ffe082',
        "secondary": "rgb(193, 197, 221)",
        "onSecondary": "rgb(43, 48, 66)",
        "secondaryContainer": "rgb(65, 70, 89)",
        "onSecondaryContainer": "rgb(221, 225, 249)",
        "tertiary": "#084ca2",
        "onTertiary": "rgb(66, 39, 64)",
        "tertiaryContainer": "rgb(91, 61, 88)",
        "onTertiaryContainer": "rgb(255, 214, 247)",
        "error": "rgb(255, 180, 171)",
        "onError": "rgb(105, 0, 5)",
        "errorContainer": "rgb(147, 0, 10)",
        "onErrorContainer": "rgb(255, 180, 171)",
        "placeholder": '#484848',

        // 🔧 COULEURS AMÉLIORÉES :
        "background": "rgb(16, 16, 20)",         // Foncé mais pas noir
        "onBackground": "rgb(228, 226, 230)",
        "surface": "rgb(32, 32, 36)",           // Plus clair que background
        "onSurface": "rgb(228, 226, 230)",

        "surfaceVariant": "rgb(69, 70, 79)",
        "onSurfaceVariant": "rgb(198, 198, 208)",
        "outline": "rgba(255, 255, 255, .1)",
        "outlineVariant": "rgb(69, 70, 79)",
        "shadow": "rgb(0, 0, 0)",
        "scrim": "rgb(0, 0, 0)",
        "inverseSurface": "rgb(228, 226, 230)",
        "inverseOnSurface": "rgb(48, 48, 52)",
        "inversePrimary": "rgb(61, 90, 172)",
        "elevation": {
            "level0": "transparent",
            "level1": "rgb(40, 40, 44)",         // Légèrement plus clair que surface
            "level2": "rgb(44, 44, 48)",         // Progression logique
            "level3": "rgb(48, 48, 52)",
            "level4": "rgb(50, 50, 54)",
            "level5": "rgb(54, 54, 58)"
        },
        "surfaceDisabled": "rgba(228, 226, 230, 0.12)",
        "onSurfaceDisabled": "rgba(228, 226, 230, 0.38)",
        "backdrop": "rgba(46, 48, 56, 0.4)",

        // 🎨 COULEURS DE TEXTE PERSONNALISÉES (DARK THEME)
        "textPrimary": "rgb(228, 226, 230)",     // Titres principaux, texte important
        "textSecondary": "rgb(198, 198, 208)",   // Descriptions, métadonnées
        "textDisabled": "rgba(228, 226, 230, 0.38)", // Texte désactivé
        "textPlaceholder": "#484848",            // Placeholder dans les inputs
        "textSuccess": "#bf8b19",                // Texte de succès (couleur dorée)
        "textError": "rgb(255, 180, 171)",       // Texte d'erreur
        "textWarning": "#084ca2",                // Texte d'avertissement
        "textHint": "rgb(198, 198, 208)",        // Hints et descriptions subtiles
        "textPrice": "#bf8b19",                  // Prix et valeurs monétaires
        "textOnCard": "rgb(228, 226, 230)",      // Texte sur les cards/surfaces
        "textSubtle": "rgba(228, 226, 230, 0.6)", // Texte très subtil
    }
}

export default {customLightTheme, customDarkTheme}
