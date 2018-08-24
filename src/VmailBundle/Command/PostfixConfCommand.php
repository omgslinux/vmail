<?php

namespace VmailBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use VmailBundle\Utils\DeliverMail;
use Doctrine\ORM\EntityManager;

class PostfixConfCommand extends ContainerAwareCommand
{
    protected $body;

    protected function configure()
    {
        $this
            ->setName('vmail:conffiles:postfix')
            ->setDescription('Writes postfix config files from database parameters')
            ->addOption(
                'source',
                's',
                InputOption::VALUE_OPTIONAL,
                'Source directory',
                './src/VmailBundle/Resources/conffiles/postfix/vmail/'
            )
            ->addOption(
                'destination',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Destination directory',
                '/etc/postfix/vmail/'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $c=$this->getContainer();
        $source = $c->getParameter('vmail.conffiles') . '/postfix/vmail/';
        $source = $input->getOption('source');
        $destination = $input->getOption('destination');
        print getcwd();

        $em = $c->get('doctrine.orm.entity_manager');
        $params=$em->getConnection()->getParams();
        $dbname=$params['dbname'];
        $dbuser=$params['user'];
        $dbhost=$params['host'];
        $dbpass=$params['password'];

        $output->writeln("Source: ${source}, destination: ${destination}");
        $sourcefiles = array_slice(scandir($source), 2);

        foreach ($sourcefiles as $sourcefile) {
            $destfile=substr($sourcefile, 0, -strlen('.twig'));
            $output->writeln("Procesando $sourcefile -> $destfile");
            file_put_contents(
                $destination . '/' . $destfile,
                $this->getContainer()->get('templating')->render(
                    '@conffiles/postfix/vmail/' . $sourcefile,
                    [
                    'dbname' => $dbname,
                    'dbuser' => $dbuser,
                    'dbhost' => $dbhost,
                    'dbpass' => $dbpass
                    ]
                )
            );
            $output->writeln("\n");
        }
    }
}
