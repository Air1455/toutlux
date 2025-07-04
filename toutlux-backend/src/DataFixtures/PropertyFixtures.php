<?php

namespace App\DataFixtures;

use App\Entity\Property;
use App\Entity\PropertyImage;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class PropertyFixtures extends Fixture implements DependentFixtureInterface
{
    public const PROPERTY_REFERENCE_PREFIX = 'property-';

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Liste des villes avec coordonnées GPS
        $cities = [
            ['name' => 'Paris', 'lat' => 48.8566, 'lng' => 2.3522, 'code' => '75001'],
            ['name' => 'Lyon', 'lat' => 45.7640, 'lng' => 4.8357, 'code' => '69001'],
            ['name' => 'Marseille', 'lat' => 43.2965, 'lng' => 5.3698, 'code' => '13001'],
            ['name' => 'Toulouse', 'lat' => 43.6047, 'lng' => 1.4442, 'code' => '31000'],
            ['name' => 'Nice', 'lat' => 43.7102, 'lng' => 7.2620, 'code' => '06000'],
            ['name' => 'Nantes', 'lat' => 47.2184, 'lng' => -1.5536, 'code' => '44000'],
            ['name' => 'Strasbourg', 'lat' => 48.5734, 'lng' => 7.7521, 'code' => '67000'],
            ['name' => 'Montpellier', 'lat' => 43.6108, 'lng' => 3.8767, 'code' => '34000'],
            ['name' => 'Bordeaux', 'lat' => 44.8378, 'lng' => -0.5792, 'code' => '33000'],
            ['name' => 'Lille', 'lat' => 50.6292, 'lng' => 3.0573, 'code' => '59000']
        ];

        $features = ['garage', 'parking', 'balcony', 'terrace', 'garden', 'pool', 'elevator', 'cellar', 'air_conditioning', 'heating', 'fireplace', 'alarm'];

        $propertyTypes = [
            'Appartement T2 moderne',
            'Appartement T3 avec vue',
            'Appartement T4 familial',
            'Studio étudiant',
            'Loft industriel',
            'Maison de ville',
            'Villa avec piscine',
            'Duplex lumineux',
            'Penthouse de luxe',
            'Maison de campagne'
        ];

        // Créer des propriétés pour chaque utilisateur vérifié
        for ($i = 0; $i < 100; $i++) {
            $property = new Property();

            // Sélectionner un propriétaire aléatoire
            $ownerIndex = $faker->numberBetween(0, 22);
            $owner = $this->getReference(UserFixtures::USER_REFERENCE_PREFIX . $ownerIndex);
            $property->setOwner($owner);

            // Informations de base
            $property->setTitle($faker->randomElement($propertyTypes) . ' - ' . $faker->words(3, true));
            $property->setDescription($faker->paragraphs(3, true));

            // Type et prix
            $type = $faker->randomElement(['sale', 'rent']);
            $property->setType($type);

            if ($type === 'sale') {
                $property->setPrice($faker->numberBetween(100000, 2000000));
            } else {
                $property->setPrice($faker->numberBetween(500, 5000));
            }

            // Caractéristiques
            $property->setSurface($faker->numberBetween(20, 300));
            $property->setRooms($faker->numberBetween(1, 8));
            $property->setBedrooms($faker->numberBetween(0, 5));
            $property->setBathrooms($faker->numberBetween(1, 3));

            // Localisation
            $city = $faker->randomElement($cities);
            $property->setAddress($faker->streetAddress);
            $property->setCity($city['name']);
            $property->setPostalCode($city['code']);
            $property->setLatitude($city['lat'] + $faker->randomFloat(4, -0.1, 0.1));
            $property->setLongitude($city['lng'] + $faker->randomFloat(4, -0.1, 0.1));

            // Features aléatoires
            $selectedFeatures = $faker->randomElements($features, $faker->numberBetween(3, 8));
            $property->setFeatures($selectedFeatures);

            // Statut
            $property->setAvailable($faker->boolean(85));
            $property->setVerified($faker->boolean(70));
            $property->setFeatured($faker->boolean(10));

            // Statistiques
            $property->setViewCount($faker->numberBetween(0, 1000));

            $manager->persist($property);

            // Ajouter des images
            $imageCount = $faker->numberBetween(3, 8);
            for ($j = 0; $j < $imageCount; $j++) {
                $image = new PropertyImage();
                $image->setProperty($property);
                $image->setImageName(sprintf('property_%d_image_%d.jpg', $i, $j));
                $image->setImageSize($faker->numberBetween(100000, 2000000));
                $image->setAlt($property->getTitle() . ' - Photo ' . ($j + 1));
                $image->setIsMain($j === 0);
                $image->setPosition($j);
                $image->setImageUrl(sprintf('https://picsum.photos/800/600?random=%d', $i * 10 + $j));

                $manager->persist($image);
            }

            $this->addReference(self::PROPERTY_REFERENCE_PREFIX . $i, $property);
        }

        $manager->flush();
    }
}
