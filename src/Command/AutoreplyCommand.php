<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Utils\AutoreplyMail;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'vmail:autoreply',
    description: 'Manages autoreply',
)]
class AutoreplyCommand extends Command
{

    protected function configure(): void
    {
        $this
            ->addArgument('sender', InputArgument::OPTIONAL, 'Postfix sender')
            ->addArgument('recipient', InputArgument::OPTIONAL, 'Postfix recipient')
            ->addOption('option', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sender = $input->getArgument('sender');
        $recipient= $input->getArgument('recipient');
        $bodyfile=trim(file_get_contents('php://STDIN'));
        $body=file_get_contents($bodyfile);

        $output->writeln("Sender: {$sender}, recipient: {$recipient}. Body: " . $body);
        $now=new \DateTime();
        syslog(
            LOG_INFO,
            "Autoreply INFO (PROCESANDO): Sender: $sender, recipient: $recipient, tamaño: "
                .strlen($body)." hora entrada: ". $now->format('d/m/Y H:i:s')
        );
        $autoreply = $this->getContainer()->get('App\Utils\AutoreplyMail');
        $autoreply->deliverReply($sender, $recipient, $body);
        syslog(
            LOG_DEBUG,
            "Autoreply INFO (SUCCESS): Sender: $sender, recipient: $recipient, hora autoreply: ".
                $now->format('d/m/Y H:i:s')
        );
    }
}
