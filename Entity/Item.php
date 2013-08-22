<?php

namespace NachoBrito\BOECrawlerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * Item
 *
 * @ORM\Table(uniqueConstraints={@UniqueConstraint(name="url_idx", columns={"url"})})
 *
 * @ORM\Entity(repositoryClass="NachoBrito\BOECrawlerBundle\Entity\ItemRepository")
 */
class Item
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
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="ref", type="string", length=50)
     */    
    private $ref;
    

    /**
     * @var string
     *
     * @ORM\Column(name="html", type="text")
     */
    private $html;

    /**
     * @var string
     *
     * @ORM\Column(name="clean", type="text")
     */
    private $clean;
    
    /**
     * @var string
     *
     * @ORM\Column(name="analysis", type="text", nullable=true)
     */
    private $analysis;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="crawled_at", type="datetime")
     */
    private $crawledAt;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="processed_at", type="datetime", nullable=true)
     */
    private $processedAt;
    
    /**
     *
     * @var string
     * 
     * @ORM\Column(name="url", type="string", length=255)
     */
    private $url;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="boe_date", type="date")
     */
    private $boeDate;
    

    /**
     *    
     * @var string
     *
     * @ORM\Column(name="section", type="string", length=255)
     */
    protected $section;
    
    /**
     * @var string
     *
     * @ORM\Column(name="department", type="string", length=255)
     */
    protected $department;
    
    /**
     * @var string
     *
     * @ORM\Column(name="epigraph", type="string", length=255)
     */
    protected $epigraph;
    
    /**
     * @ORM\ManyToOne(targetEntity="Diary", inversedBy="items")
     * @ORM\JoinColumn(name="diary_id", referencedColumnName="id")
     * @var Diary 
     */
    protected $diary;
    
    /**
     * @ORM\OneToMany(targetEntity="TermFrequency", mappedBy="item")
     */
    protected $frequencies;  
    
    
    private $_toString = false;
    
    /**
     * @ORM\Column(name="modulus", type="float")
     */
    protected $modulus = 0;       


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
     * Set title
     *
     * @param string $title
     * @return Item
     */
    public function setTitle($title)
    {
        $this->title = $title;
    
        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set html
     *
     * @param string $html
     * @return Item
     */
    public function setHtml($html)
    {
        $this->html = $html;
    
        return $this;
    }

    /**
     * Get html
     *
     * @return string 
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * Set clean
     *
     * @param string $clean
     * @return Item
     */
    public function setClean($clean)
    {
        $this->clean = $clean;
    
        return $this;
    }

    /**
     * Get clean
     *
     * @return string 
     */
    public function getClean()
    {
        return $this->clean;
    }

    /**
     * Set crawledAt
     *
     * @param DateTime $crawledAt
     * @return Item
     */
    public function setCrawledAt($crawledAt)
    {
        $this->crawledAt = $crawledAt;
    
        return $this;
    }

    /**
     * Get crawledAt
     *
     * @return DateTime 
     */
    public function getCrawledAt()
    {
        return $this->crawledAt;
    }

    /**
     * Set processedAt
     *
     * @param DateTime $processedAt
     * @return Item
     */
    public function setProcessedAt($processedAt)
    {
        $this->processedAt = $processedAt;
    
        return $this;
    }

    /**
     * Get processedAt
     *
     * @return DateTime 
     */
    public function getProcessedAt()
    {
        return $this->processedAt;
    }

    /**
     * Set boeDate
     *
     * @param DateTime $boeDate
     * @return Item
     */
    public function setBoeDate($boeDate)
    {
        $this->boeDate = $boeDate;
    
        return $this;
    }

    /**
     * Get boeDate
     *
     * @return DateTime 
     */
    public function getBoeDate()
    {
        return $this->boeDate;
    }
    
    /**
     * 
     * @return type
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * 
     * @param type $url
     * @return \NachoBrito\BOECrawlerBundle\Entity\Item
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    public function getDiary()
    {
        return $this->diary;
    }

    public function setDiary(Diary $diary)
    {
        $this->diary = $diary;
        return $this;
    }

    public function getRef()
    {
        return $this->ref;
    }

    public function setRef($ref)
    {
        $this->ref = $ref;
        return $this;
    }

    public function getAnalysis()
    {
        return $this->analysis;
    }

    public function setAnalysis($analysis)
    {
        $this->analysis = $analysis;
        return $this;
    }

    public function getSection()
    {
        return $this->section;
    }

    public function setSection($section)
    {
        $this->section = $section;
        return $this;
    }

    public function getDepartment()
    {
        return $this->department;
    }

    public function setDepartment($department)
    {
        $this->department = $department;
        return $this;
    }

    public function getEpigraph()
    {
        return $this->epigraph;
    }

    public function setEpigraph($epigraph)
    {
        $this->epigraph = $epigraph;
        return $this;
    }


    public function getFrequencies()
    {
        return $this->frequencies;
    }

    public function setFrequencies($frequencies)
    {
        $this->frequencies = $frequencies;
        return $this;
    }


    public function getModulus()
    {
        return $this->modulus;
    }

    public function setModulus($modulus)
    {
        $this->modulus = $modulus;
        return $this;
    }


    public function __toString()
    {
        if(!$this->_toString){
            $this->_toString = '[' . $this->id . '] ' . $this->getDiary() . ' - ' . $this->title;
        }
        return $this->_toString;
    }

    
}
