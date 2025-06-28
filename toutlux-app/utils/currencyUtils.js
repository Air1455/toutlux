export const GLOBAL_CURRENCIES = {
    // Devises majeures
    USD: { code: 'USD', name: 'US Dollar', symbol: '$', locale: 'en-US', region: 'North America', popular: true },
    EUR: { code: 'EUR', name: 'Euro', symbol: '€', locale: 'de-DE', region: 'Europe', popular: true },
    GBP: { code: 'GBP', name: 'British Pound', symbol: '£', locale: 'en-GB', region: 'Europe', popular: true },
    JPY: { code: 'JPY', name: 'Japanese Yen', symbol: '¥', locale: 'ja-JP', region: 'Asia', popular: true },
    CNY: { code: 'CNY', name: 'Chinese Yuan', symbol: '¥', locale: 'zh-CN', region: 'Asia', popular: true },
    CHF: { code: 'CHF', name: 'Swiss Franc', symbol: 'CHF', locale: 'de-CH', region: 'Europe', popular: true },
    CAD: { code: 'CAD', name: 'Canadian Dollar', symbol: 'C$', locale: 'en-CA', region: 'North America', popular: true },
    AUD: { code: 'AUD', name: 'Australian Dollar', symbol: 'A$', locale: 'en-AU', region: 'Oceania', popular: true },

    // Afrique
    XOF: { code: 'XOF', name: 'West African CFA Franc', symbol: 'FCFA', locale: 'fr-FR', region: 'Africa', popular: true },
    XAF: { code: 'XAF', name: 'Central African CFA Franc', symbol: 'FCFA', locale: 'fr-FR', region: 'Africa', popular: true },
    ZAR: { code: 'ZAR', name: 'South African Rand', symbol: 'R', locale: 'en-ZA', region: 'Africa', popular: true },
    NGN: { code: 'NGN', name: 'Nigerian Naira', symbol: '₦', locale: 'en-NG', region: 'Africa', popular: true },
    GHS: { code: 'GHS', name: 'Ghanaian Cedi', symbol: '₵', locale: 'en-GH', region: 'Africa', popular: true },
    KES: { code: 'KES', name: 'Kenyan Shilling', symbol: 'KSh', locale: 'en-KE', region: 'Africa' },
    EGP: { code: 'EGP', name: 'Egyptian Pound', symbol: '£', locale: 'ar-EG', region: 'Africa' },
    MAD: { code: 'MAD', name: 'Moroccan Dirham', symbol: 'DH', locale: 'ar-MA', region: 'Africa' },
    TND: { code: 'TND', name: 'Tunisian Dinar', symbol: 'د.ت', locale: 'ar-TN', region: 'Africa' },
    ETB: { code: 'ETB', name: 'Ethiopian Birr', symbol: 'Br', locale: 'am-ET', region: 'Africa' },
    UGX: { code: 'UGX', name: 'Ugandan Shilling', symbol: 'USh', locale: 'en-UG', region: 'Africa' },
    TZS: { code: 'TZS', name: 'Tanzanian Shilling', symbol: 'TSh', locale: 'sw-TZ', region: 'Africa' },
    RWF: { code: 'RWF', name: 'Rwandan Franc', symbol: 'FRw', locale: 'rw-RW', region: 'Africa' },
    MZN: { code: 'MZN', name: 'Mozambican Metical', symbol: 'MT', locale: 'pt-MZ', region: 'Africa' },
    BWP: { code: 'BWP', name: 'Botswana Pula', symbol: 'P', locale: 'en-BW', region: 'Africa' },
    SZL: { code: 'SZL', name: 'Swazi Lilangeni', symbol: 'L', locale: 'en-SZ', region: 'Africa' },
    LSL: { code: 'LSL', name: 'Lesotho Loti', symbol: 'L', locale: 'en-LS', region: 'Africa' },
    NAD: { code: 'NAD', name: 'Namibian Dollar', symbol: 'N$', locale: 'en-NA', region: 'Africa' },
    ZMW: { code: 'ZMW', name: 'Zambian Kwacha', symbol: 'ZK', locale: 'en-ZM', region: 'Africa' },
    ZWL: { code: 'ZWL', name: 'Zimbabwean Dollar', symbol: 'Z$', locale: 'en-ZW', region: 'Africa' },
    AOA: { code: 'AOA', name: 'Angolan Kwanza', symbol: 'Kz', locale: 'pt-AO', region: 'Africa' },
    DZD: { code: 'DZD', name: 'Algerian Dinar', symbol: 'د.ج', locale: 'ar-DZ', region: 'Africa' },
    LYD: { code: 'LYD', name: 'Libyan Dinar', symbol: 'ل.د', locale: 'ar-LY', region: 'Africa' },

    // Asie
    INR: { code: 'INR', name: 'Indian Rupee', symbol: '₹', locale: 'hi-IN', region: 'Asia', popular: true },
    KRW: { code: 'KRW', name: 'South Korean Won', symbol: '₩', locale: 'ko-KR', region: 'Asia' },
    SGD: { code: 'SGD', name: 'Singapore Dollar', symbol: 'S$', locale: 'en-SG', region: 'Asia' },
    HKD: { code: 'HKD', name: 'Hong Kong Dollar', symbol: 'HK$', locale: 'zh-HK', region: 'Asia' },
    THB: { code: 'THB', name: 'Thai Baht', symbol: '฿', locale: 'th-TH', region: 'Asia' },
    MYR: { code: 'MYR', name: 'Malaysian Ringgit', symbol: 'RM', locale: 'ms-MY', region: 'Asia' },
    IDR: { code: 'IDR', name: 'Indonesian Rupiah', symbol: 'Rp', locale: 'id-ID', region: 'Asia' },
    PHP: { code: 'PHP', name: 'Philippine Peso', symbol: '₱', locale: 'en-PH', region: 'Asia' },
    VND: { code: 'VND', name: 'Vietnamese Dong', symbol: '₫', locale: 'vi-VN', region: 'Asia' },
    PKR: { code: 'PKR', name: 'Pakistani Rupee', symbol: '₨', locale: 'ur-PK', region: 'Asia' },
    BDT: { code: 'BDT', name: 'Bangladeshi Taka', symbol: '৳', locale: 'bn-BD', region: 'Asia' },
    LKR: { code: 'LKR', name: 'Sri Lankan Rupee', symbol: '₨', locale: 'si-LK', region: 'Asia' },
    NPR: { code: 'NPR', name: 'Nepalese Rupee', symbol: '₨', locale: 'ne-NP', region: 'Asia' },
    MVR: { code: 'MVR', name: 'Maldivian Rufiyaa', symbol: '.ރ', locale: 'dv-MV', region: 'Asia' },

    // Moyen-Orient
    AED: { code: 'AED', name: 'UAE Dirham', symbol: 'د.إ', locale: 'ar-AE', region: 'Middle East' },
    SAR: { code: 'SAR', name: 'Saudi Riyal', symbol: '﷼', locale: 'ar-SA', region: 'Middle East' },
    QAR: { code: 'QAR', name: 'Qatari Riyal', symbol: '﷼', locale: 'ar-QA', region: 'Middle East' },
    KWD: { code: 'KWD', name: 'Kuwaiti Dinar', symbol: 'د.ك', locale: 'ar-KW', region: 'Middle East' },
    BHD: { code: 'BHD', name: 'Bahraini Dinar', symbol: '.د.ب', locale: 'ar-BH', region: 'Middle East' },
    OMR: { code: 'OMR', name: 'Omani Rial', symbol: '﷼', locale: 'ar-OM', region: 'Middle East' },
    JOD: { code: 'JOD', name: 'Jordanian Dinar', symbol: 'د.ا', locale: 'ar-JO', region: 'Middle East' },
    LBP: { code: 'LBP', name: 'Lebanese Pound', symbol: 'ل.ل', locale: 'ar-LB', region: 'Middle East' },
    ILS: { code: 'ILS', name: 'Israeli Shekel', symbol: '₪', locale: 'he-IL', region: 'Middle East' },
    IRR: { code: 'IRR', name: 'Iranian Rial', symbol: '﷼', locale: 'fa-IR', region: 'Middle East' },
    IQD: { code: 'IQD', name: 'Iraqi Dinar', symbol: 'ع.د', locale: 'ar-IQ', region: 'Middle East' },

    // Europe
    NOK: { code: 'NOK', name: 'Norwegian Krone', symbol: 'kr', locale: 'nb-NO', region: 'Europe' },
    SEK: { code: 'SEK', name: 'Swedish Krona', symbol: 'kr', locale: 'sv-SE', region: 'Europe' },
    DKK: { code: 'DKK', name: 'Danish Krone', symbol: 'kr', locale: 'da-DK', region: 'Europe' },
    PLN: { code: 'PLN', name: 'Polish Zloty', symbol: 'zł', locale: 'pl-PL', region: 'Europe' },
    CZK: { code: 'CZK', name: 'Czech Koruna', symbol: 'Kč', locale: 'cs-CZ', region: 'Europe' },
    HUF: { code: 'HUF', name: 'Hungarian Forint', symbol: 'Ft', locale: 'hu-HU', region: 'Europe' },
    RON: { code: 'RON', name: 'Romanian Leu', symbol: 'lei', locale: 'ro-RO', region: 'Europe' },
    BGN: { code: 'BGN', name: 'Bulgarian Lev', symbol: 'лв', locale: 'bg-BG', region: 'Europe' },
    HRK: { code: 'HRK', name: 'Croatian Kuna', symbol: 'kn', locale: 'hr-HR', region: 'Europe' },
    RSD: { code: 'RSD', name: 'Serbian Dinar', symbol: 'Дин.', locale: 'sr-RS', region: 'Europe' },
    RUB: { code: 'RUB', name: 'Russian Ruble', symbol: '₽', locale: 'ru-RU', region: 'Europe' },
    UAH: { code: 'UAH', name: 'Ukrainian Hryvnia', symbol: '₴', locale: 'uk-UA', region: 'Europe' },
    TRY: { code: 'TRY', name: 'Turkish Lira', symbol: '₺', locale: 'tr-TR', region: 'Europe' },

    // Amérique du Sud
    BRL: { code: 'BRL', name: 'Brazilian Real', symbol: 'R$', locale: 'pt-BR', region: 'South America', popular: true },
    ARS: { code: 'ARS', name: 'Argentine Peso', symbol: '$', locale: 'es-AR', region: 'South America' },
    CLP: { code: 'CLP', name: 'Chilean Peso', symbol: '$', locale: 'es-CL', region: 'South America' },
    COP: { code: 'COP', name: 'Colombian Peso', symbol: '$', locale: 'es-CO', region: 'South America' },
    PEN: { code: 'PEN', name: 'Peruvian Sol', symbol: 'S/', locale: 'es-PE', region: 'South America' },
    UYU: { code: 'UYU', name: 'Uruguayan Peso', symbol: '$U', locale: 'es-UY', region: 'South America' },
    BOB: { code: 'BOB', name: 'Bolivian Boliviano', symbol: '$b', locale: 'es-BO', region: 'South America' },
    PYG: { code: 'PYG', name: 'Paraguayan Guarani', symbol: 'Gs', locale: 'es-PY', region: 'South America' },
    GYD: { code: 'GYD', name: 'Guyanese Dollar', symbol: 'G$', locale: 'en-GY', region: 'South America' },
    SRD: { code: 'SRD', name: 'Surinamese Dollar', symbol: '$', locale: 'nl-SR', region: 'South America' },

    // Amérique Centrale et Caraïbes
    MXN: { code: 'MXN', name: 'Mexican Peso', symbol: '$', locale: 'es-MX', region: 'North America', popular: true },
    GTQ: { code: 'GTQ', name: 'Guatemalan Quetzal', symbol: 'Q', locale: 'es-GT', region: 'Central America' },
    HNL: { code: 'HNL', name: 'Honduran Lempira', symbol: 'L', locale: 'es-HN', region: 'Central America' },
    CRC: { code: 'CRC', name: 'Costa Rican Colon', symbol: '₡', locale: 'es-CR', region: 'Central America' },
    NIO: { code: 'NIO', name: 'Nicaraguan Córdoba', symbol: 'C$', locale: 'es-NI', region: 'Central America' },
    PAB: { code: 'PAB', name: 'Panamanian Balboa', symbol: 'B/.', locale: 'es-PA', region: 'Central America' },
    JMD: { code: 'JMD', name: 'Jamaican Dollar', symbol: 'J$', locale: 'en-JM', region: 'Caribbean' },
    TTD: { code: 'TTD', name: 'Trinidad & Tobago Dollar', symbol: 'TT$', locale: 'en-TT', region: 'Caribbean' },
    BBD: { code: 'BBD', name: 'Barbadian Dollar', symbol: 'Bds$', locale: 'en-BB', region: 'Caribbean' },
    XCD: { code: 'XCD', name: 'East Caribbean Dollar', symbol: 'EC$', locale: 'en-LC', region: 'Caribbean' },

    // Océanie
    NZD: { code: 'NZD', name: 'New Zealand Dollar', symbol: 'NZ$', locale: 'en-NZ', region: 'Oceania' },
    FJD: { code: 'FJD', name: 'Fijian Dollar', symbol: 'FJ$', locale: 'en-FJ', region: 'Oceania' },
    PGK: { code: 'PGK', name: 'Papua New Guinean Kina', symbol: 'K', locale: 'en-PG', region: 'Oceania' },
    TOP: { code: 'TOP', name: 'Tongan Paʻanga', symbol: 'T$', locale: 'to-TO', region: 'Oceania' },
    WST: { code: 'WST', name: 'Samoan Tala', symbol: 'WS$', locale: 'sm-WS', region: 'Oceania' },
    VUV: { code: 'VUV', name: 'Vanuatu Vatu', symbol: 'VT', locale: 'bi-VU', region: 'Oceania' },
};

