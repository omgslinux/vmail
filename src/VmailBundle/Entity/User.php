<?php

namespace VmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\BooleanType;
use VmailBundle\Entity\Domain;
use VmailBundle\Entity\Traits\ActivableEntityTrait;
use VmailBundle\Entity\Traits\UserInterfaceEntityTrait;
use VmailBundle\Entity\Virtual;
use VmailBundle\Entity\Alias;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Users
 *
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="VmailBundle\Repository\UserRepository")
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
    private $user;

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
     * @ORM\Column(name="quota", type="integer")
     */
    private $quota=0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="admin", type="boolean")
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
     * @ORM\OneToMany(targetEntity="Alias", mappedBy="name")
     */
    private $aliases;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Virtual", mappedBy="name")
     */
    private $virtuals;

    /**
     * @var boolean
     *
     */
    private $sendEmail;


    public function __construct()
    {
        $this->replys=new ArrayCollection();
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
     * Set user
     *
     * @param string $user
     *
     * @return users
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return string
     */
    public function getUser()
    {
        return $this->user;
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

    public function getDomainName()
    {
        return $this->getDomain()->getName();
    }

    public function getEmail()
    {
        return $this->getUser() . '@' . $this->getDomainName();
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
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * Add alias
     *
     * @param Alias $alias
     *
     * @return User
     */
    public function addAlias(Alias $alias)
    {
        $this->aliases->add($alias);
        $alias->setName($this);

        return $this;
    }

    /**
     * Remove alias
     *
     * @param Alias $alias
     *
     * @return User
     */
    public function removeAlias(Alias $alias)
    {
        $this->aliases->removeElement($alias);

        return $this;
    }

    /**
     * Get virtuals
     *
     * @return ArrayCollection
     */
    public function getVirtuals()
    {
        return $this->virtuals;
    }

    /**
     * Add virtual
     *
     * @param Virtual $alias
     *
     * @return User
     */
    public function addVirtual(Virtual $alias)
    {
        $this->virtuals->add($alias);
        $alias->setName($this);

        return $this;
    }

    /**
     * Remove virtual
     *
     * @param Virtual $alias
     *
     * @return User
     */
    public function removeVirtual(Virtual $alias)
    {
        $this->virtuals->removeElement($alias);

        return $this;
    }

    public function __toString()
    {
        return $this->getUser();
    }
}
