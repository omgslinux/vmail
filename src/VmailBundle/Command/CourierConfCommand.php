<?php

namespace VmailBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;

class CourierConfCommand extends ContainerAwareCommand
{
    protected $body;

    protected function configure()
    {
        $this
            ->setName('vmail:conffiles:courier')
            ->setDescription('Writes postfix config files from database parameters')
            ->addOption(
                'source',
                's',
                InputOption::VALUE_OPTIONAL,
                'Source directory',
                './src/VmailBundle/Resources/conffiles/courier/'
            )
            ->addOption('destination', 'd', InputOption::VALUE_OPTIONAL, 'Destination directory', '/etc/courier/')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $c=$this->getContainer();
        $source = $c->getParameter('vmail.conffiles') . '/postfix/vmail/';
        $source = $input->getOption('source');
        $destination = $input->getOption('destination');

        $em = $c->get('doctrine.orm.entity_manager');
        $params=$em->getConnection()->getParams();
        $dbname=$params['dbname'];
        $dbuser=$params['user'];
        $dbhost=$params['host'];
        $dbpass=$params['password'];
        $virtual_mailbox_base=$em->getRepository('VmailBundle:Config')->findOneBy(
            [
                'name' => 'virtual_mailbox_base'
            ]
        )->getValue();
        $stat=stat($virtual_mailbox_base);
        $uid=$stat['uid'];
        $gid=$stat['gid'];

        $output->writeln("Source: ${source}, destination: ${destination}");
        $sourcefiles = array_slice(scandir($source), 2);

        foreach ($sourcefiles as $sourcefile) {
            $destfile=substr($sourcefile, 0, -strlen('.twig'));
            $output->writeln("Procesando $sourcefile -> $destfile");
            file_put_contents(
                $destination . '/' . $destfile,
                $this->getContainer()->get('templating')->render(
                    '@conffiles/courier/' . $sourcefile,
                    [
                        'dbname' => $dbname,
                        'dbuser' => $dbuser,
                        'dbhost' => $dbhost,
                        'dbpass' => $dbpass,
                        'params' => $params,
                        'UID' => $uid,
                        'GID' => $gid,
                        'enctype' => strtoupper($c->getParameter('vmail.algorithm')),
                        'virtual_mailbox_base'=> $virtual_mailbox_base,
                    ]
                )
            );
            $output->writeln("\n");
        }
    }
}
