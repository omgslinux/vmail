<?php

namespace VmailBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use VmailBundle\Utils\DeliverMail;
use VmailBundle\Utils\PassEncoder;
use VmailBundle\Entity\User;
use VmailBundle\Entity\Domain;
use VmailBundle\Entity\Config;
use Doctrine\ORM\EntityManager;

class SetupCommand extends ContainerAwareCommand
{
    protected $body;
    private $EM;

    protected function configure()
    {
        $this
            ->setName('vmail:setup')
            ->setDescription('Initializes default values')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $c=$this->getContainer();
        $em=$this->EM = $c->get('doctrine.orm.entity_manager');
        $this->rawsql('TRUNCATE config');
        $domain=$em->getRepository(Domain::class)->find(0);

        if (empty($domain)) {
            $domain=new Domain();
            $domain->setName('default')->setActive(0);
            $em->persist($domain);
            $em->flush();
            $this->rawsql('UPDATE domain SET id=0 WHERE name="default"');
            $domain=$em->getRepository(Domain::class)->findOneBy(['name' => 'default']);
        }

        $user=$em->getRepository(User::class)->findOneByName('admin');

        $plainPassword='vmailadmin';
        //$encodedPassword = $encoder->encodePassword($plainPassword);
        if (empty($user)) {
            $user=new User();
            $output->writeln("Domain: id-> ". $domain->getId() .", name: " . $domain->getName() . ", active: " . ($domain->isActive()?'True':'False').PHP_EOL);
            $user->setDomain($domain)
            ->setActive(false)
            ->setName('admin')
            ->setFullName('Vmail Admin')
            ->setPassword(PassEncoder::encodePassword($plainPassword))
            ->setList(false)
            ;
            $em->persist($user);
            $em->flush();
            $user=$em->getRepository(User::class)->findOneByName('admin');
        } else {
            $user->setDomain($domain)
            ->setActive(false)
            ->setName('admin')
            ->setFullName('Vmail Admin')
            ->setPassword($encodedPassword)
            ->setList(false)
            ;
            $em->persist($user);
            $em->flush();
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
            $em->persist($config);
        }
        $em->flush();

        $output->writeln("Setup finished successfully".PHP_EOL);

    }

    private function rawsql($rawsql)
    {
        $conn=$this->em->getConnection();
        $num_rows_effected = $conn->exec($rawsql);
        $this->em->flush();
        return $num_rows_effected;
    }
}
