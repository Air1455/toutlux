<?php

// src/Service/CurrencyService.php
namespace App\Service;

class CurrencyService
{
    // Configuration complète des devises (synchronisée avec le frontend ET le validator)
    private const CURRENCY_CONFIG = [
        // Devises majeures
        'USD' => ['name' => 'US Dollar', 'symbol' => '$', 'region' => 'North America'],
        'EUR' => ['name' => 'Euro', 'symbol' => '€', 'region' => 'Europe'],
        'GBP' => ['name' => 'British Pound', 'symbol' => '£', 'region' => 'Europe'],
        'JPY' => ['name' => 'Japanese Yen', 'symbol' => '¥', 'region' => 'Asia'],
        'CNY' => ['name' => 'Chinese Yuan', 'symbol' => '¥', 'region' => 'Asia'],
        'CHF' => ['name' => 'Swiss Franc', 'symbol' => 'CHF', 'region' => 'Europe'],
        'CAD' => ['name' => 'Canadian Dollar', 'symbol' => 'C$', 'region' => 'North America'],
        'AUD' => ['name' => 'Australian Dollar', 'symbol' => 'A$', 'region' => 'Oceania'],

        // Afrique
        'XOF' => ['name' => 'West African CFA Franc', 'symbol' => 'FCFA', 'region' => 'Africa'],
        'XAF' => ['name' => 'Central African CFA Franc', 'symbol' => 'FCFA', 'region' => 'Africa'],
        'ZAR' => ['name' => 'South African Rand', 'symbol' => 'R', 'region' => 'Africa'],
        'NGN' => ['name' => 'Nigerian Naira', 'symbol' => '₦', 'region' => 'Africa'],
        'GHS' => ['name' => 'Ghanaian Cedi', 'symbol' => '₵', 'region' => 'Africa'],
        'KES' => ['name' => 'Kenyan Shilling', 'symbol' => 'KSh', 'region' => 'Africa'],
        'EGP' => ['name' => 'Egyptian Pound', 'symbol' => '£', 'region' => 'Africa'],
        'MAD' => ['name' => 'Moroccan Dirham', 'symbol' => 'DH', 'region' => 'Africa'],
        'TND' => ['name' => 'Tunisian Dinar', 'symbol' => 'د.ت', 'region' => 'Africa'],
        'ETB' => ['name' => 'Ethiopian Birr', 'symbol' => 'Br', 'region' => 'Africa'],
        'UGX' => ['name' => 'Ugandan Shilling', 'symbol' => 'USh', 'region' => 'Africa'],
        'TZS' => ['name' => 'Tanzanian Shilling', 'symbol' => 'TSh', 'region' => 'Africa'],
        'RWF' => ['name' => 'Rwandan Franc', 'symbol' => 'FRw', 'region' => 'Africa'],
        'MZN' => ['name' => 'Mozambican Metical', 'symbol' => 'MT', 'region' => 'Africa'],
        'BWP' => ['name' => 'Botswana Pula', 'symbol' => 'P', 'region' => 'Africa'],
        'SZL' => ['name' => 'Swazi Lilangeni', 'symbol' => 'L', 'region' => 'Africa'],
        'LSL' => ['name' => 'Lesotho Loti', 'symbol' => 'L', 'region' => 'Africa'],
        'NAD' => ['name' => 'Namibian Dollar', 'symbol' => 'N$', 'region' => 'Africa'],
        'ZMW' => ['name' => 'Zambian Kwacha', 'symbol' => 'ZK', 'region' => 'Africa'],
        'ZWL' => ['name' => 'Zimbabwean Dollar', 'symbol' => 'Z$', 'region' => 'Africa'],
        'AOA' => ['name' => 'Angolan Kwanza', 'symbol' => 'Kz', 'region' => 'Africa'],
        'DZD' => ['name' => 'Algerian Dinar', 'symbol' => 'د.ج', 'region' => 'Africa'],
        'LYD' => ['name' => 'Libyan Dinar', 'symbol' => 'ل.د', 'region' => 'Africa'],

        // Asie
        'INR' => ['name' => 'Indian Rupee', 'symbol' => '₹', 'region' => 'Asia'],
        'KRW' => ['name' => 'South Korean Won', 'symbol' => '₩', 'region' => 'Asia'],
        'SGD' => ['name' => 'Singapore Dollar', 'symbol' => 'S$', 'region' => 'Asia'],
        'HKD' => ['name' => 'Hong Kong Dollar', 'symbol' => 'HK$', 'region' => 'Asia'],
        'THB' => ['name' => 'Thai Baht', 'symbol' => '฿', 'region' => 'Asia'],
        'MYR' => ['name' => 'Malaysian Ringgit', 'symbol' => 'RM', 'region' => 'Asia'],
        'IDR' => ['name' => 'Indonesian Rupiah', 'symbol' => 'Rp', 'region' => 'Asia'],
        'PHP' => ['name' => 'Philippine Peso', 'symbol' => '₱', 'region' => 'Asia'],
        'VND' => ['name' => 'Vietnamese Dong', 'symbol' => '₫', 'region' => 'Asia'],
        'PKR' => ['name' => 'Pakistani Rupee', 'symbol' => '₨', 'region' => 'Asia'],
        'BDT' => ['name' => 'Bangladeshi Taka', 'symbol' => '৳', 'region' => 'Asia'],
        'LKR' => ['name' => 'Sri Lankan Rupee', 'symbol' => '₨', 'region' => 'Asia'],
        'NPR' => ['name' => 'Nepalese Rupee', 'symbol' => '₨', 'region' => 'Asia'],
        'MVR' => ['name' => 'Maldivian Rufiyaa', 'symbol' => '.ރ', 'region' => 'Asia'],

        // Moyen-Orient
        'AED' => ['name' => 'UAE Dirham', 'symbol' => 'د.إ', 'region' => 'Middle East'],
        'SAR' => ['name' => 'Saudi Riyal', 'symbol' => '﷼', 'region' => 'Middle East'],
        'QAR' => ['name' => 'Qatari Riyal', 'symbol' => '﷼', 'region' => 'Middle East'],
        'KWD' => ['name' => 'Kuwaiti Dinar', 'symbol' => 'د.ك', 'region' => 'Middle East'],
        'BHD' => ['name' => 'Bahraini Dinar', 'symbol' => '.د.ب', 'region' => 'Middle East'],
        'OMR' => ['name' => 'Omani Rial', 'symbol' => '﷼', 'region' => 'Middle East'],
        'JOD' => ['name' => 'Jordanian Dinar', 'symbol' => 'د.ا', 'region' => 'Middle East'],
        'LBP' => ['name' => 'Lebanese Pound', 'symbol' => 'ل.ل', 'region' => 'Middle East'],
        'ILS' => ['name' => 'Israeli Shekel', 'symbol' => '₪', 'region' => 'Middle East'],
        'IRR' => ['name' => 'Iranian Rial', 'symbol' => '﷼', 'region' => 'Middle East'],
        'IQD' => ['name' => 'Iraqi Dinar', 'symbol' => 'ع.د', 'region' => 'Middle East'],

        // Europe
        'NOK' => ['name' => 'Norwegian Krone', 'symbol' => 'kr', 'region' => 'Europe'],
        'SEK' => ['name' => 'Swedish Krona', 'symbol' => 'kr', 'region' => 'Europe'],
        'DKK' => ['name' => 'Danish Krone', 'symbol' => 'kr', 'region' => 'Europe'],
        'PLN' => ['name' => 'Polish Zloty', 'symbol' => 'zł', 'region' => 'Europe'],
        'CZK' => ['name' => 'Czech Koruna', 'symbol' => 'Kč', 'region' => 'Europe'],
        'HUF' => ['name' => 'Hungarian Forint', 'symbol' => 'Ft', 'region' => 'Europe'],
        'RON' => ['name' => 'Romanian Leu', 'symbol' => 'lei', 'region' => 'Europe'],
        'BGN' => ['name' => 'Bulgarian Lev', 'symbol' => 'лв', 'region' => 'Europe'],
        'HRK' => ['name' => 'Croatian Kuna', 'symbol' => 'kn', 'region' => 'Europe'],
        'RSD' => ['name' => 'Serbian Dinar', 'symbol' => 'Дин.', 'region' => 'Europe'],
        'RUB' => ['name' => 'Russian Ruble', 'symbol' => '₽', 'region' => 'Europe'],
        'UAH' => ['name' => 'Ukrainian Hryvnia', 'symbol' => '₴', 'region' => 'Europe'],
        'TRY' => ['name' => 'Turkish Lira', 'symbol' => '₺', 'region' => 'Europe'],

        // Amérique du Sud
        'BRL' => ['name' => 'Brazilian Real', 'symbol' => 'R$', 'region' => 'South America'],
        'ARS' => ['name' => 'Argentine Peso', 'symbol' => '$', 'region' => 'South America'],
        'CLP' => ['name' => 'Chilean Peso', 'symbol' => '$', 'region' => 'South America'],
        'COP' => ['name' => 'Colombian Peso', 'symbol' => '$', 'region' => 'South America'],
        'PEN' => ['name' => 'Peruvian Sol', 'symbol' => 'S/', 'region' => 'South America'],
        'UYU' => ['name' => 'Uruguayan Peso', 'symbol' => '$U', 'region' => 'South America'],
        'BOB' => ['name' => 'Bolivian Boliviano', 'symbol' => '$b', 'region' => 'South America'],
        'PYG' => ['name' => 'Paraguayan Guarani', 'symbol' => 'Gs', 'region' => 'South America'],
        'GYD' => ['name' => 'Guyanese Dollar', 'symbol' => 'G$', 'region' => 'South America'],
        'SRD' => ['name' => 'Surinamese Dollar', 'symbol' => '$', 'region' => 'South America'],

        // Amérique Centrale et Caraïbes
        'MXN' => ['name' => 'Mexican Peso', 'symbol' => '$', 'region' => 'North America'],
        'GTQ' => ['name' => 'Guatemalan Quetzal', 'symbol' => 'Q', 'region' => 'Central America'],
        'HNL' => ['name' => 'Honduran Lempira', 'symbol' => 'L', 'region' => 'Central America'],
        'CRC' => ['name' => 'Costa Rican Colon', 'symbol' => '₡', 'region' => 'Central America'],
        'NIO' => ['name' => 'Nicaraguan Córdoba', 'symbol' => 'C$', 'region' => 'Central America'],
        'PAB' => ['name' => 'Panamanian Balboa', 'symbol' => 'B/.', 'region' => 'Central America'],
        'JMD' => ['name' => 'Jamaican Dollar', 'symbol' => 'J$', 'region' => 'Caribbean'],
        'TTD' => ['name' => 'Trinidad & Tobago Dollar', 'symbol' => 'TT$', 'region' => 'Caribbean'],
        'BBD' => ['name' => 'Barbadian Dollar', 'symbol' => 'Bds$', 'region' => 'Caribbean'],
        'XCD' => ['name' => 'East Caribbean Dollar', 'symbol' => 'EC$', 'region' => 'Caribbean'],

        // Océanie
        'NZD' => ['name' => 'New Zealand Dollar', 'symbol' => 'NZ$', 'region' => 'Oceania'],
        'FJD' => ['name' => 'Fijian Dollar', 'symbol' => 'FJ$', 'region' => 'Oceania'],
        'PGK' => ['name' => 'Papua New Guinean Kina', 'symbol' => 'K', 'region' => 'Oceania'],
        'TOP' => ['name' => 'Tongan Paʻanga', 'symbol' => 'T$', 'region' => 'Oceania'],
        'WST' => ['name' => 'Samoan Tala', 'symbol' => 'WS$', 'region' => 'Oceania'],
        'VUV' => ['name' => 'Vanuatu Vatu', 'symbol' => 'VT', 'region' => 'Oceania'],
    ];

