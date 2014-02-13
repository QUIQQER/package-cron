<?php

/**
 * This file contains QUI\Cron\Console
 */

namespace QUI\Cron;

/**
 * Cron Console Manager
 *
 * @author www.namerobot.com (Henning Leutz)
 */

class Console extends \QUI\System\Console\Tool
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
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        $this->writeLn( 'Welcom to the Cron Manager' );
        $this->writeLn( 'Which Command would you execute?' );
        $this->writeLn( '' );

        $this->commandRead();
    }

    /**
     * Read the command from the command line
     */
    public function commandRead()
    {
        $this->writeLn( 'Available Commands: ' );
        $this->writeLn( '- run' );

        $this->writeLn( '' );

        $this->writeLn( 'Command: ' );
        $command = $this->readInput();

        switch ( $command )
        {
            case 'run':
                $this->run();
                $this->commandRead();
            break;

            default:
                $this->writeLn( 'Command not found, please type another command', 'red' );
                $this->commandRead();
        }
    }

    /**
     * Execute all upcoming crons
     */
    public function run()
    {
        $Manager = new \QUI\Cron\Manager();

        $this->write('Execute all upcoming crons ...');
        $Manager->execute();

        $this->write('finish');
    }
}