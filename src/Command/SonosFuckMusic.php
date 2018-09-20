<?php

namespace App\Command;

use duncan3dc\Sonos\Controller;
use duncan3dc\Sonos\Interfaces\SpeakerInterface;
use duncan3dc\Sonos\Network;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SonosFuckMusic extends Command
{
    /**
     * @var Network
     */
    private $network;

    /**
     * @param null|string $name
     * @param Network $network
     */
    public function __construct(?string $name = null, Network $network)
    {
        parent::__construct($name);
        $this->network = $network;
    }

    protected function configure()
    {
        $this->setName('sonos:fuck')
            ->setDescription('Mute shitty music');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        /** @var Controller $controllers */
        $controllers = $this->network->getControllers();

        foreach ($controllers as $controller) {
            /** @var Controller $controller */
            $speakers = $controller->getSpeakers();

            /** @var SpeakerInterface $speaker */
            foreach ($speakers as $speaker) {
                $output->writeln('Settings speaker (' . $speaker->getName() . ') volume to 0, was ' . $speaker->getVolume());
                $speaker->setVolume(0);
            }
        }
    }
}
