<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Utils\DeliverMail;
use App\Utils\PassEncoder as PE;
use App\Entity\User;
use App\Entity\Domain;
use App\Entity\Config;
use App\Repository\ConfigRepository as CR;
use App\Repository\DomainRepository as DR;
use App\Repository\UserRepository as UR;
use Doctrine\ORM\EntityManagerInterface as EM;

class SetupCommand extends Command
{
    protected static $defaultName = 'vmail:setup';
    protected static $defaultDescription = 'Initializes default values';

    protected $body;
    private $CR;
    private $DR;
    private $UR;
    private $PE;


    public function __construct(CR $CR, DR $DR, UR $UR, PE $PE)
    {
        $this->CR = $CR;
        $this->DR = $DR;
        $this->UR = $UR;
        $this->PE = $PE;
        parent::__construct();
    }

    protected function configure(): void
    {
        /*$this
            ->setName('vmail:setup')
            ->setDescription('Initializes default values')
        ; */
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->CR->rawsql('TRUNCATE config');
        $domain=$this->DR->find(0);

        if (empty($domain)) {
            $domain=new Domain();
            $domain->setName('default')->setActive(0);
            $this->DR->add($domain, true);
            $this->DR->rawsql('UPDATE domain SET id=0 WHERE name="default"');
            $domain=$this->DR->findOneBy(['name' => 'default']);
        }

        $user=$this->UR->findOneByName('admin');

        $plainPassword='vmailadmin';
        $encodedPassword = $this->PE->encodePassword($plainPassword);
        if (empty($user)) {
            $user=new User();
            $output->writeln("Domain: id-> ". $domain->getId() .", name: " . $domain->getName() . ", active: " . ($domain->isActive()?'True':'False').PHP_EOL);
            $user->setDomain($domain)
            ->setActive(false)
            ->setName('admin')
            ->setFullName('Vmail Admin')
            ->setPassword($encodedPassword)
            ->setList(false)
            ;
            $this->UR->add($user, true);
            $user=$this->UR->findOneByName('admin');
        } else {
            $user->setDomain($domain)
            ->setActive(false)
            ->setName('admin')
            ->setFullName('Vmail Admin')
            ->setPassword($encodedPassword)
            ->setList(false)
            ;
            $this->UR->add($user, true);
            //$em->persist($user);
            //$em->flush();
        }

        $output->writeln("User: id-> " . $user->getId() .", username: " . $user->getEmail() . ", Full name: " . $user->getFullName() . ", Password: $plainPassword, list: " . ($user->isList()?'true':'false'). ", active: " . ($user->isActive()?'true':'false'));

        $configs=
        [
          [
            'name' => 'virtual_mailbox_base',
            'value' => '/var/lib/vmail',
            'description' => 'Valor raíz de los buzones virtuales'
          ],
          [
            'name' => 'welcome_subject',
            'value' => 'Bienvenido',
            'description' => 'Asunto del correo de bienvenida'
          ],
          [
            'name' => 'welcome_body',
            'value' => 'Este es un correo de bienvenida para crear el buzón',
            'description' => 'Cuerpo del mensaje de bienvenida'
          ],
          [
            'name' => 'autoreply_delay',
            'value' => '4',
            'description' => 'Horas que deben transcurrir para repetir la respuesta automática'
          ],
          [
            'name' => 'autoreply_subject',
            'value' => 'Respuesta automática de %s (NO RESPONDER)',
            'description' => 'Asunto del correo de respuesta automática'
          ]
        ];

        foreach ($configs as $key) {
            $config=new Config();
            $config->setValue($key['value'])
            ->setName($key['name'])
            ->setDescription($key['description']);
            $this->CR->add($config);
        }
        $this->CR->add($config, true);

        $output->writeln("Setup finished successfully".PHP_EOL);

        return Command::SUCCESS;
    }
}