// Devises populaires pour accès rapide
export const POPULAR_CURRENCIES = [
    'USD', 'EUR', 'GBP', 'JPY', 'CNY', 'CHF', 'CAD', 'AUD',
    'XOF', 'XAF', 'NGN', 'GHS', 'ZAR', 'INR', 'BRL', 'MXN'
];

// Groupement par régions
export const CURRENCIES_BY_REGION = {
    'Popular': POPULAR_CURRENCIES.map(code => ({ code, ...GLOBAL_CURRENCIES[code] })),
    'Africa': Object.entries(GLOBAL_CURRENCIES)
        .filter(([_, currency]) => currency.region === 'Africa')
        .map(([code, currency]) => ({ code, ...currency })),
    'Asia': Object.entries(GLOBAL_CURRENCIES)
        .filter(([_, currency]) => currency.region === 'Asia')
        .map(([code, currency]) => ({ code, ...currency })),
    'Europe': Object.entries(GLOBAL_CURRENCIES)
        .filter(([_, currency]) => currency.region === 'Europe')
        .map(([code, currency]) => ({ code, ...currency })),
    'Middle East': Object.entries(GLOBAL_CURRENCIES)
        .filter(([_, currency]) => currency.region === 'Middle East')
        .map(([code, currency]) => ({ code, ...currency })),
    'North America': Object.entries(GLOBAL_CURRENCIES)
        .filter(([_, currency]) => currency.region === 'North America')
        .map(([code, currency]) => ({ code, ...currency })),
    'South America': Object.entries(GLOBAL_CURRENCIES)
        .filter(([_, currency]) => currency.region === 'South America')
        .map(([code, currency]) => ({ code, ...currency })),
    'Central America': Object.entries(GLOBAL_CURRENCIES)
        .filter(([_, currency]) => currency.region === 'Central America')
        .map(([code, currency]) => ({ code, ...currency })),
    'Caribbean': Object.entries(GLOBAL_CURRENCIES)
        .filter(([_, currency]) => currency.region === 'Caribbean')
        .map(([code, currency]) => ({ code, ...currency })),
    'Oceania': Object.entries(GLOBAL_CURRENCIES)
        .filter(([_, currency]) => currency.region === 'Oceania')
        .map(([code, currency]) => ({ code, ...currency })),
};

