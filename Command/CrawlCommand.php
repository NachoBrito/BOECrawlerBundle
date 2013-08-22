<?php

namespace NachoBrito\BOECrawlerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of CrawlCommand
 *
 * @author nacho
 */
class CrawlCommand extends ContainerAwareCommand
{

    /**
     * 
     */
    protected function configure()
    {

        $this
                ->setName('boecrawler:crawl')
                ->setDescription('Launches crawling service.');
    }

    /**
     * 
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Launching Crawler process...');
        $crawler = $this->getContainer()->get('nbboe_crawler.crawler');
        
        $crawler->crawlSinceLastKnownDate();
        
//        $y = date('Y');
//        $output->writeln("Crawling since Jan 1st $y");
//        $crawler->crawlYear($y);        
    }

}
