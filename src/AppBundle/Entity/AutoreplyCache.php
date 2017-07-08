<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AutoreplyCache
 *
 * @ORM\Table(name="autoreply_cache")
 * @ORM\Entity
 */
class AutoreplyCache
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
     * @ORM\ManyToOne(targetEntity="User", inversedBy="autoreplycache")
     */
    private $user;

    /**
     * @var sender
     *
     * @ORM\Column(name="sender", type="string")
     */
    private $sender;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="datestart", type="datetime")
     */
    private $datesent;


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
     * @param Users $user
     *
     * @return AutoreplyCache
     */
    public function setUser(User $user)
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
     * Set sender
     *
     * @param string $sender
     *
     * @return AutoreplyCache
     */
    public function setSender($sender)
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * Get sender
     *
     * @return string
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * Set datesent
     *
     * @param \DateTime $datesent
     *
     * @return AutoreplyCache
     */
    public function setDateSent($datesent)
    {
        $this->datesent = $datesent;

        return $this;
    }

    /**
     * Get datesent
     *
     * @return \DateTime
     */
    public function getDateSent()
    {
        return $this->datesent;
    }
}
