<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;
use App\Repository\ConfigRepository as CR;
use Doctrine\ORM\EntityManagerInterface as EM;
use App\Entity\Config;
use Twig\Environment as TW;

#[AsCommand(
    name: 'vmail:conffiles:courier',
    description: 'Writes courier config files from database parameters',
)]
class CourierConfCommand extends Command
{
    protected $body;

    private $CR;
    private $TW;

    public function __construct(CR $CR, TW $TW)
    {
        $this->CR = $CR;
        $this->TW = $TW;

        parent::__construct();
    }
    protected function configure()
    {
        $this
            ->addOption(
                'source',
                's',
                InputOption::VALUE_OPTIONAL,
                'Source directory',
                'templates/conffiles/courier/'
            )
            ->addOption('destination', 'd', InputOption::VALUE_OPTIONAL, 'Destination directory', '/etc/courier/')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $source = $input->getOption('source');
        $destination = $input->getOption('destination');

        $dbname=$this->CR->param('dbname');
        $dbuser=$this->CR->param('user');
        $dbhost=$this->CR->param('host');
        $dbpass=$this->CR->param('password');
        $dbport=$this->CR->param('port');
        $virtual_mailbox_base=$this->CR->findOneBy(
            [
                'name' => 'virtual_mailbox_base'
            ]
        )->getValue();
        $stat=stat($virtual_mailbox_base);
        $uid=$stat['uid'];
        $gid=$stat['gid'];

        $output->writeln("Source: {$source}, destination: {$destination}");
        $sourcefiles = array_slice(scandir($source), 2);

        foreach ($sourcefiles as $sourcefile) {
            $destfile=substr($sourcefile, 0, -strlen('.twig'));
            $output->writeln("Procesando $sourcefile -> $destfile");
            file_put_contents(
                $destination . '/' . $destfile,
                $this->TW->render(
                    'conffiles/courier/' . $sourcefile,
                    [
                        'dbname' => $dbname,
                        'dbuser' => $dbuser,
                        'dbhost' => $dbhost,
                        'dbpass' => $dbpass,
                        'dbport' => $dbport,
                        'UID' => $uid,
                        'GID' => $gid,
                        //'enctype' => strtoupper($c->getParameter('vmail.algorithm')),
                        'enctype' => strtoupper('sha512'),
                    'virtual_mailbox_base'=> $virtual_mailbox_base,
                    ]
                )
            );
            $output->writeln("\n");

            return Command::SUCCESS;
        }
    }
}
