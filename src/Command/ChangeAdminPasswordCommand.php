<?php

namespace App\Command;

use App\Repository\AdminRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ChangeAdminPasswordCommand extends Command
{
    private $passwordEncoder;
    private $em;
    private $repository;

    public function __construct(EntityManagerInterface $entityManager,
                                UserPasswordEncoderInterface $passwordEncoder,
                                AdminRepository $repository)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->repository = $repository;
        $this->em = $entityManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('user:password')
            ->setDescription('Change password')
            ->addArgument('email', InputArgument::REQUIRED, 'Email - must be unique')
            ->addArgument('password', InputArgument::REQUIRED, 'Password - use any symbols')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');

        $user = $this->repository->findOneBy([
            'email' => $email
        ]);

        if (null === $user) {
            throw new NotFoundHttpException('bad email');
        }

        $password = $this->passwordEncoder->encodePassword($user, $password);
        $user->setPassword($password);

        $this->em->persist($user);

        try {
            $this->em->flush();
        } catch (\Exception $e) {
            $output->writeln(sprintf('Exception code: %s, Message: $s, trace: %s',
                $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
        }

        $io->success('Admin password updated!');

        return 0;
    }
}
