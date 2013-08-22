<?php

namespace NachoBrito\BOECrawlerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * Term
 *
 * @ORM\Table(indexes={@Index(name="term_idx", columns={"term"})})
 * @ORM\Entity(repositoryClass="NachoBrito\BOECrawlerBundle\Entity\TermRepository")
 */
class Term
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
     * @ORM\Column(name="term", type="string", length=100)
     */
    private $term;

    /**
     * @var integer
     *
     * @ORM\Column(name="count", type="integer")     
     */
    private $count;

    /**
     * @ORM\OneToMany(targetEntity="TermFrequency", mappedBy="term", cascade={"all"},fetch="EXTRA_LAZY" )
     */
    protected $frequencies;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    public function getTerm()
    {
        return $this->term;
    }

    public function setTerm($term)
    {
        $this->term = $term;
        return $this;
    }

    /**
     * Set count
     *
     * @param integer $count
     * @return Term
     */
    public function setCount($count)
    {
        $this->count = $count;

        return $this;
    }

    /**
     * Get count
     *
     * @return integer 
     */
    public function getCount()
    {
        return $this->count;
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

    /**
     * 
     */
    public function countHit()
    {
        $this->count += 1;
    }

}
