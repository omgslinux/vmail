<?php

namespace VmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use VmailBundle\Entity\Traits\ActivableEntityTrait;
use VmailBundle\Entity\AutoreplyCache;
use VmailBundle\Entity\Autoreply;

/**
 * Autoreply
 *
 * @ORM\Table(name="autoreply")
 * @ORM\Entity(repositoryClass="VmailBundle\Repository\UserRepository")
 */
class Autoreply
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
     * @var user
     *
     * @ORM\OneToOne(targetEntity="User", inversedBy="reply")
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
     * @ORM\Column(type="datetime")
     */
    private $startdate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $enddate;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="AutoreplyCache", mappedBy="reply")
     */
    private $replys;


    public function __construct()
    {
        $this->startdate=new \DateTime();
        $this->startdate->format('Y-m-d');
        $this->enddate=$this->startdate;
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
     * @param User $user
     *
     * @return Autoreply
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
     * Set message
     *
     * @param string $message
     *
     * @return Autoreply
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set startdate
     *
     * @param datetime $startdate
     *
     * @return Autoreply
     */
    public function setStartDate($datetime)
    {
        $this->startdate = $datetime;

        return $this;
    }

    /**
     * Get startdate
     *
     * @return datetime
     */
    public function getStartDate()
    {
        return $this->startdate;
    }

    /**
     * Set enddate
     *
     * @param datetime $enddate
     *
     * @return Autoreply
     */
    public function setEndDate($datetime)
    {
        $this->enddate = $datetime;

        return $this;
    }

    /**
     * Get enddate
     *
     * @return datetime
     */
    public function getEndDate()
    {
        return $this->enddate;
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
     * @param AutoreplyCache $autoreply
     *
     * @return Domain
     */
    public function addReply(AutoreplyCache $reply)
    {
        $this->replys->add($reply);
        $reply->setAutoreply($this);

        return $this;
    }

    /**
     * Remove reply
     *
     * @param AutoreplyCache $reply
     *
     * @return Autoreply
     */
    public function removeReply(AutoreplyCache $reply)
    {
        $this->replys->removeElement($reply);

        return $this;
    }



}
