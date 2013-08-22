<?php

namespace NachoBrito\BOECrawlerBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

/**
 * WordFrequencyRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TermFrequencyRepository extends EntityRepository
{

    /**
     * Returns an array of vectors, each one representing a document.
     * For each document, the array items are the normalised freqs of the terms provided
     * 
     * @param type $termIds
     */
    public function getNormalizedVectorsForTerms($terms)
    {
        $ids = array();
        foreach ($terms as $t)
            $ids[] = $t->getId();

        $sql1 = "SELECT tf.term_id ,tf.item_id,tf.count,i.modulus,(tf.count/i.modulus) as normalised FROM TermFrequency tf INNER JOIN Item i ON tf.item_id=i.id WHERE tf.term_id IN (:tids);";

        $c = $this->getEntityManager()->getConnection();
        $st = $c->prepare($sql1);
        $st->bindValue('tids', implode(',', $ids));
        $st->execute();
        $data = array();

        //1. term freqs by doc
        while ($row = $st->fetch(Query::HYDRATE_ARRAY))
        {
            $item_id = $row['item_id'];
            $count = $row['count'];
            $term_id = $row['term_id'];
            $normalised = $row['normalised'];
            $modulus = (int) $row['modulus'];

            if (!isset($data[$item_id]))
            {
                $data[$item_id] = array();
            }

            $data[$item_id][$term_id] = $normalised;
        }

        return $data;
    }

}