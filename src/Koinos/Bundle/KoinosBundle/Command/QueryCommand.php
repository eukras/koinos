<?php 

namespace Koinos\Bundle\KoinosBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Koinos\Bundle\KoinosBundle\Service\ReferenceManager; 
use Koinos\Bundle\KoinosBundle\Utility\Reference; 

class QueryCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('koinos:query')
            ->setDescription('Lookup a reference; display in canonical form.')
            ->addArgument('library', InputArgument::REQUIRED, "Name of a library, e.g. 'nt', 'lxx'")
            ->addArgument('query',   InputArgument::REQUIRED, "Reference to a text."); 
            ;
    }

    protected function execute(inputinterface $input, outputinterface $output)
    {
        try { 
            $library = $input->getArgument('library'); 
            $rm = new ReferenceManager([$library]); 
        } catch (\Exception $e) { 
            $output->writeln("Could not initialise library '$library'."); 
            return; 
        }
        try { 
            $query = $input->getArgument('query'); 
            $reference = $rm->createReferenceFromQuery($query); 
        } catch (\Exception $e) { 
            $output->writeln("Could not create reference for query '$query'."); 
            return; 
        }

        $output->writeln("Title:       " . $rm->getTitle($reference)); 
        $output->writeln("Short Title: " . $rm->getShortTitle($reference)); 
        $output->writeln("Handle:      " . $rm->getHandle($reference)); 
        foreach ($reference->getRanges($asQuadruples=true) as $i => $range) { 
            $output->writeln("$i            " . json_encode($range)); 
        }
    }

}

