<?php

namespace NachoBrito\BOECrawlerBundle\Services;

use Doctrine\ORM\EntityManager;
use NachoBrito\BOECrawlerBundle\Entity\TermFrequencyRepository;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

/**
 * Description of SearchService
 *
 * @author nacho
 */
class SearchService
{

    /**
     *
     * @var EntityManager 
     */
    private $em;

    /**
     *
     * @var LoggerInterface 
     */
    private $logger;

    /**
     *
     * @var Stemmer 
     */
    private $stemmer;

    /**
     *
     * @var StopWordsProvider
     */
    private $stopWords;

    /**
     * 
     * @param EntityManager $em
     * @param Stemmer $stemmer
     * @param StopWordsProvider $stopWords
     * @param LoggerInterface $logger
     */
    function __construct(EntityManager $em = null, Stemmer $stemmer, StopWordsProvider $stopWords, LoggerInterface $logger = null)
    {
        mb_regex_encoding('UTF-8');
        mb_internal_encoding('UTF-8');

        $this->em = $em;
        $this->logger = $logger;
        $this->stemmer = $stemmer;
        $this->stopWords = $stopWords;
    }

    /**
     * 
     * @param mixed $query
     * @return array A list of Item instances that match the provided query
     */
    public function searchItems($query, $max_results = 10)
    {
        /* @var $termsRepo TermRepository  */
        $termsRepo = $this->em->getRepository('NBBOECrawlerBundle:Term');
        /* @var $freqsRepo TermFrequencyRepository */
        $freqsRepo = $this->em->getRepository('NBBOECrawlerBundle:TermFrequency');
        /* @var $itemsRepo ItemRepository */
        $itemsRepo = $this->em->getRepository('NBBOECrawlerBundle:Item');

        $query_vector = $this->prepareQuery($query);
        $words = array_keys($query_vector);
        $terms = $termsRepo->getTermsForWords($words);
        //$ifs = $termsRepo->getTermsIF($terms);

        $candidates = $freqsRepo->getNormalizedVectorsForTerms($terms);
        $doc = array();
        foreach ($terms as $term)
        {
            $doc[$term->getId()] = $query_vector[$term->getTerm()];
        }
        $similarities = $this->calculateSimilarities($candidates, $doc);

        $result = array();
        foreach ($similarities as $item_id => $sim)
        {
            $o = new \stdClass();
            $o->sim = $sim;
            $item = $itemsRepo->find($item_id);
            $o->title = $item->getTitle();
            $o->url = $item->getUrl();
            $result[] = $o;
            if (count($result) >= $max_results)
            {
                break;
            }
        }


        return $result;
    }

    /**
     * Calculate the cosine similarity as the dot-product of each
     * vector to the query vector.
     * 
     * @param type $vectors
     */
    public function calculateSimilarities($candidates, $doc)
    {
        $result = array();
        foreach ($candidates as $item_id => $freqs)
        {
            $sim = 0;
            foreach ($freqs as $term_id => $weight)
            {
                $sim += $weight * $doc[$term_id];
            }
            $result[$item_id] = $sim;
        }

        arsort($result, SORT_NUMERIC);

        return $result;
    }

    /**
     * 
     * @param type $query
     * @return array [term] => [freq]
     */
    private function prepareQuery($query)
    {
        $q = mb_strtolower($query);
        $parts = mb_split("[\s,\(\)\.\'\"\:\«\»\/\;\=\-]+", $q);
        $words = array();
        $stopWords = $this->stopWords->getStopWords();

        $total = 0;
        foreach ($parts as $w)
        {
            if (mb_strlen($w) < 2 || in_array($w, $stopWords))
            {
                continue;
            }
            $s = $this->stemmer->stemm($w);
            @$words[$s] ++;
            $total += pow($words[$s],2);
            //$words[] = $s;
        }
        $total = sqrt($total);
        foreach($words as $s => $freq){
            $words[$s] = $words[$s] / $total;
        }
        //Normalise:
        
        return $words;
    }

}
