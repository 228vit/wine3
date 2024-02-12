<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class CreateUserCommand extends Command
{
    private $passwordEncoder;
    private $em;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->em = $entityManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('user:create')
            ->setDescription('Create user')
            ->addArgument('email', InputArgument::REQUIRED, 'Email - must be unique')
            ->addArgument('password', InputArgument::REQUIRED, 'Password - use any symbols')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($password);
        $password = $this->passwordEncoder->encodePassword($user, $password);
        $user->setPassword($password);

        $this->em->persist($user);

        try {
            $this->em->flush();
        } catch (\Exception $e) {
            $output->writeln(sprintf('Exception code: %s, Message: $s, trace: %s',
                $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
        }

        $io->success('New user created!');

        return 0;
    }
}
