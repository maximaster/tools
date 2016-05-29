<?php

namespace Maximaster\Tools\Twig\Command;

use Maximaster\Tools\Twig\TemplateEngine;
use Maximaster\Tools\Twig\TwigCacheCleaner;
use Notamedia\ConsoleJedi\Application\Command\BitrixCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheClearCommand extends BitrixCommand {

    /** {@inheritdoc} */
    protected function configure()
    {
        $this->setName('twig:cache:clear')
            ->setDescription('Clears twig cache')
            ->addArgument('template');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $cleaner = new TwigCacheCleaner(TemplateEngine::getInstance()->getEngine());

            $template = $input->getArgument('template');

            $cleaned = strlen($template) > 0 ? $cleaner->clearByName($template) : $cleaner->clearAll();
            $output->writeln("<info>Deleted {$cleaned} cache files</info>");

        } catch (\Exception $e) {

            $output->writeln('<info>Twig cache was not deleted or deleted partly. Something went wrong.'
                . PHP_EOL
                . 'Message from cleaner:</info>'
                . PHP_EOL
                . '<error>' . $e->getMessage() . '</error>');
        }
    }
}