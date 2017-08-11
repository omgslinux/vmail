<?php

namespace VmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use VmailBundle\Entity\Autoreply;

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
     * @var Autoreply
     *
     * @ORM\ManyToOne(targetEntity="Autoreply", inversedBy="replys")
     */
    private $reply;

    /**
     * @var sender
     *
     * @ORM\Column(type="string", length=64)
     */
    private $sender;

    /**
     * @var sender
     *
     * @ORM\Column(type="string", length=64)
     */
    private $recipient;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="datestart", type="datetime")
     */
    private $datesent;

    /**
     * @var boolean
     *
     */
    private $demo=false;


    public function __construct()
    {
        $this->datesent=new \DateTime();
        $this->datesent->format('Y-m-d h:i:s');
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
     * Set reply
     *
     * @param Autoreply $reply
     *
     * @return AutoreplyCache
     */
    public function setReply(Autoreply $reply)
    {
        $this->reply = $reply;
        $this->setRecipient($reply->getUser()->getEmail());

        return $this;
    }

    /**
     * Get reply
     *
     * @return string
     */
    public function getReply()
    {
        return $this->reply;
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
     * Set recipient
     *
     * @param string $recipient
     *
     * @return AutoreplyCache
     */
    public function setRecipient($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get recpient
     *
     * @return string
     */
    public function getRecipient()
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

    public function setDemo($bool)
    {
        $this->demo = $bool;
        return $this;
    }

    public function isDemo()
    {
        return $this->demo;
    }


}
