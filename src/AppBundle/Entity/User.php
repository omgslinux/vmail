<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\BooleanType;
use AppBundle\Entity\Domain;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Users
 *
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 */
class User implements UserInterface
{
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
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255)
     */
    private $password;

    /**
     * @var string
     *
     */
    private $plainPassword;

    /**
     * @var domain
     *
     * @ORM\ManyToOne(targetEntity="Domain", inversedBy="users")
     */
    private $domain;

    private $domainName;

    /**
     * @var boolean
     *
     * @ORM\Column(name="active", type="boolean")
     */
    private $active=false;

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
    private $autoreply;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="AutoreplyCache", mappedBy="user")
     */
    private $autoreplycache;

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
     * Set plainpassword
     *
     * @param string $plainpassword
     *
     * @return users
     */
    public function setPlainPassword($password)
    {
        $this->plainpassword = $password;

        return $this;
    }

    /**
     * Get plainpassword
     *
     * @return string
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
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

    /**
     * Set active
     *
     * @param string $active
     *
     * @return users
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return string
     */
    public function isActive()
    {
        return $this->active;
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

    public function getRoles()
    {
        if ($this->getDomain()->getId() === 0) {
            return ['ROLE_ADMIN'];
        } elseif ($this->isAdmin()) {
            return ['ROLE_MANAGER'];
        } elseif ($this->getId()) {
            return ['ROLE_USER'];
        } else {
            return [];
        }
    }

    public function getRol()
    {
        $rol=$this->getRoles();
        if (is_array($rol)) {
          return $this->getRoles()[0];
        } else {
          return false;
        }
    }

    public function eraseCredentials()
    {

    }

    public function getSalt()
    {
        return null;
    }

    public function getUsername()
    {
        return $this->getUser() . '@' . $this->getDomainName();
    }

/*    public function isAccountNonExpired()
    {
        return true;
    }

    public function isAccountNonLocked()
    {
        return true;
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }

    public function isEnabled()
    {
        return $this->isActive();
    }
*/
    public function serialize()
    {
        return serialize(array(
          $this->id,
          $this->user,
          $this->password,
          $this->active
        ));
    }

    public function unserialize($serialized)
    {
        list(
          $this->id,
          $this->user,
          $this->password,
          $this->active,
        ) = userialize($serialized);
    }

    public function __toString()
    {
        $this->getUser();
    }
}
