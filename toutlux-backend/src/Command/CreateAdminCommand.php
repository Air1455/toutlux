<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Créer un utilisateur administrateur',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private string $defaultEmail = '',
        private string $defaultPassword = ''
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Email de l\'administrateur')
            ->addArgument('password', InputArgument::OPTIONAL, 'Mot de passe')
            ->addOption('super-admin', null, InputOption::VALUE_NONE, 'Créer un super administrateur')
            ->addOption('firstName', null, InputOption::VALUE_REQUIRED, 'Prénom')
            ->addOption('lastName', null, InputOption::VALUE_REQUIRED, 'Nom')
            ->setHelp('Cette commande permet de créer un utilisateur administrateur');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        // Email
        $email = $input->getArgument('email') ?: $this->defaultEmail;
        if (!$email) {
            $question = new Question('Email de l\'administrateur: ');
            $question->setValidator(function ($email) {
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('Email invalide');
                }
                return $email;
            });
            $email = $helper->ask($input, $output, $question);
        }

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $io->error('Un utilisateur avec cet email existe déjà.');
            return Command::FAILURE;
        }

        // Mot de passe
        $password = $input->getArgument('password') ?: $this->defaultPassword;
        if (!$password) {
            $question = new Question('Mot de passe: ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $question->setValidator(function ($password) {
                if (empty($password) || strlen($password) < 8) {
                    throw new \RuntimeException('Le mot de passe doit contenir au moins 8 caractères');
                }
                return $password;
            });
            $password = $helper->ask($input, $output, $question);
        }

        // Prénom
        $firstName = $input->getOption('firstName');
        if (!$firstName) {
            $question = new Question('Prénom: ', 'Admin');
            $firstName = $helper->ask($input, $output, $question);
        }

        // Nom
        $lastName = $input->getOption('lastName');
        if (!$lastName) {
            $question = new Question('Nom: ', 'TOUTLUX');
            $lastName = $helper->ask($input, $output, $question);
        }

        // Créer l'utilisateur
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setIsEmailVerified(true);
        $user->setTermsAccepted(true);
        $user->setTermsAcceptedAt(new \DateTimeImmutable());

        // Définir les rôles
        if ($input->getOption('super-admin')) {
            $user->setRoles(['ROLE_USER', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN']);
            $io->note('Création d\'un super administrateur...');
        } else {
            $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
            $io->note('Création d\'un administrateur...');
        }

        // Valider l'entité
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $io->error('Erreurs de validation:');
            foreach ($errors as $error) {
                $io->error($error->getPropertyPath() . ': ' . $error->getMessage());
            }
            return Command::FAILURE;
        }

        // Sauvegarder
        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $io->success(sprintf(
                'Administrateur créé avec succès!%sEmail: %s%sNom: %s %s%sRôles: %s',
                PHP_EOL,
                $user->getEmail(),
                PHP_EOL,
                $user->getFirstName(),
                $user->getLastName(),
                PHP_EOL,
                implode(', ', $user->getRoles())
            ));

            $io->info('Vous pouvez maintenant vous connecter à l\'interface d\'administration à l\'adresse: /admin');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors de la création de l\'administrateur: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
