<?php

namespace App\Command;

use duncan3dc\Sonos\Controller;
use duncan3dc\Sonos\Interfaces\SpeakerInterface;
use duncan3dc\Sonos\Network;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SonosWhat extends Command
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
        $this->setName('sonos:what')
            ->setDescription('Whats going on, on the sonos network?');
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

        /** @var Controller $controller */
        foreach ($controllers as $controller) {
            $output->writeln('Controller: ' . $controller->getName());
            $speakers = $controller->getSpeakers();

            /** @var SpeakerInterface $speaker */
            foreach ($speakers as $speaker) {
                $output->writeln('Speaker: ' . $speaker->getIp() . ' --- ' . $speaker->getRoom());
                $output->writeln('Volume: ' . $speaker->getVolume(''));

                $state = $controller->getStateDetails();
                if ($state->isStreaming()) {
                    $output->writeln("Currently Streaming: " . $state->getStream());

                    if ($state->getArtist()) {
                        $output->writeln("Artist: " . $state->getArtist());
                    }
                } else {
                    $track = $controller->getStateDetails();
                    $output->writeln("Now Playing: " . $track->getArtist() . ' - ' . $track->getTitle() . ' (' . $track->getPosition() . '/' . $track->getDuration() . ')');
                }
            }
        }
    }
}
