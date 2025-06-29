<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

#[AsEntityListener(event: 'postUpdate', entity: User::class)]
class UserDocumentUploadListener
{
    private $mailer;
    private $twig;

    public function __construct(MailerInterface $mailer, Environment $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    public function postUpdate(User $user, PostUpdateEventArgs $event): void
    {
        $changeSet = $event->getEntityManager()->getUnitOfWork()->getEntityChangeSet($user);

        $identityDocsChanged = (
            isset($changeSet['identityCard']) ||
            isset($changeSet['selfieWithId'])
        ) && !$user->isIdentityVerified();

        $financialDocsChanged = (
            isset($changeSet['incomeProof']) ||
            isset($changeSet['ownershipProof'])
        ) && !$user->isFinancialDocsVerified();

        if ($identityDocsChanged || $financialDocsChanged) {
            $this->notifyAdminOfNewDocuments($user, $identityDocsChanged, $financialDocsChanged);
        }
    }

    private function notifyAdminOfNewDocuments(User $user, bool $identityDocsChanged, bool $financialDocsChanged): void
    {
        $subject = 'Nouveaux documents à vérifier pour l\'utilisateur ' . $user->getEmail();
        $body = $this->twig->render('emails/admin_document_notification.html.twig', [
            'user' => $user,
            'identityDocsChanged' => $identityDocsChanged,
            'financialDocsChanged' => $financialDocsChanged,
        ]);

        $email = (new Email())
            ->from('no-reply@toutlux.com')
            ->to('admin@toutlux.com') // Replace with actual admin email
            ->subject($subject)
            ->html($body);

        $this->mailer->send($email);
    }
}
