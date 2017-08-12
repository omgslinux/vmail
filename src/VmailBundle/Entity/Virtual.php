<?php

namespace VmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use VmailBundle\Entity\Traits\ActivableEntityTrait;

/**
 * Virtual
 *
 * @ORM\Table(name="virtual_alias")
 * @ORM\Entity(repositoryClass="VmailBundle\Repository\AliasRepository")
 */
class Virtual
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
     * @ORM\ManyToOne(targetEntity="User", inversedBy="virtuals")
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=64)
     */
    private $address;



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
     * @param User $name
     *
     * @return Aliases
     */
    public function setName(User $alias)
    {
        $this->name = $alias;
        $alias->setList(true);

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
     * Set address
     *
     * @param string $address
     *
     * @return aliases
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

}
