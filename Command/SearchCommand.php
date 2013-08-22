<?php

namespace NachoBrito\BOECrawlerBundle\Command;

use NachoBrito\BOECrawlerBundle\Services\SearchService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Description of SearchCommand
 *
 * @author nacho
 */
class SearchCommand extends ContainerAwareCommand
{

    /**
     * 
     */
    protected function configure()
    {

        $this
                ->setName('boecrawler:search')
                ->setDescription('Searches items in BOE')
                ->addArgument('q', InputArgument::REQUIRED, 'Términos a buscar (entre comillas si usas más de uno)');
    }

    /**
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {        
        $q = $input->getArgument('q');
        $output->writeln('Search results for "' . $q . '":');
        /* @var $search SearchService */
        $search = $this->getContainer()->get('nbboe_crawler.search_service');
        
        $result = $search->searchItems($q);
        
        $output->write(print_r($result,true));
    }
}
