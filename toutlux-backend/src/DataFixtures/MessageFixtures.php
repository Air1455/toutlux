<?php

namespace App\DataFixtures;

use App\Entity\Message;
use App\Enum\MessageStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class MessageFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            PropertyFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $messageTemplates = [
            'Bonjour, je suis intéressé par votre bien. Est-il toujours disponible ?',
            'Cette propriété correspond exactement à ce que je recherche. Serait-il possible d\'organiser une visite ?',
            'J\'aimerais avoir plus d\'informations sur ce bien. Pouvez-vous me contacter ?',
            'Le prix est-il négociable ? J\'ai un budget de %d€.',
            'Quelles sont les charges mensuelles pour ce logement ?',
            'Y a-t-il des travaux à prévoir ?',
            'Le quartier est-il bien desservi par les transports en commun ?',
            'Acceptez-vous les animaux de compagnie ?',
            'Quelle est la date de disponibilité ?',
            'Pouvons-nous convenir d\'un rendez-vous pour visiter le bien ?'
        ];

        // Créer des conversations entre utilisateurs
        for ($i = 0; $i < 50; $i++) {
            // Sélectionner deux utilisateurs différents
            $senderIndex = $faker->numberBetween(0, 22);
            $recipientIndex = $faker->numberBetween(0, 22);

            while ($recipientIndex === $senderIndex) {
                $recipientIndex = $faker->numberBetween(0, 22);
            }

            $sender = $this->getReference(UserFixtures::USER_REFERENCE_PREFIX . $senderIndex);
            $recipient = $this->getReference(UserFixtures::USER_REFERENCE_PREFIX . $recipientIndex);

            // 70% des messages sont liés à une propriété
            $property = null;
            if ($faker->boolean(70)) {
                $propertyIndex = $faker->numberBetween(0, 99);
                $property = $this->getReference(PropertyFixtures::PROPERTY_REFERENCE_PREFIX . $propertyIndex);
            }

            $message = new Message();
            $message->setSender($sender);
            $message->setRecipient($recipient);
            $message->setProperty($property);

            // Contenu du message
            if ($property) {
                $template = $faker->randomElement($messageTemplates);
                if (strpos($template, '%d') !== false) {
                    $content = sprintf($template, $faker->numberBetween(100000, 1000000));
                } else {
                    $content = $template;
                }
                $message->setSubject('À propos de : ' . $property->getTitle());
            } else {
                $content = $faker->paragraphs(2, true);
                $message->setSubject($faker->sentence(6));
            }

            $message->setContent($content);

            // Status et modération
            if ($property) {
                // Les messages liés aux propriétés nécessitent une modération
                $message->setNeedsModeration(true);

                // 60% sont approuvés, 30% en attente, 10% rejetés
                $rand = $faker->numberBetween(1, 100);
                if ($rand <= 60) {
                    $message->setStatus(MessageStatus::APPROVED);
                    $message->setAdminValidated(true);
                    $message->setValidatedAt($faker->dateTimeBetween('-1 month', '-1 day'));

                    // 80% des messages approuvés sont lus
                    if ($faker->boolean(80)) {
                        $message->setIsRead(true);
                        $message->setReadAt($faker->dateTimeBetween($message->getValidatedAt(), 'now'));
                    }
                } elseif ($rand <= 90) {
                    $message->setStatus(MessageStatus::PENDING);
                } else {
                    $message->setStatus(MessageStatus::REJECTED);
                    $message->setValidatedAt($faker->dateTimeBetween('-1 month', '-1 day'));
                    $message->setModerationReason($faker->randomElement([
                        'Contenu inapproprié',
                        'Spam détecté',
                        'Informations de contact partagées',
                        'Langage offensant'
                    ]));
                }
            } else {
                // Messages directs sont automatiquement approuvés
                $message->setStatus(MessageStatus::APPROVED);
                $message->setNeedsModeration(false);

                // 70% sont lus
                if ($faker->boolean(70)) {
                    $message->setIsRead(true);
                    $message->setReadAt($faker->dateTimeBetween('-1 month', 'now'));
                }
            }

            // Dates
            $message->setCreatedAt($faker->dateTimeBetween('-3 months', 'now'));
            $message->setUpdatedAt($message->getCreatedAt());

            $manager->persist($message);

            // Créer des réponses pour certains messages
            if ($faker->boolean(40) && $message->getStatus() === MessageStatus::APPROVED) {
                $reply = new Message();
                $reply->setSender($recipient);
                $reply->setRecipient($sender);
                $reply->setProperty($property);
                $reply->setParentMessage($message);
                $reply->setSubject('Re: ' . $message->getSubject());

                $replyContent = $faker->randomElement([
                    'Oui, le bien est toujours disponible. Quand souhaiteriez-vous le visiter ?',
                    'Merci pour votre intérêt. Je vous propose une visite ce weekend.',
                    'Les charges sont de 150€ par mois, incluant eau et parties communes.',
                    'Aucun travaux n\'est à prévoir, tout a été refait récemment.',
                    'Le quartier est très bien desservi, métro à 5 minutes à pied.',
                    'Les animaux sont acceptés sous conditions.'
                ]);

                $reply->setContent($replyContent);
                $reply->setStatus(MessageStatus::APPROVED);
                $reply->setNeedsModeration(false);

                if ($faker->boolean(60)) {
                    $reply->setIsRead(true);
                    $reply->setReadAt($faker->dateTimeBetween($message->getCreatedAt(), 'now'));
                }

                $reply->setCreatedAt($faker->dateTimeBetween($message->getCreatedAt(), 'now'));
                $reply->setUpdatedAt($reply->getCreatedAt());

                $manager->persist($reply);
            }
        }

        $manager->flush();
    }
}
