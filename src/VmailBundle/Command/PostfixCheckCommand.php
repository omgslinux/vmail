<?php

namespace VmailBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use VmailBundle\Utils\DeliverMail;
use Doctrine\ORM\EntityManager;

class PostfixCheckCommand extends ContainerAwareCommand
{
    protected $body;

    protected function configure()
    {
        $this
            ->setName('vmail:check:postfix')
            ->setDescription('Check postfix config files from database parameters')
            ->addOption('key', 'k', InputOption::VALUE_REQUIRED, 'Key to be checked')
            ->addOption('source', 's', InputOption::VALUE_OPTIONAL, 'Source directory', '/etc/postfix/vmail')
            ->addArgument('file', InputArgument::REQUIRED, 'Config file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $key = $input->getOption('key');
        $source = $input->getOption('source');
        $file = $input->getArgument('file');

        $output->writeln("Source: ${source}, file: ${file}");

        // Check paths
        $exists=false;
        clearstatcache();
        if (file_exists($file)) {
            $exists=$file;
        } else {
            $test=$file.'.cf';
            if (file_exists($test)) {
                $exists=$test;
            } else {
                $test='./mysql-' . $file.'.cf';
                if (file_exists($test)) {
                    $exists=$test;
                } else {
                    $test=$source.'/' . $file;
                    if (file_exists($test)) {
                        $exists=$test;
                    } else {
                        $test=$source.'/' . $file.'.cf';
                        if (file_exists($test)) {
                            $exists=$test;
                        } else {
                            $test=$source.'/mysql-' . $file.'.cf';
                            if (file_exists($test)) {
                                $exists=$test;
                            }
                        }
                    }
                }
            }
        }

        if ($exists) {
            $output->writeln("File $exists exists");
            $command="/usr/sbin/postmap -q $key mysql:$exists";
            $output->writeln("Command to run: $command");
            $output->writeln(exec($command));
        } else {
            $output->writeln("File $file is not valid");
        }

    }
}
