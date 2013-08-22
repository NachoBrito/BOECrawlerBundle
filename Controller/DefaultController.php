<?php

namespace NachoBrito\BOECrawlerBundle\Controller;

use DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends Controller
{

    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
        /* @var $crawler BOECrawler */
        //$crawler = $this->get('nbboe_crawler.crawler');
        //$crawler->crawlDate('2013', '06', '27');
        //$crawler->crawlYear(2013);
        $vector = $this->get('nbboe_crawler.vector_space_model');
        $vector->reBuildVectorSpace();
    }

}
