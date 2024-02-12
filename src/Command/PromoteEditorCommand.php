<?php

namespace App\Command;

use App\Entity\Admin;
use App\Repository\AdminRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PromoteEditorCommand extends Command
{
    private $passwordEncoder;
    private $em;
    private $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('admin:password')
            ->setDescription('Change Admin password')
            ->addArgument('email', InputArgument::REQUIRED, 'Email - must be unique')
            ->addArgument('password', InputArgument::REQUIRED, 'Password - use any symbols')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        /** @var AdminRepository $repo */
        $repo = $this->em->getRepository(Admin::class);

        /** @var Admin $admin */
        $admin = $repo->findOneBy(['email' => $email]);

        if (!$admin) {
            throw new \Exception('Wrong email');
        }

        $admin->setIsEditor(true);
        $this->em->persist($admin);

        try {
            $this->em->flush();
        } catch (\Exception $e) {
            $output->writeln(sprintf('Exception code: %s, Message: $s, trace: %s',
                $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
        }

        $io->success('Admin ' . $admin->getEmail() . ' been promoted as superadmin!');

        return 1;
    }
}
