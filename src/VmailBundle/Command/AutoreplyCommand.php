<?php

namespace VmailBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use VmailBundle\Utils\AutoreplyMail;

class AutoreplyCommand extends ContainerAwareCommand
{

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
        $bodyfile=trim(file_get_contents('php://STDIN'));
        $body=file_get_contents($bodyfile);

        $output->writeln("Sender: ${sender}, recipient: ${recipient}. Body: " . $body);
        $now=new \DateTime();
        syslog(
            LOG_INFO,
            "Autoreply INFO (PROCESANDO): Sender: $sender, recipient: $recipient, tamaño: "
                .strlen($body)." hora entrada: ". $now->format('d/m/Y H:i:s')
        );
        $autoreply = $this->getContainer()->get('VmailBundle\Utils\AutoreplyMail');
        $autoreply->deliverReply($sender, $recipient, $body);
        syslog(
            LOG_DEBUG,
            "Autoreply INFO (SUCCESS): Sender: $sender, recipient: $recipient, hora autoreply: ".
                $now->format('d/m/Y H:i:s')
        );
    }
}
