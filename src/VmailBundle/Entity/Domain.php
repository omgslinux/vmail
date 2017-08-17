<?php

namespace VmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use VmailBundle\Entity\User;
use VmailBundle\Entity\Traits\ActivableEntityTrait;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * Domains
 *
 * @ORM\Table(name="domain", uniqueConstraints={@UniqueConstraint(name="name_unique", columns={"name", "id"})})
 * @ORM\Entity(repositoryClass="VmailBundle\Repository\DomainRepository")
 */
class Domain
{
    Use ActivableEntityTrait;
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
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    private $name;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="User", mappedBy="domain")
     */
    private $users;


    public function __construct()
    {
        $this->users=new ArrayCollection();
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
     * @return Domain
     */
    public function setName($name)
    {
        $this->name = $name;

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
     * Get users
     *
     * @return ArrayCollection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Add users
     *
     * @param User $users
     *
     * @return Domain
     */
    public function addUser(User $user)
    {
        $this->users->add($user);
        $user->setName($this);

        return $this;
    }

    /**
     * Remove user
     *
     * @param User $user
     *
     * @return Domain
     */
    public function removeUser(User $user)
    {
        $this->users->removeElement($user);

        return $this;
    }

    public function __toString()
    {
        return $this->getName();
    }

}
