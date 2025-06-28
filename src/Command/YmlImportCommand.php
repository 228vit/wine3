<?php

namespace App\Command;

use App\Repository\AdminRepository;
use App\Utils\Slugger;
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
    private $uploadsPath;

    public function __construct(EntityManagerInterface $entityManager,
                                ImportYmlRepository $importYmlRepository,
                                string $localUploadsDirectory)
    {
        $this->importYmlRepository = $importYmlRepository;
        $this->em = $entityManager;
        $this->uploadsPath = $localUploadsDirectory;

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
        $offset = intval($input->getOption('offset'));
        $offset = empty($offset) ? 0 : $offset;
        $limit = $input->getOption('limit');
        $limit = empty($limit) ? 10 : $limit;
        $finishStep = $offset + $limit - 1; // 0+10
//        die("l: $limit o: $offset");

        $importYml = $this->importYmlRepository->find($id);

        if (!$importYml) {
            $io->error('Wrong ID supported');
            return Command::FAILURE;
        }

//        $io->success("ID: $id, Offset: $offset, Limit: $limit");

        // grab YML to local PATH, to avoid requery
        if (0 === $offset) {
            $ymlContent = file_get_contents($importYml->getUrl());
            $storedYmlPath = sprintf('%s%s.yml',
                $this->uploadsPath . DIRECTORY_SEPARATOR . 'yml' . DIRECTORY_SEPARATOR,
                Slugger::urlSlug($importYml->getSupplier()->getName().'-at-'.date('Y-m-d'))
            );

            $res = file_put_contents($storedYmlPath, $ymlContent);

            if (false === $res) {
                $io->success('YML file cannot be saved');
                return Command::FAILURE;
            }
            $importYml->setSavedYmlPath($storedYmlPath);
            $this->em->persist($importYml);
            $this->em->flush();
        }

        $countries = json_decode($importYml->getCountriesMapping(), true);
        $regions = json_decode($importYml->getRegionsMapping(), true);
        $appellations = json_decode($importYml->getAppellationsMapping(), true);
        $vendors = json_decode($importYml->getVendorsMapping(), true); // "Gaja" => "140"

        // do import
        $data = simplexml_load_file($importYml->getSavedYmlPath() ? $importYml->getSavedYmlPath() : $importYml->getUrl());

        $totalOffers = count($data->shop->offers->offer);
        $currentRow = 0;

        foreach ($data->shop->offers->offer as $row) {
            $io->writeln("curr: $currentRow, finish: $finishStep, offset: $offset");

            if ($currentRow < $offset) {
                ++$currentRow;
                continue;
            }
            if ($currentRow >= $finishStep) {
                $io->writeln('break');
                break;
            }

            $currentRow++;
            continue;

            $offerId = strval($row->attributes()->id);
            $isActive = boolval($row->attributes()->available);
            $price = floatval($row->price);
            $name = html_entity_decode(strval($row->name), ENT_QUOTES);
            $barcode = isset($row->barcode) ? strval($row->barcode) : null;

            $io->writeln("id: $offerId, name: $name, price: $price");
        }

        return Command::SUCCESS;

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
