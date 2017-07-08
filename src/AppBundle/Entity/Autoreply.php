<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Autoreply
 *
 * @ORM\Table(name="autoreply")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 */
class Autoreply
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
     * @var user
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="autoreply")
     */
    private $user;

    /**
     * @var message
     *
     * @ORM\Column(name="message", type="text")
     */
    private $message;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="datestart", type="datetime")
     */
    private $datestart;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dateend", type="datetime")
     */
    private $dateend;

    /**
     * @var string
     *
     * @ORM\Column(name="active", type="boolean")
     */
    private $active;


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
     * @param string $domain
     *
     * @return users
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
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
    public function getActive()
    {
        return $this->active;
    }
}
