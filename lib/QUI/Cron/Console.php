<?php

/**
 * This file contains QUI\Cron\Console
 */

namespace QUI\Cron;

use QUI;

/**
 * Cron Console Manager
 *
 * @author www.namerobot.com (Henning Leutz)
 */
class Console extends QUI\System\Console\Tool
{
    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->setName('package:cron')
            ->setDescription('Cron Manager');
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        $run     = $this->getArgument('--run');
        $list    = $this->getArgument('--list');
        $listall = $this->getArgument('--list-all');
        $runCron = $this->getArgument('--cron');

        if ($run) {
            $this->run();
            return;
        }

        if ($list) {
            $this->listCrons();
            return;
        }

        if ($listall) {
            $this->listAllCrons();
            return;
        }

        if ($runCron) {
            $this->runCron($runCron);
            return;
        }

        $this->writeLn('Welcome to the Cron Manager');
        $this->writeLn('Which Command would you execute?');
        $this->writeLn('');

        $this->commandRead();
    }

    /**
     * Read the command from the command line
     */
    public function commandRead()
    {
        $this->writeLn('Available Commands: ');
        $this->writeLn("- run\t\trun all active crons");
        $this->writeLn("- list\t\tlist all active crons");
        $this->writeLn("- list-all\tlist all crons");
        $this->writeLn("- cron\trun a specific cron");

        $this->writeLn('');

        $this->writeLn('Command: ');
        $command = $this->readInput();

        switch ($command) {
            // run all crons
            case 'run':
                $this->run();
                $this->commandRead();
                break;

            // list all inserted crons
            case 'list':
                $this->listCrons();
                $this->commandRead();
                break;

            // list all inserted crons
            case 'list-all':
                $this->listAllCrons();
                $this->commandRead();
                break;

            case 'cron':
                $this->write("Please enter the Cron-ID: ");
                $cronId = $this->readInput();

                try {
                    $this->runCron($cronId);
                } catch (QUI\Exception $Exception) {
                    $this->writeLn($Exception->getMessage(), 'red');
                    $this->resetColor();
                    $this->writeLn('');
                }

                $this->commandRead();
                break;

            default:
                $this->writeLn(
                    'Command not found, please type another command',
                    'red'
                );

                $this->commandRead();
        }
    }

    /**
     * Execute all upcoming crons
     */
    public function run()
    {
        $Manager = new Manager();

        $this->writeLn('');
        $this->write('Execute all upcoming crons ...');

        $Manager->execute();

        $this->write('finish');
        $this->writeLn('');
    }

    /**
     * List all active crons
     */
    public function listCrons()
    {
        $Manager = new Manager();
        $list    = $Manager->getList();

        $this->writeLn('Cron list:');
        $this->writeLn('=======================================================');
        $this->writeLn('');

        foreach ($list as $entry) {
            if ($entry['active'] != 1) {
                continue;
            }

            $time = $entry['min']
                    . ' ' . $entry['hour']
                    . ' ' . $entry['day']
                    . ' ' . $entry['month'];

            $exec = $entry['exec'];

            $this->writeLn('ID: ' . $entry['id']);
            $this->writeLn($time . "\t" . $exec, 'green');

            $this->resetColor();
            $this->writeLn('');
        }

        $this->writeLn('=======================================================');
        $this->writeLn('');
    }

    /**
     * List all inserted Crons
     */
    public function listAllCrons()
    {
        $Manager = new Manager();
        $list    = $Manager->getList();

        $this->writeLn('Cron list:');
        $this->writeLn('=======================================================');
        $this->writeLn('');

        foreach ($list as $entry) {
            $time = $entry['min']
                    . ' ' . $entry['hour']
                    . ' ' . $entry['day']
                    . ' ' . $entry['month'];

            $exec = $entry['exec'];

            $this->writeLn('ID: ' . $entry['id']);
            $this->writeLn($time . "\t" . $exec, 'green');

            $this->resetColor();
            $this->writeLn('');
        }

        $this->writeLn('=======================================================');
        $this->writeLn('');
    }

    /**
     * Run a specific cron
     *
     * @param Boolean|Integer $cronId - ID of the cron
     * @throws QUI\Exception
     */
    public function runCron($cronId = false)
    {
        $Manager = new Manager();
        $cron    = $Manager->getCronById($cronId);

        if (!$cron) {
            throw new QUI\Exception('Cron not found');
        }

        $this->writeLn('Execute Cron: ' . $cronId. ' '. $cron['title']);
        $Manager->executeCron($cronId);

        $this->writeLn('=======================================================');
        $this->writeLn('');
    }
}
