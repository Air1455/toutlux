import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Platform } from 'react-native';

/**
 * Hook personnalisé pour gérer les safe areas de manière flexible
 * @param {Object} options - Options de configuration
 * @param {boolean} options.top - Inclure le padding top (défaut: true)
 * @param {boolean} options.bottom - Inclure le padding bottom (défaut: true)
 * @param {boolean} options.left - Inclure le padding left (défaut: true)
 * @param {boolean} options.right - Inclure le padding right (défaut: true)
 * @param {number} options.additionalTop - Padding supplémentaire en haut
 * @param {number} options.additionalBottom - Padding supplémentaire en bas
 * @returns {Object} Objet avec les paddings calculés
 */
export const useSafeArea = (options = {}) => {
    const {
        top = true,
        bottom = true,
        left = true,
        right = true,
        additionalTop = 0,
        additionalBottom = 0
    } = options;

    const insets = useSafeAreaInsets();

    return {
        paddingTop: top ? insets.top + additionalTop : additionalTop,
        paddingBottom: bottom ? insets.bottom + additionalBottom : additionalBottom,
        paddingLeft: left ? insets.left : 0,
        paddingRight: right ? insets.right : 0,
        insets, // Exposer les insets bruts si nécessaire
        // Utilitaires pratiques
        safeAreaStyle: {
            paddingTop: top ? insets.top + additionalTop : additionalTop,
            paddingBottom: bottom ? insets.bottom + additionalBottom : additionalBottom,
            paddingLeft: left ? insets.left : 0,
            paddingRight: right ? insets.right : 0,
        }
    };
};

/**
 * Hook spécifique pour les écrans avec header
 */
export const useSafeAreaWithHeader = () => {
    return useSafeArea({ top: false }); // Le header gère déjà le top
};

/**
 * Hook pour les modals (généralement pas de safe area)
 */
export const useSafeAreaModal = () => {
    return useSafeArea({ top: false, bottom: false });
};

/**
 * Hook pour obtenir seulement la hauteur de la status bar
 */
export const useStatusBarHeight = () => {
    const insets = useSafeAreaInsets();
    return Platform.OS === 'ios' ? insets.top : insets.top;
};