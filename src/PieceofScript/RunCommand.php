<?php

namespace PieceofScript;

use PieceofScript\Services\Config\Config;
use PieceofScript\Services\Errors\InternalError;
use PieceofScript\Services\Out\In;
use PieceofScript\Services\Out\Out;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use PieceofScript\Services\Tester;

/**
 * Class RunCommand
 *
 * @package PieceofScript
 */
class RunCommand extends Command
{

    protected function configure()
    {
        $this->setName('run')
            ->setDescription('Run testing scenario')
            ->setHelp('This command runs testing scenario')
            ->addArgument('scenario', InputArgument::REQUIRED, 'Start script file')
            ->addOption('junit', 'j', InputOption::VALUE_OPTIONAL, 'Reporting file in JUnit format', null)
            ->addOption('html', 'r', InputOption::VALUE_OPTIONAL, 'Reporting file in HTML format', null)
            ->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Configuration file', null)
            ->addOption('storage', 's', InputOption::VALUE_OPTIONAL, 'Storage file', null)
            ->addOption('skip-assertions', 'a', InputOption::VALUE_OPTIONAL, 'Skip assertions outside Endpoint call', null)
            ->setHelp('Run testing scenario');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            Out::setOutput($output);
            In::init($input, $output, $this->getHelper('question'));
            $startFile = realpath($input->getArgument('scenario'));
            if (!file_exists($startFile) || !is_readable($startFile)) {
                throw new InternalError('File is not readable ' . $input->getArgument('scenario'));
            }
            chdir(dirname($startFile));
            if ($input->getOption('config')) {
                Config::loadFromFile($input->getOption('config'), true);
            } else {
                Config::loadFromFile('./config.yaml', false);
            }
            Config::loadInput($input);
            $tester = new Tester($startFile, $input->getOption('junit'), $input->getOption('html'));
            return $tester->run();
        } catch (InternalError $e) {
            Out::printError($e);
            return 1;
        }
    }
}