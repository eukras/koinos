<?php 

namespace Koinos\Bundle\KoinosBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Koinos\Bundle\KoinosBundle\Service\ReferenceManager; 

class ListCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('koinos:list')
            ->setDescription('List libraries and books')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $rm = $this->getContainer()->get('koinos'); 
        $libraries = $rm->getLibraryBooks();
        $pattern = " #%3s  %40s  %5s  %-30s"; 
        foreach ($libraries as $libraryName => $bookIds) { 
            $output->writeln(str_repeat('-', 15) . " $libraryName " . str_repeat('-', 75)); 
            foreach ($bookIds as $b) { 
                $output->writeln(vsprintf($pattern, [
                    $b, 
                    join(', ', [$rm->getName($b), $rm->getShortName($b)]), 
                    '(' . $rm->getChapters($b) . ')', 
                    join(', ', [$rm->getAbbreviation($b)] + $rm->getAliases($b)), 
                ])); 
            }
        }
    }

}

