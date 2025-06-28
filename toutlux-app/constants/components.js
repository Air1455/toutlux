// constants/components.js
import { SPACING, BORDER_RADIUS, BUTTON_HEIGHTS } from './spacing';

// Configuration des composants Card
export const CARD_CONFIG = {
    borderRadius: BORDER_RADIUS.lg,
    padding: SPACING.lg,
    margin: SPACING.sm,
    elevation: 2,
    minHeight: 120,
};

// Configuration des boutons
export const BUTTON_CONFIG = {
    borderRadius: BORDER_RADIUS.lg,
    paddingHorizontal: SPACING.xl,
    paddingVertical: SPACING.sm,
    heights: BUTTON_HEIGHTS,
    iconSize: 20,
};

// Configuration des inputs
export const INPUT_CONFIG = {
    borderRadius: BORDER_RADIUS.md,
    padding: SPACING.md,
    height: 48,
    fontSize: 16,
};

// Configuration des headers
export const HEADER_CONFIG = {
    height: 56,
    paddingHorizontal: SPACING.lg,
    elevation: 2,
    titleSize: 18,
    iconSize: 24,
};

// Configuration des modals
export const MODAL_CONFIG = {
    borderRadius: BORDER_RADIUS.xl,
    padding: SPACING.xl,
    margin: SPACING.xl,
    backdropOpacity: 0.5,
};

// Configuration des listes
export const LIST_CONFIG = {
    itemHeight: 72,
    itemPadding: SPACING.lg,
    separatorHeight: 1,
    sectionHeaderHeight: 40,
};

// Configuration des images
export const IMAGE_CONFIG = {
    borderRadius: BORDER_RADIUS.md,
    aspectRatio: {
        square: 1,
        landscape: 16/9,
        portrait: 9/16,
        card: 3/2,
    },
    placeholder: {
        backgroundColor: '#F5F5F5',
        color: '#BDBDBD',
    },
};