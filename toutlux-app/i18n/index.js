import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import * as Localization from 'expo-localization';

import en from './en';
import fr from './fr';

const resources = {
    en: { translation: en },
    fr: { translation: fr }
};

i18n
    .use(initReactI18next)
    .init({
        resources,
        lng: Localization.locale.split('-')[0],
        fallbackLng: 'fr',
        interpolation: {
            escapeValue: false,
        },
    });

export default i18n;