/**
 * Formate un prix avec support global des devises
 */
export function formatPrice(amount, currencyCode, options = {}) {
    const {
        locale,
        isRental = false,
        showDecimals = false,
        ...intlOptions
    } = options;

    const currency = GLOBAL_CURRENCIES[currencyCode];
    if (!currency) {
        console.warn(`Devise ${currencyCode} non supportée`);
        return `${amount} ${currencyCode}`;
    }

    const formatLocale = locale || currency.locale;

    try {
        const formatOptions = {
            style: 'currency',
            currency: currencyCode,
            minimumFractionDigits: showDecimals ? 2 : 0,
            maximumFractionDigits: showDecimals ? 2 : 0,
            ...intlOptions,
        };

        const formatter = new Intl.NumberFormat(formatLocale, formatOptions);
        const formattedAmount = formatter.format(amount);

        return isRental ? `${formattedAmount} / mois` : formattedAmount;
    } catch (error) {
        // Fallback avec symbole personnalisé
        const formattedAmount = amount.toLocaleString(formatLocale);
        const result = `${currency.symbol}${formattedAmount}`;
        return isRental ? `${result} / mois` : result;
    }
}

/**
 * Recherche de devises par nom, code, symbole ou région
 */
export function searchCurrencies(query) {
    const searchTerm = query.toLowerCase().trim();
    if (!searchTerm) return [];

    return Object.entries(GLOBAL_CURRENCIES)
        .filter(([code, currency]) =>
            code.toLowerCase().includes(searchTerm) ||
            currency.name.toLowerCase().includes(searchTerm) ||
            currency.symbol.toLowerCase().includes(searchTerm) ||
            currency.region.toLowerCase().includes(searchTerm)
        )
        .map(([code, currency]) => ({ code, ...currency }))
        .sort((a, b) => {
            // Priorité : exact match code > popular > alphabetical
            if (a.code.toLowerCase() === searchTerm) return -1;
            if (b.code.toLowerCase() === searchTerm) return 1;
            if (a.popular && !b.popular) return -1;
            if (b.popular && !a.popular) return 1;
            return a.name.localeCompare(b.name);
        });
}

