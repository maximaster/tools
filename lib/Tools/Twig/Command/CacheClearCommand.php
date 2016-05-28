<?php

namespace Maximaster\Tools\Twig\Command;

use Maximaster\Tools\Twig\TemplateEngine;
use Notamedia\ConsoleJedi\Application\Command\BitrixCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheClearCommand extends BitrixCommand {

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('twig:cache:clear')
            ->setDescription('Clear all twig cache');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            TemplateEngine::clearAllCache();
            $output->writeln('<info>All Twig cache must be deleted</info>');
        } catch (\UnexpectedValueException $e) {
            $output->writeln('<info>Twig cache was not deleted. Probably directory with cache is not exists or something went wrong. '
                . PHP_EOL
                . 'Message from worker:</info>'
                . PHP_EOL
                . '<error>' . $e->getMessage() . '</error>');
        }


    }
}