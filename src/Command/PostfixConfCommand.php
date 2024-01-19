<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
//use App\Utils\DeliverMail;
//use Doctrine\ORM\EntityManager;
use App\Repository\ConfigRepository as CR;
//use App\Entity\Config;
use Twig\Environment as TW;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'vmail:conffiles:postfix',
    description: 'Writes postfix config files from database parameters',
)]
class PostfixConfCommand extends Command
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

    protected function configure(): void
    {
        $this
            ->addOption(
                'source',
                's',
                InputOption::VALUE_OPTIONAL,
                'Source directory',
                'templates/conffiles/postfix/vmail/'
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //$c=$this->getContainer();
        //$source = $c->getParameter('vmail.conffiles') . '/postfix/vmail/';
        $source = $input->getOption('source');
        $destination = $input->getOption('destination');
        print getcwd();

        $dbname=$this->CR->param('dbname');
        $dbuser=$this->CR->param('user');
        $dbhost=$this->CR->param('host');
        $dbpass=$this->CR->param('password');
        $dbport=$this->CR->param('port');

        $output->writeln("Source: {$source}, destination: {$destination}");
        $sourcefiles = array_slice(scandir($source), 2);

        foreach ($sourcefiles as $sourcefile) {
            $destfile=substr($sourcefile, 0, -strlen('.twig'));
            $output->writeln("Procesando $sourcefile -> $destfile");
            file_put_contents(
                $destination . '/' . $destfile,
                $this->TW->render(
                    'conffiles/postfix/vmail/' . $sourcefile,
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
        return Command::SUCCESS;
    }
}
