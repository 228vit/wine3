<?php

namespace App\Command;

use App\Entity\Admin;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class CreateAdminCommand extends Command
{
    private $passwordEncoder;
    private $em;

    public function __construct(EntityManagerInterface $entityManager,
                                UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->em = $entityManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('admin:create')
            ->setDescription('Create admin')
            ->addArgument('email', InputArgument::REQUIRED, 'Email - must be unique')
            ->addArgument('password', InputArgument::REQUIRED, 'Password - use any symbols')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');

        $admin = new Admin();
        $admin->setEmail($email);
        $admin->setPassword($password);
        $admin->setIsSuperAdmin(false);
        $admin->setIsEditor(false);
        $password = $this->passwordEncoder->encodePassword($admin, $password);
        $admin->setPassword($password);

        $this->em->persist($admin);

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
