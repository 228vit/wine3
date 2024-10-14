<?php

namespace App\Command;

use App\Entity\Vendor;
use App\Repository\VendorRepository;
use App\Utils\Slugger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class VendorSlugCommand extends Command
{
    protected static $defaultName = 'vendor:slug';
    protected static $defaultDescription = 'Add a short description for your command';

    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('vendor:slug')
            ->setDescription('Vendors slugify')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var VendorRepository $repo */
        $repo = $this->em->getRepository(Vendor::class);

        $arr = [];
        /** @var Vendor $row */
        foreach ($repo->findAll() as $row) {
            $io->writeln($row->getId() . ' ' . $row->getSlug());
            if (empty($row->getSlug())) {
                $row->setSlug(Slugger::urlSlug($row->getName()));
            }


            $this->em->persist($row);
        }

        $this->em->flush();

        $io->success('Done.');

        return Command::SUCCESS;
    }
}
