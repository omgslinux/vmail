<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\Config;
use App\Repository\ConfigRepository as CR;
use Doctrine\ORM\EntityManager;

class ConfigCommand extends Command
{
    protected $body;
    private $CR;

    protected static $defaultName = 'vmail:config';
    protected static $defaultDescription = 'Manage database config parameters';

    public function __construct(CR $CR)
    {
        $this->CR = $CR;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Just print all records')
            ->addArgument('name', InputArgument::OPTIONAL, 'Config name')
            ->addArgument('value', InputArgument::OPTIONAL, 'Set value for name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $all = $input->getOption('all');
        $name = $input->getArgument('name');
        $value = $input->getArgument('value');

        $output->writeln("Arguments: all: $all, name: {$name}, value: {$value}");
        if ($all) {
            //$configs=$em->getRepository(Config::class)->findAll();
            $configs=$this->CR->findAll();
            foreach ($configs as $key => $conf) {
                $output->writeln(
                    "name: ".$conf->getName().", value: ".$conf->getValue().", description: ". $conf->getDescription()
                );
            }
        } else {
            //$config=$em->getRepository(Config::class)->findOneByName($name);
            $config=$this->CR->findOneByName($name);

            if (!$config) {
                return $output->writeln("Parameter $name not found. Exiting");
            }

            if (!empty($value)) {
                $config->setValue($value);
                //$em->persist($config);
                //$em->flush();
                $this->CR->add($config, true);
            }
            $output->writeln("$name: ".$config->getValue());
        }

        return Command::SUCCESS;
    }
}
