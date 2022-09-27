<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\ActivableEntityTrait;

/**
 * Alias
 *
 * @ORM\Table(name="alias")
 * @ORM\Entity(repositoryClass="App\Repository\AliasRepository")
 */
class Alias
{
    use ActivableEntityTrait;
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
     * @ORM\ManyToOne(targetEntity="User", inversedBy="aliasnames",cascade={"persist"})
     */
    private $aliasname;

    /**
     * @var string
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="addressnames",cascade={"persist"})
     */
    private $addressname;



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
     * Set aliasname
     *
     * @param User $aliasname
     *
     * @return Alias
     */
    public function setAliasname(User $user)
    {
        $this->aliasname = $user;
        $user->setList(true);

        return $this;
    }

    /**
     * Get aliasname
     *
     * @return User
     */
    public function getAliasname()
    {
        return $this->aliasname;
    }

    /**
     * Set addressname
     *
     * @param User $address
     *
     * @return Alias
     */
    public function setAddressname(User $address)
    {
        $this->addressname = $address;

        return $this;
    }

    /**
     * Get addressname
     *
     * @return User
     */
    public function getAddressname()
    {
        return $this->addressname;
    }

    public function __toString()
    {
        return $this->getAliasname()->getEmail() .'/'. $this->getAddressname()->getEmail();
    }
}
