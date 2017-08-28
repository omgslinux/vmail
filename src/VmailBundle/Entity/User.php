<?php

namespace VmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\BooleanType;
use VmailBundle\Entity\Domain;
use VmailBundle\Entity\Traits\ActivableEntityTrait;
use VmailBundle\Entity\Traits\UserInterfaceEntityTrait;
use VmailBundle\Entity\Alias;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * User
 *
 * @ORM\Entity(repositoryClass="VmailBundle\Repository\UserRepository")
 * @ORM\Table(name="user", uniqueConstraints={@UniqueConstraint(name="name_unique", columns={"user", "domain_id"})})
 */
class User implements UserInterface
{
    Use ActivableEntityTrait, UserInterfaceEntityTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="user", type="string", length=32)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255)
     */
    private $password;

    /**
     * @var domain
     *
     * @ORM\ManyToOne(targetEntity="Domain", inversedBy="users")
     */
    private $domain;

    private $domainName;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $quota=0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_admin", type="boolean")
     */
    private $admin=false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_list", type="boolean")
     */
    private $list=false;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Autoreply", mappedBy="user")
     */
    private $replys;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Alias", mappedBy="aliasname")
     */
    private $aliasnames;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Alias", mappedBy="addressname", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $addressnames;

    /**
     * @var boolean
     *
     */
    private $sendEmail;


    public function __construct()
    {
        $this->replys=new ArrayCollection();
        $this->aliasnames=new ArrayCollection();
        $this->addresses=new ArrayCollection();
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
     * Set password
     *
     * @param string $password
     *
     * @return users
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
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
    public function getReplys()
    {
        return $this->replys;
    }

    /**
     * Add reply
     *
     * @param Autoreply $reply
     *
     * @return User
     */
    public function addReply(Autoreply $reply)
    {
        $this->replys->add($reply);
        $reply->setReply($this);

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
        $aliasname->setAliasName($this);

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

    public function __toString()
    {
        return $this->getName();
    }
}
