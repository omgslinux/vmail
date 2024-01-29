<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Domain;
use App\Entity\Traits\ActivableEntityTrait;
use App\Entity\Traits\UserInterfaceEntityTrait;
use App\Entity\Autoreply;
use App\Entity\Alias;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * User
 */
#[ORM\Table(name: 'user')]
#[UniqueConstraint(name: 'name_unique', columns: ['domain_id', 'user'])]
#[ORM\Entity(repositoryClass: 'App\Repository\UserRepository')]
#[UniqueEntity(fields: ['name', 'domain'], message: 'Ya existe ese usuario', errorPath: 'name')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use ActivableEntityTrait, UserInterfaceEntityTrait;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'user', type: 'string', length: 32)]
    private $name;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private $fullName;

    /**
     * @var domain
     */
    #[ORM\ManyToOne(targetEntity: 'Domain', inversedBy: 'users')]
    private $domain;

    private $domainName;

    /**
     * @var integer
     */
    #[ORM\Column(type: 'bigint')]
    private $quota=0;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'is_admin', type: 'boolean')]
    private $admin=false;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'is_list', type: 'boolean')]
    private $list=false;

    /**
     * @var Autoreply
     */
    #[ORM\OneToOne(targetEntity: 'Autoreply', mappedBy: 'user', cascade: ['persist', 'remove'])]
    private $reply;

    /**
     * @var ArrayCollection
     */
    #[ORM\OneToMany(targetEntity: 'Alias', mappedBy: 'aliasname', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private $aliasnames;

    /**
     * @var ArrayCollection
     */
    #[ORM\OneToMany(targetEntity: 'Alias', mappedBy: 'addressname', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private $addressnames;

    /**
     * @var boolean
     *
     */
    private $sendEmail = false;

    #[ORM\Column(nullable: true)]
    private ?array $certdata = null;


    public function __construct()
    {
        //$this->reply=new ArrayCollection();
        $this->aliasnames=new ArrayCollection();
        $this->addressnames=new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return users
     */
    public function setName($user)
    {
        $this->name = $user;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set fullName
     *
     * @param string $fullName
     *
     * @return users
     */
    public function setFullName($name)
    {
        $this->fullName = $name;

        return $this;
    }

    /**
     * Get fullName
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * Set domain
     *
     * @param Domain $domain
     *
     * @return users
     */
    public function setDomain(Domain $domain)
    {
        $this->domain = $domain;
        //$this->domainName=$domain->getName();
        $this->setDomainName($domain->getName());

        return $this;
    }

    /**
     * Get domain
     *
     * @return Domain
     */
    public function getDomain()
    {
        return $this->domain;
    }

    public function setDomainName($name)
    {
        $this->domainName=$name;

        return $this;
    }

    public function getDomainName()
    {
        //return $this->domainName;
        return $this->getDomain()->getName();
    }

    public function getEmail()
    {
        return $this->getName() . '@' . $this->getDomainName();
    }

    /**
     * Set quota
     *
     * @param integer $quota
     *
     * @return users
     */
    public function setQuota($quota)
    {
        $this->quota = $quota;

        return $this;
    }

    /**
     * Get quota
     *
     * @return integer
     */
    public function getQuota()
    {
        return $this->quota;
    }

    /**
     * Set admin
     *
     * @param string $admin
     *
     * @return users
     */
    public function setAdmin($admin)
    {
        $this->admin = $admin;

        return $this;
    }

    /**
     * Is admin
     *
     * @return boolean
     */
    public function isAdmin()
    {
        return $this->admin;
    }

    /**
     * Set list
     *
     * @param boolean $bool
     *
     * @return users
     */
    public function setList($bool)
    {
        $this->list = $bool;

        return $this;
    }

    /**
     * Is list
     *
     * @return boolean
     */
    public function isList()
    {
        return $this->list;
    }

    /**
     * Set sendEmail
     *
     * @return User
     */
    public function setSendEmail($value)
    {
        $this->sendEmail=$value;
        return $this;
    }

    /**
     * Get sendEmail
     *
     * @return boolean
     */
    public function getSendEmail()
    {
        return $this->sendEmail;
    }

    /**
     * Get replys
     *
     * @return ArrayCollection
     */
    public function getReply()
    {
        return $this->reply;
    }

    /**
     * Add reply
     *
     * @param Autoreply $reply
     *
     * @return User
     */
    public function setReply(Autoreply $reply)
    {
        $this->reply=$reply;
        //$reply->setReply($this);

        return $this;
    }

    /**
     * Remove reply
     *
     * @param Autoreply $reply
     *
     * @return User
     */
    public function removeReply(Autoreply $reply)
    {
        $this->replys->removeElement($reply);

        return $this;
    }

    /**
     * Get aliases
     *
     * @return ArrayCollection
     */
    public function getAliasnames()
    {
        return $this->aliasnames;
    }

    /**
     * Add alias
     *
     * @param Alias $alias
     *
     * @return User
     */
    public function addAliasname(Alias $alias)
    {
        $this->aliasnames->add($alias);
        $alias->setAliasName($this);

        return $this;
    }

    /**
     * Remove alias
     *
     * @param Alias $alias
     *
     * @return User
     */
    public function removeAliasname(Alias $alias)
    {
        $this->aliasnames->removeElement($alias);

        return $this;
    }

    /**
     * Get addresnames
     *
     * @return ArrayCollection
     */
    public function getAddressnames()
    {
        return $this->addressnames;
    }

    /**
     * Add addressname
     *
     * @param Alias $addressname
     *
     * @return User
     */
    public function addAddressname(Alias $alias)
    {
        $this->addressnames->add($alias);
        $alias->setAddressname($this);

        return $this;
    }

    /**
     * Remove addressname
     *
     * @param Alias $alias
     *
     * @return User
     */
    public function removeAddressname(Alias $alias)
    {
        $this->addressnames->removeElement($alias);

        return $this;
    }

    public function __toString(): string
    {
        if ($this->getDomain()->getId()===0) {
            return $this->getName();
        } else {
            return $this->getEmail();
        }
    }

    public function getCertdata(): ?array
    {
        return $this->certdata;
    }

    public function setCertdata(?array $certdata): static
    {
        $this->certdata = $certdata;

        return $this;
    }
}
