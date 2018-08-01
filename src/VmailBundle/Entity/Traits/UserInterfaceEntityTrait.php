<?php

namespace VmailBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait UserInterfaceEntityTrait
{
    /**
     * @var string
     *
     */
    private $username;

    /**
     * @var string
     *
     */
    private $plainPassword;

    /**
     * Set plainpassword
     *
     * @param string $plainpassword
     *
     * @return users
     */
    public function setPlainPassword($password)
    {
        $this->plainPassword = $password;

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
        return $this->getEmail();
    }

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
}
