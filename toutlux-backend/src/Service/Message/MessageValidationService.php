<?php

namespace App\Service\Message;

use App\Entity\Message;
use Psr\Log\LoggerInterface;

class MessageValidationService
{
    private const SPAM_KEYWORDS = [
        'viagra', 'casino', 'lottery', 'winner', 'congratulations',
        'bitcoin', 'crypto', 'forex', 'investment opportunity'
    ];

    private const SUSPICIOUS_PATTERNS = [
        '/\b(?:https?:\/\/|www\.)\S+/i' => 'contains_links',
        '/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i' => 'contains_email',
        '/\b(?:\+?\d{1,3}[-.\s]?)?\(?\d{1,4}\)?[-.\s]?\d{1,4}[-.\s]?\d{1,9}\b/' => 'contains_phone',
        '/\b\d{4,}\b/' => 'contains_long_numbers',
        '/(.)\1{4,}/' => 'repeated_characters'
    ];

    private const MAX_MESSAGE_LENGTH = 2000;
    private const MIN_MESSAGE_LENGTH = 10;

    public function __construct(
        private LoggerInterface $logger
    ) {}

    /**
     * Valider le contenu d'un message
     */
    public function validateMessage(string $content): array
    {
        $errors = [];
        $warnings = [];
        $flags = [];

        // Vérifier la longueur
        $length = mb_strlen($content);
        if ($length < self::MIN_MESSAGE_LENGTH) {
            $errors[] = sprintf('Le message doit contenir au moins %d caractères.', self::MIN_MESSAGE_LENGTH);
        }
        if ($length > self::MAX_MESSAGE_LENGTH) {
            $errors[] = sprintf('Le message ne doit pas dépasser %d caractères.', self::MAX_MESSAGE_LENGTH);
        }

        // Vérifier le spam
        $spamCheck = $this->checkForSpam($content);
        if ($spamCheck['is_spam']) {
            $warnings[] = 'Le message semble contenir du spam.';
            $flags = array_merge($flags, $spamCheck['reasons']);
        }

        // Vérifier les patterns suspects
        $suspiciousCheck = $this->checkSuspiciousPatterns($content);
        if (!empty($suspiciousCheck)) {
            $warnings = array_merge($warnings, $suspiciousCheck);
            $flags = array_merge($flags, array_keys($suspiciousCheck));
        }

        // Vérifier la langue (optionnel)
        $languageCheck = $this->checkLanguage($content);
        if ($languageCheck !== null && !in_array($languageCheck, ['fr', 'en'])) {
            $warnings[] = 'Le message semble être dans une langue non supportée.';
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'flags' => array_unique($flags),
            'requires_moderation' => !empty($warnings) || !empty($flags)
        ];
    }

    /**
     * Vérifier la présence de spam
     */
    private function checkForSpam(string $content): array
    {
        $lowerContent = mb_strtolower($content);
        $foundKeywords = [];

        foreach (self::SPAM_KEYWORDS as $keyword) {
            if (str_contains($lowerContent, $keyword)) {
                $foundKeywords[] = $keyword;
            }
        }

        // Vérifier les majuscules excessives
        $uppercaseRatio = $this->calculateUppercaseRatio($content);
        if ($uppercaseRatio > 0.5) {
            $foundKeywords[] = 'excessive_capitals';
        }

        return [
            'is_spam' => !empty($foundKeywords),
            'reasons' => $foundKeywords
        ];
    }

    /**
     * Vérifier les patterns suspects
     */
    private function checkSuspiciousPatterns(string $content): array
    {
        $warnings = [];

        foreach (self::SUSPICIOUS_PATTERNS as $pattern => $type) {
            if (preg_match($pattern, $content)) {
                switch ($type) {
                    case 'contains_links':
                        $warnings[$type] = 'Le message contient des liens.';
                        break;
                    case 'contains_email':
                        $warnings[$type] = 'Le message contient une adresse email.';
                        break;
                    case 'contains_phone':
                        $warnings[$type] = 'Le message contient un numéro de téléphone.';
                        break;
                    case 'contains_long_numbers':
                        $warnings[$type] = 'Le message contient de longs nombres.';
                        break;
                    case 'repeated_characters':
                        $warnings[$type] = 'Le message contient des caractères répétés excessivement.';
                        break;
                }
            }
        }

        return $warnings;
    }

    /**
     * Calculer le ratio de majuscules
     */
    private function calculateUppercaseRatio(string $content): float
    {
        $letters = preg_replace('/[^a-zA-Z]/', '', $content);
        if (strlen($letters) === 0) {
            return 0;
        }

        $uppercase = preg_replace('/[^A-Z]/', '', $letters);
        return strlen($uppercase) / strlen($letters);
    }

