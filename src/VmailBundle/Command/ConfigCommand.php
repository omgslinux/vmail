<?php

namespace VmailBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use VmailBundle\Entity\Config;
use Doctrine\ORM\EntityManager;

class ConfigCommand extends ContainerAwareCommand
{
    protected $body;

    protected function configure()
    {
        $this
            ->setName('vmail:config')
            ->setDescription('Manage database config parameters')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Just print all records')
            ->addArgument('name', InputArgument::OPTIONAL, 'Config name')
            ->addArgument('value', InputArgument::OPTIONAL, 'Set value for name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $all = $input->getOption('all');
        $name = $input->getArgument('name');
        $value = $input->getArgument('value');
        $c=$this->getContainer();
        $em = $c->get('doctrine.orm.entity_manager');

        $output->writeln("Arguments: all: $all, name: ${name}, value: ${value}");
        if ($all) {
            $configs=$em->getRepository(Config::class)->findAll();
            foreach ($configs as $key => $config) {
                $output->writeln("name: ".$config->getName().", value: ".$config->getValue().", description: ". $config->getDescription());
            }
        } else {
            $config=$em->getRepository(Config::class)->findOneByName($name);

            if (!$config) {
                return $output->writeln("Parameter $name not found. Exiting");
            }

            if (!empty($value)) {
                $config->setValue($value);
                $em->persist($config);
                $em->flush();
            }
            $output->writeln("$name: ".$config->getValue());
        }

    }

}
