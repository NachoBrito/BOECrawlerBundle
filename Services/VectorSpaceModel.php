<?php

namespace NachoBrito\BOECrawlerBundle\Services;

use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Exception;
use NachoBrito\BOECrawlerBundle\Entity\Item;
use NachoBrito\BOECrawlerBundle\Entity\ItemRepository;
use NachoBrito\BOECrawlerBundle\Entity\Term;
use NachoBrito\BOECrawlerBundle\Entity\TermFrequencyRepository;
use NachoBrito\BOECrawlerBundle\Entity\TermRepository;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

/**
 * Description of VectorSpaceModel
 *
 * @author nacho
 */
class VectorSpaceModel
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
     * @var type 
     */
    private $_stopWords = array();

    /**
     *
     * @var TermRepository
     */
    private $_termRepo;

    /**
     *
     * @var ItemRepository 
     */
    private $_itemRepo;

    /**
     *
     * @var TermFrequencyRepository 
     */
    private $_freqRepo;
    private $_totalItems = 0;
    private $_terms;
    private $_new_terms;
    private $_frequencies;
    private $_moduli;
    private $_stemm_cache = array();

    /**
     * 
     * @param EntityManager $em
     * @param LoggerInterface $logger
     */
    function __construct(
            EntityManager $em = null, 
            Stemmer $stemmer, 
            StopWordsProvider $stopWords,
            LoggerInterface $logger = null)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->stemmer = $stemmer;
        $this->stopWords = $stopWords;
    }

    /**
     * 
     */
    private function initRepos()
    {
        $this->_termRepo = $this->em->getRepository('NBBOECrawlerBundle:Term');
        $this->_itemRepo = $this->em->getRepository('NBBOECrawlerBundle:Item');
        $this->_freqRepo = $this->em->getRepository('NBBOECrawlerBundle:TermFrequency');
    }

    public function reBuildVectorSpace()
    {
        mb_regex_encoding('UTF-8');
        mb_internal_encoding('UTF-8');

        $this->_moduli = array();
        
        $this->logger->info('Reading stop words and known terms...');
        $this->_stopWords = $this->stopWords->getStopWords();
        $this->_totalItems = $this->em->createQuery('SELECT COUNT(item.id) FROM NachoBrito\BOECrawlerBundle\Entity\Item item')->getSingleScalarResult();
        $this->loadKnownTerms();

        $this->initRepos();

        $this->logger->info('**** START FINDING TERMS ****');
        $this->findTerms();


        $this->logger->info('**** START DUMPING SQL ****');
        $this->dumpSQL();
    }

    /**
     * 
     */
    private function loadKnownTerms()
    {
        $q = $this->em->createQuery('SELECT term from NachoBrito\BOECrawlerBundle\Entity\Term term');
        $res = $q->iterate(false, Query::HYDRATE_ARRAY);
        $this->_terms = array();
        $this->_new_terms = array();
        foreach ($res as $row)
        {
            $t = $row[0];
            $this->_terms[$t['term']] = (int) $t['id'];
        }
        $q->free();
        unset($q);        
    }

    /**
     * 
     */
    public function findTerms()
    {

        $t0 = time();
        $total = 0;
        $errors = 0;

        $first = 0;
        $max = 50000;

        $loop_until_complete = false;
        do
        {
            $now = new DateTime();
            if ($first > 0)
            {
                //log expected time to finish:
                $v = $total / (time() - $t0);
                //ms remaining
                $ret = round($this->_totalItems / $v);
                $fut = new DateTime();
                $fut->setTimestamp(time() + $ret);
                $interval = $fut->diff($now);

                $this->logger->info('Remaining: ' . $interval->format('%d days, %h hours, %i minutes, %s seconds'));
            }

            $q = $this->em->createQuery('SELECT item.id, item.clean from NachoBrito\BOECrawlerBundle\Entity\Item item WHERE item.processedAt IS NULL');
            $q->setMaxResults($max);
            $q->setFirstResult($first);
            //iterate hydrates rows on demand:
            $res = $q->iterate(false, Query::HYDRATE_ARRAY);

            $page_results = 0;
            $this->logger->info("Processing items $first to " . ($first + $max));
            foreach ($res as $row)
            {
                $page_results++;
                try
                {
                    //the item is always the first element of the $row array
                    $item = reset($row);
                    $this->processItem($item);
                    $total++;
                } catch (Exception $x)
                {
                    $this->logger->error($x->getMessage());
                    $errors++;
                }
            }
            $first += ($page_results + 1);
            $q->free();
            unset($q);

            $this->em->flush();
            $this->em->clear();
        } while ($loop_until_complete && $page_results > 0);

        $t = time() - $t0;

        $this->logger->info('Terms found:' . count($this->_terms));
        $this->logger->info("Done rebuilding Vector Space Model. Parsed $total items in $t ms. (Errors: $errors).");
    }

    /**
     * caching proxy method for stemmer
     * 
     * @param type $word
     * @return type
     */
    private function stemm($word)
    {
        if (!isset($this->_stemm_cache[$word]))
        {
            $this->_stemm_cache[$word] = $this->stemmer->stemm($word);
        }
        return $this->_stemm_cache[$word];
    }

    /**
     * 
     * @param array $item
     */
    private function processItem($item = array())
    {

        //$this->logger->info('Processing "' . $item->getTitle() . '"');        
        $text = html_entity_decode(trim(mb_strtolower($item['clean'], 'UTF-8')));
        $words = mb_split("[\s,\(\)\.\'\"\:\«\»\/\;\=\-]+", $text);
        $freqs = array_count_values($words);
        
        $tid = $item['id'];
        //1. find term freqs:
        foreach ($words as $w)
        {
            //ignore single letters and common words
            if (mb_strlen($w) < 2 || in_array($w, $this->_stopWords))
            {
                continue;
            }
            $word = $this->stemm($w);
            //A new term was
            if (!isset($this->_terms[$word]) && !in_array($word, $this->_new_terms))
            {
                $this->_new_terms[] = $word;
                $this->_frequencies[$word] = array();
            }

            if (!isset($this->_frequencies[$word][$tid]))
            {
                $this->_frequencies[$word][$tid] = $freqs[$w];
            } else
            {
                $this->_frequencies[$word][$tid] += $freqs[$w];
            }
        }
        
        //2. calculate modulus:
        $total = 0;
        foreach ($words as $w)
        {
            //ignore single letters and common words
            if (mb_strlen($w) < 2 || in_array($w, $this->_stopWords))
            {
                continue;
            }            
            //note there is a stemm cache, so no overhead here.
            $word = $this->stemm($w);            
            $total += pow($this->_frequencies[$word][$tid],2);
        }
        $this->_moduli[$tid] = sqrt($total);
    }

    /**
     * 
     * @param type $fn
     * @return type
     */
    private function openSQLFile($fn = 1)
    {

        $now = new DateTime();
        $folder = 'generated-SQL_' . $now->format("Y-m-d");
        if (!file_exists($folder))
        {
            mkdir($folder);
        }
        $output_file = $folder . DIRECTORY_SEPARATOR . 'data-' . $now->format("Y-m-d_H.i.s_$fn") . '.sql';
        if (file_exists($output_file))
        {
            unlink($output_file);
        }
        $fp = fopen($output_file, 'w');
        fwrite($fp, "SET FOREIGN_KEY_CHECKS=0;\n");
        return $fp;
    }

    private function closeSQLFile($fp)
    {
        fwrite($fp, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fp);
        $this->logger->info('SQL File created.');
    }

    /**
     * 
     * 
     */
    private function dumpSQL()
    {
        $fn = 1;
        $fp = $this->openSQLFile($fn);
        $item_ids = array();

        $query_count = 0;
        $max_queries_per_file = 1000000;

        //1. Insert freqs for known terms
        foreach ($this->_terms as $term => $wID)
        {
            if (is_array(@$this->_frequencies[$term]))
            {
                $v = array();
                foreach ($this->_frequencies[$term] as $itemID => $count)
                {
                    if (!in_array($itemID, $item_ids))
                    {
                        fwrite($fp, "UPDATE `Item` SET `modulus`={$this->_moduli[$itemID]}, `processed_at` = NOW() WHERE `id` = '$itemID';\n");
                        $query_count++;
                        $item_ids[] = $itemID;
                    }

                    $tfidf = 1;
                    $v[] =  "(NULL,'$wID', '$itemID', '$count', '$tfidf')";
                    $query_count++;
                }
                $values = implode(",\n",$v);
                fwrite($fp, "INSERT INTO `TermFrequency` (`id`, `term_id`,`item_id`, `count`, `tfidf`) VALUES $values;\n");

                unset($this->_frequencies[$term]);
            }
            
            if ($query_count > $max_queries_per_file)
            {
                $fn++;
                $this->closeSQLFile($fp);
                $fp = $this->openSQLFile($fn);
                $query_count = 0;
            }

            unset($this->_terms[$term]);
        }

        //2. Insert new terms, and the their freqs
        foreach ($this->_new_terms as $term)
        {
            $quoted = $this->em->getConnection()->quote($term);
            fwrite($fp, "INSERT INTO `Term` (`id`, `term`, `count`) VALUES (NULL, $quoted, 0);\n");
            $query_count++;
            fwrite($fp, "SET @last_id = LAST_INSERT_ID();\n");
            $query_count++;
            $v = array();
            foreach ($this->_frequencies[$term] as $itemID => $count)
            {
                if (!in_array($itemID, $item_ids))
                {
                    fwrite($fp, "UPDATE `Item` SET `processed_at` = NOW() WHERE `id` = '$itemID';\n");
                    $query_count++;
                    $item_ids[] = $itemID;
                }
                $v[] = "(NULL,@last_id, '$itemID', '$count', 0)";
                $query_count++;
            }
            $values = implode(",\n",$v);
            fwrite($fp, "INSERT INTO `TermFrequency` (`id`, `term_id`,`item_id`, `count`, `tfidf`) VALUES $values;\n");
            if ($query_count > $max_queries_per_file)
            {
                $fn++;
                $this->closeSQLFile($fp);
                $fp = $this->openSQLFile($fn);
                $query_count = 0;
            }            
            unset($this->_frequencies[$term]);
        }

        $this->closeSQLFile($fp);
    }

    /**
     * 
     * @param type $text
     */
    private function getTerm($text)
    {
        $t = mb_strtolower($text);
        $term = $this->_termRepo->findOneByTerm($t);
        if (!$term)
        {
            $term = new Term();
            $term->setTerm($t);
            $term->setCount(0);
            $this->em->persist($term);
            $this->em->flush($term);
        }
        return $term;
    }


    /**
     * 
     * 
     */
    public function calculateIDF(){
        //1. Update the count field of every Term with the number of items it apears in 
        //   at least once.
        $t0 = time();
        $this->logger->info('Calculating inverse frequencies...');
        $sql = '
        UPDATE Term t
        LEFT JOIN 
        (
            SELECT term_id, COUNT(DISTINCT tf.item_id) AS item_count
            FROM TermFrequency tf
            GROUP BY tf.term_id        
        ) AS t2 ON t2.term_id=t.id
        SET t.count = t2.item_count
        ';
        $this->em->getConnection()->executeUpdate($sql);
        $t = time() - $t0;
        $this->logger->info('Done - ' . $t . 's.');
    }


}

