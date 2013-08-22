<?php

namespace NachoBrito\BOECrawlerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * Diary
 *
 * @ORM\Table(uniqueConstraints={@UniqueConstraint(name="token_idx", columns={"token"})})
 * @ORM\Entity(repositoryClass="NachoBrito\BOECrawlerBundle\Entity\DiaryRepository")
 */
class Diary
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=50)
     */
    private $token;

    /**
     * @var integer
     *
     * @ORM\Column(name="nbo", type="integer")
     */
    private $nbo;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="pubDate", type="datetime")
     */
    private $pubDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="prevDate", type="datetime", nullable=true)
     */
    private $prevDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="nextDate", type="datetime", nullable=true)
     */
    private $nextDate;

    /**
     * @ORM\OneToMany(targetEntity="Item", mappedBy="diary")
     */
    protected $items;

    /**
     *
     * @var type 
     */
    private $_toString = false;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set token
     *
     * @param string $token
     * @return Diary
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string 
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set nbo
     *
     * @param integer $nbo
     * @return Diary
     */
    public function setNbo($nbo)
    {
        $this->nbo = $nbo;

        return $this;
    }

    /**
     * Get nbo
     *
     * @return integer 
     */
    public function getNbo()
    {
        return $this->nbo;
    }

    /**
     * Set pubDate
     *
     * @param \DateTime $pubDate
     * @return Diary
     */
    public function setPubDate($pubDate)
    {
        $this->pubDate = $pubDate;

        return $this;
    }

    /**
     * Get pubDate
     *
     * @return \DateTime 
     */
    public function getPubDate()
    {
        return $this->pubDate;
    }

    public function getItems()
    {
        return $this->items;
    }

    public function setItems($items)
    {
        $this->items = $items;
        return $this;
    }

    public function getPrevDate()
    {
        return $this->prevDate;
    }

    public function setPrevDate(\DateTime $prevDate)
    {
        $this->prevDate = $prevDate;
        return $this;
    }

    public function getNextDate()
    {
        return $this->nextDate;
    }

    public function setNextDate(\DateTime $nextDate)
    {
        $this->nextDate = $nextDate;
        return $this;
    }

    public function __toString()
    {
        if (!$this->_toString)
        {
            $this->_toString = '[' . $this->nbo . ' - ' .$this->pubDate . '] ';
        }
        return $this->_toString;
    }

}