/**
 * Obtient les informations d'une devise
 */
export function getCurrencyInfo(currencyCode) {
    return GLOBAL_CURRENCIES[currencyCode] || null;
}

/**
 * Obtient la liste complète des devises
 */
export function getAllCurrencies() {
    return Object.entries(GLOBAL_CURRENCIES)
        .map(([code, currency]) => ({ code, ...currency }))
        .sort((a, b) => {
            if (a.popular && !b.popular) return -1;
            if (b.popular && !a.popular) return 1;
            return a.name.localeCompare(b.name);
        });
}

/**
 * Validation d'une devise
 */
export function isValidCurrency(currencyCode) {
    return GLOBAL_CURRENCIES.hasOwnProperty(currencyCode);
}

/**
 * Détection intelligente de la devise par défaut
 */
export function getDefaultCurrency(locale = 'fr-FR', country = null) {
    const currencyByLocale = {
        // Afrique francophone
        'fr-CI': 'XOF', 'fr-SN': 'XOF', 'fr-ML': 'XOF', 'fr-BF': 'XOF',
        'fr-NE': 'XOF', 'fr-TG': 'XOF', 'fr-BJ': 'XOF',
        // Afrique centrale francophone
        'fr-CM': 'XAF', 'fr-GA': 'XAF', 'fr-CF': 'XAF', 'fr-TD': 'XAF',
        'fr-CG': 'XAF', 'fr-GQ': 'XAF',
        // Autres pays africains
        'en-GH': 'GHS', 'en-NG': 'NGN', 'en-ZA': 'ZAR', 'en-KE': 'KES',
        'ar-MA': 'MAD', 'ar-TN': 'TND', 'ar-EG': 'EGP',
        // Majors
        'en-US': 'USD', 'en-GB': 'GBP', 'fr-FR': 'EUR', 'de-DE': 'EUR',
        'ja-JP': 'JPY', 'zh-CN': 'CNY', 'hi-IN': 'INR',
        'pt-BR': 'BRL', 'es-MX': 'MXN', 'en-CA': 'CAD', 'en-AU': 'AUD',
    };

    // Détection par pays si fourni
    if (country) {
        const countryToCurrency = {
            'Togo': 'XOF', 'Côte d\'Ivoire': 'XOF', 'Sénégal': 'XOF',
            'Mali': 'XOF', 'Burkina Faso': 'XOF', 'Niger': 'XOF', 'Bénin': 'XOF',
            'Cameroun': 'XAF', 'Gabon': 'XAF', 'Tchad': 'XAF',
            'Ghana': 'GHS', 'Nigeria': 'NGN', 'Kenya': 'KES',
            'Afrique du Sud': 'ZAR', 'Maroc': 'MAD', 'Tunisie': 'TND',
            'États-Unis': 'USD', 'France': 'EUR', 'Royaume-Uni': 'GBP',
            'Japon': 'JPY', 'Chine': 'CNY', 'Inde': 'INR',
            'Brésil': 'BRL', 'Canada': 'CAD', 'Australie': 'AUD',
        };

        if (countryToCurrency[country]) {
            return countryToCurrency[country];
        }
    }

    return currencyByLocale[locale] || 'XOF'; // XOF par défaut pour l'Afrique de l'Ouest
}