<?php

namespace App\Command;

use App\Repository\AdminRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\ArrayInput;
use App\Repository\ImportYmlRepository;

class YmlImportCommand extends Command
{
    private $em;
    private $importYmlRepository;

    public function __construct(EntityManagerInterface $entityManager,
                                ImportYmlRepository $importYmlRepository)
    {
        $this->importYmlRepository = $importYmlRepository;
        $this->em = $entityManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('import:yml')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'YML Import ID')
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset (default = 0)')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit (default = 50)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $id = $input->getOption('id');
        $offset = $input->getOption('offset');
        $offset = empty($offset) ? 0 : $offset;
        $limit = $input->getOption('limit');
        $limit = empty($limit) ? 50 : $limit;

        $importYml = $this->importYmlRepository->find($id);

        if (!$importYml) {
            $io->error('Wrong ID supported');
            return Command::FAILURE;
        }

        // do import
//        $io->success("ID: $id, Offset: $offset, Limit: $limit");
        $data = simplexml_load_file($importYml->getUrl());
        $totalOffers = count($data->shop->offers->offer);
        
        // grab YML to local PATH, to avoid requering
        if (0 === $offset) {

            $countries = json_decode($importYml->getCountriesMapping(), true);
            $regions = json_decode($importYml->getRegionsMapping(), true);
            $appellations = json_decode($importYml->getAppellationsMapping(), true);
            $vendors = json_decode($importYml->getVendorsMapping(), true); // "Gaja" => "140"

        }

        // call next batch import
        $offset = $offset + $limit;

//        if ($offset > 200) {
//            $io->info('Maximum reached');
//            return Command::SUCCESS;
//        }


        $script = sprintf('%s/bin/console import:yml --id=%s --offset=%s --limit=%s',
            $this->getParameter('kernel.project_dir'),
            $id,
            $offset,
            $limit
        );

        shell_exec(sprintf('%s > /dev/null 2>&1 &', $script));



        $command = $this->getApplication()->find('import:yml');
        $arguments = array(
            'command' => 'import:yml',
            '--id'  => $id,
            '--offset'  => $offset,
            '--limit'  => $limit,
        );

        $greetInput = new ArrayInput($arguments);
        $returnCode = $command->run($greetInput, $output);


        return Command::SUCCESS;
    }
}