    /**
     * Détecter la langue (basique)
     */
    private function checkLanguage(string $content): ?string
    {
        // Méthode très basique - à améliorer avec une vraie détection
        $frenchWords = ['le', 'la', 'les', 'de', 'et', 'un', 'une', 'pour', 'dans', 'sur'];
        $englishWords = ['the', 'and', 'or', 'for', 'in', 'on', 'at', 'to', 'of', 'a'];

        $words = str_word_count(mb_strtolower($content), 1);
        $frenchCount = count(array_intersect($words, $frenchWords));
        $englishCount = count(array_intersect($words, $englishWords));

        if ($frenchCount > $englishCount * 2) {
            return 'fr';
        } elseif ($englishCount > $frenchCount * 2) {
            return 'en';
        }

        return null;
    }

    /**
     * Nettoyer le contenu d'un message
     */
    public function sanitizeContent(string $content): string
    {
        // Supprimer les espaces multiples
        $content = preg_replace('/\s+/', ' ', $content);

        // Supprimer les espaces en début et fin
        $content = trim($content);

        // Limiter les sauts de ligne
        $content = preg_replace('/\n{3,}/', "\n\n", $content);

        // Encoder les caractères HTML
        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

        return $content;
    }

    /**
     * Suggérer des corrections
     */
    public function suggestCorrections(string $content): array
    {
        $suggestions = [];

        // Si trop de majuscules
        $uppercaseRatio = $this->calculateUppercaseRatio($content);
        if ($uppercaseRatio > 0.5) {
            $suggestions[] = [
                'type' => 'excessive_capitals',
                'message' => 'Évitez d\'utiliser trop de majuscules.',
                'suggested' => ucfirst(mb_strtolower($content))
            ];
        }

        // Si contient des emails
        if (preg_match('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i', $content, $matches)) {
            $suggestions[] = [
                'type' => 'contains_email',
                'message' => 'Évitez de partager des adresses email directement. Utilisez la messagerie interne.',
                'suggested' => str_replace($matches[0], '[email masqué]', $content)
            ];
        }

        // Si contient des numéros de téléphone
        if (preg_match('/\b(?:\+?\d{1,3}[-.\s]?)?\(?\d{1,4}\)?[-.\s]?\d{1,4}[-.\s]?\d{1,9}\b/', $content, $matches)) {
            $suggestions[] = [
                'type' => 'contains_phone',
                'message' => 'Évitez de partager des numéros de téléphone directement.',
                'suggested' => str_replace($matches[0], '[numéro masqué]', $content)
            ];
        }

        return $suggestions;
    }

    /**
     * Analyser un message pour les statistiques
     */
    public function analyzeMessage(Message $message): array
    {
        $content = $message->getContent();
        $validation = $this->validateMessage($content);

        return [
            'length' => mb_strlen($content),
            'word_count' => str_word_count($content),
            'has_links' => preg_match('/\b(?:https?:\/\/|www\.)\S+/i', $content) === 1,
            'has_email' => preg_match('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i', $content) === 1,
            'has_phone' => preg_match('/\b(?:\+?\d{1,3}[-.\s]?)?\(?\d{1,4}\)?[-.\s]?\d{1,4}[-.\s]?\d{1,9}\b/', $content) === 1,
            'uppercase_ratio' => $this->calculateUppercaseRatio($content),
            'detected_language' => $this->checkLanguage($content),
            'validation_result' => $validation,
            'spam_score' => $this->calculateSpamScore($content)
        ];
    }

    /**
     * Calculer un score de spam (0-100)
     */
    private function calculateSpamScore(string $content): int
    {
        $score = 0;

        // Mots-clés spam
        $spamCheck = $this->checkForSpam($content);
        if ($spamCheck['is_spam']) {
            $score += count($spamCheck['reasons']) * 20;
        }

        // Patterns suspects
        $suspiciousCheck = $this->checkSuspiciousPatterns($content);
        $score += count($suspiciousCheck) * 10;

        // Majuscules excessives
        $uppercaseRatio = $this->calculateUppercaseRatio($content);
        if ($uppercaseRatio > 0.5) {
            $score += 30;
        } elseif ($uppercaseRatio > 0.3) {
            $score += 15;
        }

        // Longueur suspecte
        $length = mb_strlen($content);
        if ($length < 20 || $length > 1500) {
            $score += 10;
        }

        return min(100, $score);
    }
}
