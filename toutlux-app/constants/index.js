export { FONT_FAMILIES, TYPOGRAPHY } from '.@/utils//typography';

// Couleurs (déjà dans votre projet via les thèmes)
export { customLightTheme, customDarkTheme } from '@/utils/customTheme';

// Nouvelles constantes
export { LAYOUT } from './welcome';
export {
    SPACING,
    BORDER_RADIUS,
    ELEVATION,
    ICON_SIZES,
    BUTTON_HEIGHTS
} from './spacing';

export {
    SEMANTIC_COLORS,
    STATE_COLORS,
    PROPERTY_COLORS,
    OVERLAY_COLORS
} from './colors';

export {
    CARD_CONFIG,
    BUTTON_CONFIG,
    INPUT_CONFIG,
    HEADER_CONFIG,
    MODAL_CONFIG,
    LIST_CONFIG,
    IMAGE_CONFIG,
} from './components';

// Configuration générale de l'application
export const APP_CONFIG = {
    name: 'HomeEasy',
    version: '1.0.0',
    supportEmail: 'support@homeeasy.com',
    websiteUrl: 'https://homeeasy.com',

    // Pagination
    itemsPerPage: 20,
    maxItemsPerPage: 50,

    // Limites
    maxImageSize: 5 * 1024 * 1024, // 5MB
    maxImagesPerProperty: 10,
    maxDescriptionLength: 2000,

    // Timeouts
    requestTimeout: 30000, // 30 secondes
    imageLoadTimeout: 10000, // 10 secondes

    // Cache
    cacheTimeout: 5 * 60 * 1000, // 5 minutes

    // Animation
    animationDuration: 300,
    fastAnimationDuration: 150,
    slowAnimationDuration: 500,
};