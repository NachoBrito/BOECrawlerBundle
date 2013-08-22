<?php

use NachoBrito\BOECrawlerBundle\Services\BOECrawler;

/**
 * Description of BOECrawlerTest
 *
 * @author nacho
 */
class BOECrawlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var BOECrawler
     */
    private $crawler;
    
    protected function setUp()
    {
        parent::setUp();
        $this->crawler = new BOECrawler();
    }

    protected function tearDown()
    {
        $this->crawler = null;
        parent::tearDown();
    }

    /**
     * 
     */
    public function testGetURLGeneratesValidURL(){
        $url_regex='/(https|http|ftp)\:\/\/|([a-z0-9A-Z]+\.[a-z0-9A-Z]+\.[a-zA-Z]{2,4})|([a-z0-9A-Z]+\.[a-zA-Z]{2,4})|\?([a-zA-Z0-9]+[\&\=\#a-z]+)/i';
        $u = $this->crawler->getURL('2013', '06', '25');
        $this->assertNotEmpty($u);
        $this->assertRegExp($url_regex, $u);
    }
}