    /**
     * Valide si une devise est supportée
     */
    public function isValidCurrency(string $currencyCode): bool
    {
        return isset(self::CURRENCY_CONFIG[strtoupper($currencyCode)]);
    }

    /**
     * Obtient les informations d'une devise
     */
    public function getCurrencyInfo(string $currencyCode): ?array
    {
        $normalized = strtoupper($currencyCode);
        return self::CURRENCY_CONFIG[$normalized] ?? null;
    }

    /**
     * Obtient toutes les devises supportées
     */
    public function getAllCurrencies(): array
    {
        return self::CURRENCY_CONFIG;
    }

    /**
     * Obtient la liste des codes de devises supportées (pour le validator)
     */
    public static function getSupportedCurrencyCodes(): array
    {
        return array_keys(self::CURRENCY_CONFIG);
    }

    /**
     * Obtient les devises par région
     */
    public function getCurrenciesByRegion(string $region): array
    {
        return array_filter(self::CURRENCY_CONFIG, function($currency) use ($region) {
            return $currency['region'] === $region;
        });
    }

    /**
     * Formate un prix selon la devise
     */
    public function formatPrice(int $amount, string $currencyCode, bool $isRental = false): string
    {
        $currencyInfo = $this->getCurrencyInfo($currencyCode);

        if (!$currencyInfo) {
            return $amount . ' ' . $currencyCode;
        }

        $symbol = $currencyInfo['symbol'];
        $formattedAmount = number_format($amount, 0, ',', ' ');

        // Gestion spéciale pour les francs CFA
        if (in_array($currencyCode, ['XOF', 'XAF'])) {
            $result = $formattedAmount . ' ' . $symbol;
        } else {
            $result = $symbol . $formattedAmount;
        }

        return $isRental ? $result . ' / mois' : $result;
    }

