<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\UserProfile;
use App\Entity\Property;
use App\Entity\PropertyImage;
use App\Entity\Document;
use App\Entity\Message;
use App\Entity\Notification;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;

class AppFixtures extends Fixture
{
    private $faker;

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        // Create admin user
        $admin = $this->createAdmin($manager);

        // Create regular users
        $users = [];
        for ($i = 0; $i < 20; $i++) {
            $users[] = $this->createUser($manager, $i);
        }

        // Create properties
        $properties = [];
        foreach ($users as $index => $user) {
            if ($index % 3 === 0) { // Every third user has properties
                for ($j = 0; $j < rand(1, 3); $j++) {
                    $properties[] = $this->createProperty($manager, $user);
                }
            }
        }

        // Create messages between users
        for ($i = 0; $i < 30; $i++) {
            $this->createMessage(
                $manager,
                $users[array_rand($users)],
                $users[array_rand($users)],
                $properties[array_rand($properties)] ?? null
            );
        }

        // Create documents for some users
        foreach ($users as $index => $user) {
            if ($index % 2 === 0) {
                $this->createDocuments($manager, $user);
            }
        }

        $manager->flush();
    }

    private function createAdmin(ObjectManager $manager): User
    {
        $admin = new User();
        $admin->setEmail('admin@toutlux.com');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']);
        $admin->setIsVerified(true);
        $admin->setTrustScore(5.0);

        $profile = new UserProfile();
        $profile->setFirstName('Admin');
        $profile->setLastName('TOUTLUX');
        $profile->setPhoneNumber('0123456789');
        $profile->setPersonalInfoValidated(true);
        $profile->setIdentityValidated(true);
        $profile->setFinancialValidated(true);
        $profile->setTermsAccepted(true);

        $admin->setProfile($profile);
        $manager->persist($admin);

        return $admin;
    }

    private function createUser(ObjectManager $manager, int $index): User
    {
        $user = new User();
        $user->setEmail($this->faker->email);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified($this->faker->boolean(80)); // 80% verified

        $profile = new UserProfile();
        $profile->setFirstName($this->faker->firstName);
        $profile->setLastName($this->faker->lastName);
        $profile->setPhoneNumber($this->faker->phoneNumber);

        // Random profile completion
        if ($this->faker->boolean(70)) {
            $profile->setPersonalInfoValidated(true);
        }
        if ($this->faker->boolean(50)) {
            $profile->setIdentityValidated(true);
        }
        if ($this->faker->boolean(40)) {
            $profile->setFinancialValidated(true);
        }
        if ($this->faker->boolean(90)) {
            $profile->setTermsAccepted(true);
        }

        $user->setProfile($profile);

        // Calculate trust score based on profile completion
        $score = 0;
        if ($user->isVerified()) $score += 0.5;
        if ($profile->isPersonalInfoValidated()) $score += 1.0;
        if ($profile->isIdentityValidated()) $score += 1.5;
        if ($profile->isFinancialValidated()) $score += 1.5;
        if ($profile->isTermsAccepted()) $score += 0.5;
        $user->setTrustScore($score);

        $manager->persist($user);

        // Create welcome notification
        $notification = Notification::createWelcomeNotification($user);
        $manager->persist($notification);

        return $user;
    }

    private function createProperty(ObjectManager $manager, User $owner): Property
    {
        $property = new Property();
        $property->setTitle($this->faker->catchPhrase . ' - ' . $this->faker->city);
        $property->setDescription($this->faker->paragraphs(3, true));
        $property->setType($this->faker->randomElement([Property::TYPE_SALE, Property::TYPE_RENT]));
        $property->setPrice($property->getType() === Property::TYPE_SALE ?
            $this->faker->numberBetween(100000, 1000000) :
            $this->faker->numberBetween(500, 3000)
        );
        $property->setSurface($this->faker->numberBetween(20, 300));
        $property->setRooms($this->faker->numberBetween(1, 10));
        $property->setBedrooms($this->faker->numberBetween(0, 5));
        $property->setAddress($this->faker->streetAddress);
        $property->setCity($this->faker->city);
        $property->setZipCode($this->faker->postcode);
        $property->setLatitude($this->faker->latitude(43, 49));
        $property->setLongitude($this->faker->longitude(-5, 10));
        $property->setFeatures([
            'parking' => $this->faker->boolean,
            'elevator' => $this->faker->boolean,
            'terrace' => $this->faker->boolean,
            'garden' => $this->faker->boolean,
            'pool' => $this->faker->boolean,
        ]);
        $property->setStatus(Property::STATUS_AVAILABLE);
        $property->setOwner($owner);
        $property->incrementViewCount();

        $manager->persist($property);

        // Add images
        for ($i = 0; $i < rand(1, 5); $i++) {
            $image = new PropertyImage();
            $image->setImageName('property_' . $property->getId() . '_' . $i . '.jpg');
            $image->setPosition($i);
            $image->setIsMain($i === 0);
            $image->setProperty($property);
            $manager->persist($image);
        }

        return $property;
    }

    private function createMessage(ObjectManager $manager, User $sender, User $recipient, ?Property $property): void
    {
        if ($sender === $recipient) {
            return; // Don't send messages to self
        }

        $message = new Message();
        $message->setSender($sender);
        $message->setRecipient($recipient);
        $message->setSubject($property ?
            'Question about: ' . $property->getTitle() :
            $this->faker->sentence
        );
        $message->setContent($this->faker->paragraphs(2, true));
        $message->setProperty($property);
        $message->setStatus($this->faker->randomElement([
            Message::STATUS_PENDING,
            Message::STATUS_APPROVED,
            Message::STATUS_APPROVED,
            Message::STATUS_APPROVED, // More approved than pending
        ]));
        $message->setIsRead($this->faker->boolean(60));

        $manager->persist($message);
    }

    private function createDocuments(ObjectManager $manager, User $user): void
    {
        $documentTypes = [
            Document::TYPE_IDENTITY,
            Document::TYPE_SELFIE,
            Document::TYPE_FINANCIAL
        ];

        foreach ($documentTypes as $type) {
            if ($this->faker->boolean(60)) { // 60% chance of having each document
                $document = new Document();
                $document->setUser($user);
                $document->setType($type);
                $document->setFileName('document_' . $user->getId() . '_' . $type . '.pdf');
                $document->setFileSize($this->faker->numberBetween(100000, 5000000));
                $document->setStatus($this->faker->randomElement([
                    Document::STATUS_PENDING,
                    Document::STATUS_APPROVED,
                    Document::STATUS_APPROVED,
                    Document::STATUS_APPROVED, // More approved
                ]));

                if ($document->getStatus() === Document::STATUS_REJECTED) {
                    $document->setRejectionReason($this->faker->randomElement([
                        'Document is not readable',
                        'Invalid document type',
                        'Document expired',
                        'Poor quality image'
                    ]));
                }

                $manager->persist($document);
            }
        }
    }
}
