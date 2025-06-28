// utils/colorUtils.js - Version corrigée

/**
 * Convertit une couleur en format rgba avec transparence
 * @param {string} color - Couleur de base (hex, rgb, ou nom)
 * @param {number} opacity - Opacité entre 0 et 1
 * @returns {string} - Couleur au format rgba
 */
export const addOpacity = (color, opacity = 0.1) => {
    // Si c'est déjà une couleur rgba, on la retourne telle quelle
    if (color.includes('rgba')) {
        return color;
    }

    // Si c'est une couleur hex
    if (color.startsWith('#')) {
        const hex = color.replace('#', '');

        // Gérer les hex de 3 ou 6 caractères
        let fullHex = hex;
        if (hex.length === 3) {
            fullHex = hex.split('').map(char => char + char).join('');
        }

        const r = parseInt(fullHex.slice(0, 2), 16);
        const g = parseInt(fullHex.slice(2, 4), 16);
        const b = parseInt(fullHex.slice(4, 6), 16);

        return `rgba(${r}, ${g}, ${b}, ${opacity})`;
    }

    // Si c'est une couleur rgb
    if (color.startsWith('rgb(')) {
        const values = color.match(/\d+/g);
        if (values && values.length === 3) {
            return `rgba(${values[0]}, ${values[1]}, ${values[2]}, ${opacity})`;
        }
    }

    // Pour les couleurs nommées ou autres, on retourne une couleur de fallback
    return `rgba(103, 80, 164, ${opacity})`;
};

/**
 * Convertit une couleur hex en RGB
 * @param {string} hex - Couleur hexadécimale
 * @returns {object} - Objet avec r, g, b
 */
export const hexToRgb = (hex) => {
    const cleanHex = hex.replace('#', '');

    let fullHex = cleanHex;
    if (cleanHex.length === 3) {
        fullHex = cleanHex.split('').map(char => char + char).join('');
    }

    return {
        r: parseInt(fullHex.slice(0, 2), 16),
        g: parseInt(fullHex.slice(2, 4), 16),
        b: parseInt(fullHex.slice(4, 6), 16)
    };
};

/**
 * Couleurs prédéfinies avec transparence pour l'application
 */
export const transparentColors = {
    primary: {
        5: 'rgba(103, 80, 164, 0.05)',
        10: 'rgba(103, 80, 164, 0.1)',
        15: 'rgba(103, 80, 164, 0.15)',
        20: 'rgba(103, 80, 164, 0.2)',
        30: 'rgba(103, 80, 164, 0.3)',
        40: 'rgba(103, 80, 164, 0.4)',
    },
    success: {
        5: 'rgba(81, 207, 102, 0.05)',
        10: 'rgba(81, 207, 102, 0.1)',
        15: 'rgba(81, 207, 102, 0.15)',
        20: 'rgba(81, 207, 102, 0.2)',
        30: 'rgba(81, 207, 102, 0.3)',
    },
    warning: {
        5: 'rgba(255, 169, 77, 0.05)',
        10: 'rgba(255, 169, 77, 0.1)',
        15: 'rgba(255, 169, 77, 0.15)',
        20: 'rgba(255, 169, 77, 0.2)',
        30: 'rgba(255, 169, 77, 0.3)',
    },
    error: {
        5: 'rgba(244, 67, 54, 0.05)',
        10: 'rgba(244, 67, 54, 0.1)',
        15: 'rgba(244, 67, 54, 0.15)',
        20: 'rgba(244, 67, 54, 0.2)',
        30: 'rgba(244, 67, 54, 0.3)',
    },
    neutral: {
        3: 'rgba(0, 0, 0, 0.03)',
        5: 'rgba(0, 0, 0, 0.05)',
        10: 'rgba(0, 0, 0, 0.1)',
        15: 'rgba(0, 0, 0, 0.15)',
        20: 'rgba(0, 0, 0, 0.2)',
        30: 'rgba(0, 0, 0, 0.3)',
    },
    white: {
        5: 'rgba(255, 255, 255, 0.05)',
        10: 'rgba(255, 255, 255, 0.1)',
        20: 'rgba(255, 255, 255, 0.2)',
        30: 'rgba(255, 255, 255, 0.3)',
        90: 'rgba(255, 255, 255, 0.9)',
    }
};

/**
 * Couleurs spécifiques pour les statuts de validation
 */
export const validationColors = {
    verified: {
        background: transparentColors.success[10],
        border: '#51cf66',
        text: '#2b8a3e'
    },
    pending: {
        background: transparentColors.warning[10],
        border: '#ffa94d',
        text: '#e8590c'
    },
    error: {
        background: transparentColors.error[10],
        border: '#f44336',
        text: '#c62828'
    }
};