    /**
     * Normalise une devise (convertit symboles vers codes ISO)
     */
    public function normalizeCurrency(string $currency): string
    {
        $symbolMap = [
            '$' => 'USD',
            '€' => 'EUR',
            '£' => 'GBP',
            '₣' => 'XOF',
            'FCFA' => 'XOF',
            '₵' => 'GHS',
            '₦' => 'NGN',
            'DH' => 'MAD',
            'R' => 'ZAR',
        ];

        $trimmed = trim($currency);

        // Si c'est un symbole, on le convertit
        if (isset($symbolMap[$trimmed])) {
            return $symbolMap[$trimmed];
        }

        // Si c'est déjà un code ISO valide
        $uppercase = strtoupper($trimmed);
        if ($this->isValidCurrency($uppercase)) {
            return $uppercase;
        }

        // Fallback
        return 'XOF';
    }

    /**
     * Détecte la devise par défaut selon le pays
     */
    public function getDefaultCurrencyByCountry(string $country): string
    {
        $countryToCurrency = [
            'Togo' => 'XOF',
            'Côte d\'Ivoire' => 'XOF',
            'Sénégal' => 'XOF',
            'Mali' => 'XOF',
            'Burkina Faso' => 'XOF',
            'Niger' => 'XOF',
            'Bénin' => 'XOF',
            'Cameroun' => 'XAF',
            'Gabon' => 'XAF',
            'Tchad' => 'XAF',
            'République centrafricaine' => 'XAF',
            'République du Congo' => 'XAF',
            'Guinée équatoriale' => 'XAF',
            'Ghana' => 'GHS',
            'Nigeria' => 'NGN',
            'Kenya' => 'KES',
            'Afrique du Sud' => 'ZAR',
            'Maroc' => 'MAD',
            'Tunisie' => 'TND',
            'Égypte' => 'EGP',
            'Éthiopie' => 'ETB',
            'France' => 'EUR',
            'États-Unis' => 'USD',
            'Royaume-Uni' => 'GBP',
            'Canada' => 'CAD',
            'Australie' => 'AUD',
            'Brésil' => 'BRL',
            'Mexique' => 'MXN',
            'Inde' => 'INR',
            'Japon' => 'JPY',
            'Chine' => 'CNY',
            'Suisse' => 'CHF',
        ];

        return $countryToCurrency[$country] ?? 'XOF';
    }
}
