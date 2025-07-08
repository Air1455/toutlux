<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const ADMIN_USER_REFERENCE = 'admin-user';
    public const USER_REFERENCE_PREFIX = 'user-';

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Créer un super admin
        $admin = new User();
        $admin->setEmail('admin@toutlux.com');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setFirstName('Admin');
        $admin->setLastName('TOUTLUX');
        $admin->setRoles([UserRole::SUPER_ADMIN->value]);
        $admin->setIsEmailVerified(true);
        $admin->setVerifiedAt(new \DateTime());
        $admin->setTermsAccepted(true);
        $admin->setTermsAcceptedAt(new \DateTimeImmutable());
        $admin->setTrustScore('5.00');
        $admin->setProfileCompleted(true);
        $admin->setIdentityVerified(true);
        $admin->setFinancialVerified(true);

        $manager->persist($admin);
        $this->addReference(self::ADMIN_USER_REFERENCE, $admin);

        // Créer des utilisateurs test avec différents niveaux de complétion
        $users = [
            // Utilisateur complètement vérifié
            [
                'email' => 'user.verified@test.com',
                'firstName' => 'Jean',
                'lastName' => 'Dupont',
                'password' => 'password123',
                'isEmailVerified' => true,
                'profileCompleted' => true,
                'identityVerified' => true,
                'financialVerified' => true,
                'trustScore' => '4.50'
            ],
            // Utilisateur avec profil incomplet
            [
                'email' => 'user.incomplete@test.com',
                'firstName' => 'Marie',
                'lastName' => 'Martin',
                'password' => 'password123',
                'isEmailVerified' => true,
                'profileCompleted' => false,
                'identityVerified' => false,
                'financialVerified' => false,
                'trustScore' => '1.00'
            ],
            // Utilisateur non vérifié
            [
                'email' => 'user.unverified@test.com',
                'firstName' => 'Pierre',
                'lastName' => 'Bernard',
                'password' => 'password123',
                'isEmailVerified' => false,
                'profileCompleted' => false,
                'identityVerified' => false,
                'financialVerified' => false,
                'trustScore' => '0.00'
            ]
        ];

        foreach ($users as $index => $userData) {
            $user = new User();
            $user->setEmail($userData['email']);
            $user->setPassword($this->passwordHasher->hashPassword($user, $userData['password']));
            $user->setFirstName($userData['firstName']);
            $user->setLastName($userData['lastName']);
            $user->setRoles([UserRole::USER->value]);
            $user->setIsEmailVerified($userData['isEmailVerified']);

            if ($userData['isEmailVerified']) {
                $user->setVerifiedAt($faker->dateTimeBetween('-1 year', '-1 month'));
            }

            $user->setProfileCompleted($userData['profileCompleted']);
            $user->setIdentityVerified($userData['identityVerified']);
            $user->setFinancialVerified($userData['financialVerified']);
            $user->setTrustScore($userData['trustScore']);

            if ($userData['profileCompleted']) {
                $user->setPhone($faker->phoneNumber);
                $user->setPhoneVerified(true);
                $user->setBirthDate($faker->dateTimeBetween('-60 years', '-18 years'));
                $user->setAddress($faker->streetAddress);
                $user->setCity($faker->city);
                $user->setPostalCode($faker->postcode);
                $user->setCountry('FR');
                $user->setBio($faker->text(200));
                $user->setAvatar('https://i.pravatar.cc/300?img=' . ($index + 1));
            }

            $user->setTermsAccepted(true);
            $user->setTermsAcceptedAt(new \DateTimeImmutable());

            $manager->persist($user);
            $this->addReference(self::USER_REFERENCE_PREFIX . $index, $user);
        }

        // Créer des utilisateurs aléatoires
        for ($i = 0; $i < 20; $i++) {
            $user = new User();
            $user->setEmail($faker->unique()->email);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
            $user->setFirstName($faker->firstName);
            $user->setLastName($faker->lastName);
            $user->setRoles([UserRole::USER->value]);

            // 80% des utilisateurs sont vérifiés
            if ($faker->boolean(80)) {
                $user->setIsEmailVerified(true);
                $user->setVerifiedAt($faker->dateTimeBetween('-1 year', '-1 week'));

                // 60% ont un profil complet
                if ($faker->boolean(60)) {
                    $user->setPhone($faker->phoneNumber);
                    $user->setPhoneVerified($faker->boolean(70));
                    $user->setBirthDate($faker->dateTimeBetween('-60 years', '-18 years'));
                    $user->setAddress($faker->streetAddress);
                    $user->setCity($faker->city);
                    $user->setPostalCode($faker->postcode);
                    $user->setCountry('FR');
                    $user->setBio($faker->text(200));
                    $user->setAvatar('https://i.pravatar.cc/300?img=' . ($i + 10));
                    $user->setProfileCompleted(true);

                    // 40% ont l'identité vérifiée
                    if ($faker->boolean(40)) {
                        $user->setIdentityVerified(true);

                        // 30% ont les documents financiers vérifiés
                        if ($faker->boolean(30)) {
                            $user->setFinancialVerified(true);
                            $user->setTrustScore('4.00');
                        } else {
                            $user->setTrustScore('2.50');
                        }
                    } else {
                        $user->setTrustScore('1.50');
                    }
                } else {
                    $user->setTrustScore('1.00');
                }
            } else {
                $user->setTrustScore('0.00');
            }

            $user->setTermsAccepted(true);
            $user->setTermsAcceptedAt(
                \DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-1 year', '-1 day'))
            );

            // Préférences de notification
            $user->setEmailNotificationsEnabled($faker->boolean(80));
            $user->setSmsNotificationsEnabled($faker->boolean(30));

            // Dernière connexion
            if ($faker->boolean(70)) {
                $user->setLastLoginAt($faker->dateTimeBetween('-1 month', 'now'));
            }

            $manager->persist($user);
            $this->addReference(self::USER_REFERENCE_PREFIX . ($i + 3), $user);
        }

        $manager->flush();
    }
}
