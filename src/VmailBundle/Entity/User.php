<?php

namespace VmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\BooleanType;
use VmailBundle\Entity\Domain;
use VmailBundle\Entity\Traits\ActivableEntityTrait;
use VmailBundle\Entity\Traits\UserInterfaceEntityTrait;
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
     * @ORM\Column(name="user", type="string", length=255)
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
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Autoreply", mappedBy="user")
     */
    private $replys;

    /**
     * @var boolean
     *
     */
    private $sendemail;


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
     * Set sendemail
     *
     * @return User
     */
    public function setSendemail($value)
    {
        $this->sendemail=$value;
        return $this;
    }

    /**
     * Get sendemail
     *
     * @return boolean
     */
    public function getSendemail()
    {
        return $this->sendemail;
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

    public function __toString()
    {
        $this->getUser();
    }
}
