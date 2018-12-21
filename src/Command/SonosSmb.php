<?php

namespace App\Command;

use App\Track\SmbTrack;
use duncan3dc\Sonos\Controller;
use duncan3dc\Sonos\Network;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SonosSmb extends Command
{
    /**
     * @var Network
     */
    private $network;

    /**
     * @var string
     */
    private $smbServerName;

    /**
     * @var string
     */
    private $smbShareName;

    /**
     * @param null|string $name
     * @param Network     $network
     * @param string      $smbServerName
     */
    public function __construct(?string $name = null, Network $network, string $smbServerName, string $smbShareName)
    {
        parent::__construct($name);
        $this->network = $network;
        $this->smbServerName = $smbServerName;
        $this->smbShareName = $smbShareName;
    }

    protected function configure()
    {
        $this->setName('sonos:smb')
             ->setDescription('Play an MP3 from SMB share')
             ->addOption('volume', null, InputOption::VALUE_OPTIONAL, 'Adjust volume (0-100)', false)
             ->addArgument('file');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return void
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $track = new SmbTrack($this->smbShareName.'/'.$input->getArgument('file'), $this->smbServerName);
        $output->writeln('Getting controllers');

        /** @var Controller $controllers */
        $controllers = $this->network->getControllers();

        /** @var \duncan3dc\Sonos\Controller $controller */
        foreach ($controllers as $controller) {
            $output->writeln('Interrupting playback with our SMB track now for controller ' . $controller->getName());
            $controller->interrupt($track, $input->getOption('volume'));
        }

        $output->writeln('Done!');
    }
}
