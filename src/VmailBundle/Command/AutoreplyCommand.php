<?php

namespace VmailBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use VmailBundle\Utils\DeliverMail;
use Doctrine\ORM\EntityManager;

class AutoreplyCommand extends ContainerAwareCommand
{
    protected $body;

    protected function configure()
    {
        $this
            ->setName('vmail:autoreply')
            ->setDescription('Manages autoreply')
            ->addArgument('sender', InputArgument::OPTIONAL, 'Postfix sender')
            ->addArgument('recipient', InputArgument::OPTIONAL, 'Postfix recipient')
            ->addOption('option', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sender = $input->getArgument('sender');
        $recipient= $input->getArgument('recipient');
        $this->body=file_get_contents('php://STDIN');


        $output->writeln("Sender: ${sender}, recipient: ${recipient}. Body: " . $this->body);
        $d=new DeliverMail();
        $d->deliverMail($sender, $recipient, $this->body);
    }

}
