<?php

namespace App\Tests\Integration;

use App\Entity\Document;
use App\Entity\Message;
use App\Entity\User;
use App\Enum\DocumentStatus;
use App\Enum\DocumentType;
use App\Enum\MessageStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CoherenceTest extends KernelTestCase
{
    private $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testDocumentEnumCoherence(): void
    {
        $document = new Document();

        // Test DocumentType enum
        $document->setType(DocumentType::IDENTITY_CARD);
        $this->assertEquals('identity', $document->getType()->category());
        $this->assertTrue($document->getType()->isIdentityDocument());

        // Test DocumentStatus enum
        $document->setStatus(DocumentStatus::PENDING);
        $this->assertTrue($document->getStatus()->canBeValidated());
        $this->assertTrue($document->canBeDeleted());

        $document->setStatus(DocumentStatus::APPROVED);
        $this->assertFalse($document->getStatus()->canBeValidated());
        $this->assertFalse($document->canBeDeleted());
    }

    public function testMessageEnumCoherence(): void
    {
        $message = new Message();

        // Test MessageStatus enum
        $message->setStatus(MessageStatus::PENDING);
        $this->assertFalse($message->getStatus()->canBeSent());

        $message->setStatus(MessageStatus::APPROVED);
        $this->assertTrue($message->getStatus()->canBeSent());
    }

    public function testUserMethodsCoherence(): void
    {
        $user = new User();

        // Test email verification methods
        $this->assertFalse($user->isEmailVerified());
        $user->setEmailVerified(true);
        $this->assertTrue($user->isEmailVerified());
        $this->assertTrue($user->isVerified());

        // Test trust score calculation
        $user->calculateTrustScore();
        $this->assertIsString($user->getTrustScore());
        $this->assertGreaterThanOrEqual(0, (float)$user->getTrustScore());
        $this->assertLessThanOrEqual(5, (float)$user->getTrustScore());
    }

    public function testServiceInjection(): void
    {
        $container = static::getContainer();

        // Vérifier que tous les services critiques sont bien définis
        $services = [
            'App\Service\Document\DocumentValidationService',
            'App\Service\Message\MessageService',
            'App\Service\Email\NotificationEmailService',
            'App\Service\Document\TrustScoreCalculator',
            'App\Service\Auth\JWTService',
            'App\Service\Auth\GoogleAuthService',
            'App\Service\Upload\FileUploadService'
        ];

        foreach ($services as $service) {
            $this->assertTrue(
                $container->has($service),
                sprintf('Service %s not found in container', $service)
            );
        }
    }

    public function testRepositoryMethods(): void
    {
        $documentRepo = $this->entityManager->getRepository(Document::class);
        $messageRepo = $this->entityManager->getRepository(Message::class);

        // Test que les méthodes existent
        $this->assertTrue(
            method_exists($documentRepo, 'findByStatus'),
            'DocumentRepository::findByStatus() method missing'
        );

        $this->assertTrue(
            method_exists($messageRepo, 'findByStatus'),
            'MessageRepository::findByStatus() method missing'
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
