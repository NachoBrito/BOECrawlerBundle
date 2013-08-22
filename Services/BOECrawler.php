<?php

namespace NachoBrito\BOECrawlerBundle\Services;

use Closure;
use DateTime;
use Doctrine\ORM\EntityManager;
use Exception;
use Goutte\Client;
use NachoBrito\BOECrawlerBundle\Entity\Diary;
use NachoBrito\BOECrawlerBundle\Entity\Item;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

/**
 * Description of BOECrawler
 *
 * @author nacho
 */
class BOECrawler
{

    const URL_PATTERN = 'http://www.boe.es/boe/dias/{Y}/{M}/{D}/index.php?s=c';
    const USER_AGENT = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.4; en-US; rv:1.9b5) Gecko/2008032619 Firefox/3.0b5';
    const URL_HOST = 'http://www.boe.es';

    /**
     *
     * @var Client
     */
    private $client;

    /**
     *
     * @var Diary
     */
    private $diary;

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

    function __construct(EntityManager $em = null, LoggerInterface $logger = null)
    {
        $this->em = $em;
        $this->logger = $logger;
    }



    /**
     * 
     */
    public function crawlSinceLastKnownDate()
    {
        $q = $this->em->createQuery('SELECT d from NBBOECrawlerBundle:Diary d ORDER BY d.pubDate DESC');
        $q->setMaxResults(1);
        /* @var $d Diary */
        $d = $q->getSingleResult();
        $last_date = $d->getPubDate();
        $this->crawlSince($last_date);
    }

    public function crawlSince($date)
    {
        $sql_logger = $this->em->getConnection()->getConfiguration()->getSQLLogger();

        //prevent memory exhaustion in very long session:
        //(see http://stackoverflow.com/questions/9699185/memory-leaks-symfony2-doctrine2-exceed-memory-limit)
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);


        $now = new DateTime();
        do
        {
            $year = $date->format('Y');
            $month = $date->format('m');
            $day = $date->format('d');
            $this->crawlDate($year, $month, $day);
            $date->modify('+1 day');
            $this->em->clear();
        } while ($date < $now);
        //leave the logger as it was before.
        $this->em->getConnection()->getConfiguration()->setSQLLogger($sql_logger);
    }

    /**
     * 
     * @param type $year
     */
    public function crawlYear($year)
    {
        $date = new DateTime("$year/01/01 00:00:00");
        $this->crawlSince($date);
    }
    /**
     * 
     */
    public function crawlToday()
    {
        $day = date('d');
        $month = date('m');
        $year = date('Y');

        $this->crawlDate($year, $month, $day);
    }
    /**
     * 
     * @param type $year
     * @param type $month
     * @param type $day
     */
    public function crawlDate($year, $month, $day)
    {
        $url = $this->getURL($year, $month, $day);
        /* @var $client Client */
        $this->client = new Client();
        $this->url_queue = array();

        //1. request master index page
        $crawler = $this->getCrawler($url);
        $xml_url = $crawler->filter('div.linkSumario2 > ul > li.puntoXML a');
        if ($xml_url->count())
        {
            $xurl = $xml_url->first()->attr('href');
            $crawler = $this->getCrawler($xurl);
            $crawler_diary = $crawler->filter('sumario > diario');
            if ($crawler_diary->count())
            {
                $n = $crawler_diary->first();
                $this->getDiary($n);
                $this->getSections($n);
            } else
            {
                $this->logger->info("XML SEEMS TO BE INVALID: $xurl");
            }
        } else
        {
            $this->logger->info("NO XML FOUND FOR DATE $year/$month/$day");
        }
    }

    /**
     * 
     * @param Crawler $crawler_diary
     */
    private function getSections(Crawler $crawler_diary)
    {
        /* @var $items_closure Closure */
        $this->items_closure = Closure::bind(function(Crawler $item_crawler, $l)
                        {
                            $this->parseItem($item_crawler);
                        }, $this);

        $this->epigraph_closure = Closure::bind(function(Crawler $epigraph_crawler, $k)
                        {
                            $this->epigraph_name = $epigraph_crawler->attr('nombre');
                            $epigraph_crawler->filter('item')->each($this->items_closure);
                        }, $this);

        $this->department_closure = Closure::bind(function (Crawler $department_crawler, $j)
                        {
                            $this->department_name = $department_crawler->attr('nombre');
                            $department_crawler->filter('epigrafe')->each($this->epigraph_closure);
                        }, $this);

        $this->section_closure = Closure::bind(function (Crawler $section_crawler, $i)
                        {
                            $this->section_name = $section_crawler->attr('nombre');
                            $section_crawler->filter('departamento')->each($this->department_closure);
                        }, $this);

        $crawler_diary->filter('seccion')->each($this->section_closure);
    }

    /**
     * 
     */
    private function crawler2Map(Crawler $crawler)
    {
        $map = array();
        /* @var $$node DOMNode */
        foreach ($crawler->children() as $node)
        {
            $k = $node->nodeName;
            $v = $node->nodeValue;
            $map[$k] = $v;
        }
        return $map;
    }

    /**
     * 
     * @param type $item_crawler
     */
    private function parseItem(Crawler $item_crawler)
    {
        try
        {
            $ref = $item_crawler->attr('id');

            $map = $this->crawler2Map($item_crawler);
            $url = $map['urlHtm'];
            $title = $map['titulo'];

            $item = $this->em->getRepository('NBBOECrawlerBundle:Item')->findOneByUrl($url);
            if (!$item)
            {
                $crawler = $this->getCrawler($url);

                $html = $crawler->filter('body')->html();

                $item = new Item();
                $item->setTitle($title);
                $item->setRef($ref);
                $item->setUrl($url);
                $item->setEpigraph($this->epigraph_name);
                $item->setDepartment($this->department_name);
                $item->setSection($this->section_name);
                //$item->setHtml($html);
                $item->setHtml('');
                $item->setClean(strip_tags($html));
                $item->setCrawledAt(new DateTime());
                $item->setDiary($this->diary);
                $item->setBoeDate($this->diary->getPubDate());

                $analysis = $crawler->filter('body div.analisisDoc');
                if ($analysis->count())
                {
                    $item->setAnalysis($analysis->first()->html());
                }

                $this->em->persist($item);
                $this->em->flush($item);
            }
        } catch (Exception $x)
        {
            $this->logger->err($x->getMessage());
        }
    }

    /**
     * 
     * @param Crawler $crawler_diary
     */
    private function getDiary(Crawler $crawler_diary)
    {
        $token = $crawler_diary->filter('id')->first()->text();
        $repo = $this->em->getRepository('NBBOECrawlerBundle:Diary');
        /* @var $Item Item */
        $this->diary = $repo->findOneByToken($token);
        if (!$this->diary)
        {
            $this->diary = new Diary();
            $this->diary->setNbo($crawler_diary->attr('nbo'));
            $this->diary->setToken($token);
            $crawler_meta = $crawler_diary->parents()->first()->filter('meta')->first();
            $pubDate = $crawler_meta->filter('fecha')->first()->text();
            $this->diary->setPubDate(DateTime::createFromFormat('d/m/Y', $pubDate));
            $nextDate = $crawler_meta->filter('fechaSig');
            if ($nextDate->count())
            {
                $this->diary->setPubDate(DateTime::createFromFormat('d/m/Y', $nextDate->first()->text()));
            }
            $prevDate = $crawler_meta->filter('fechaAnt');
            if ($prevDate->count())
            {
                $this->diary->setPubDate(DateTime::createFromFormat('d/m/Y', $prevDate->first()->text()));
            }

            $this->em->persist($this->diary);
            $this->em->flush($this->diary);
        }
    }

    /**
     * 
     * @param type $year
     * @param type $month
     * @param type $day
     */
    public function getURL($year, $month, $day)
    {
        $find = array('{Y}', '{M}', '{D}');
        $replace = array($year, $month, $day);

        return str_replace($find, $replace, self::URL_PATTERN);
    }

    /**
     * 
     * @param type $url
     * @return Crawler
     */
    private function getCrawler($url)
    {
        if (strpos($url, self::URL_HOST) !== 0)
        {
            $url = self::URL_HOST . $url;
        }
        $this->logger->info("Crawling url: $url");
        $this->client->setHeader('User-Agent', self::USER_AGENT);
        /* @var $crawler Crawler */
        $crawler = $this->client->request('GET', $url);

        return $crawler;
    }

    /**
     * 
     * @param type $url
     */
    private function getHTML($url)
    {
        $crawler = $this->getCrawler($url);
        $html = $crawler->filter('body')->html();
        return $html;
    }

}
