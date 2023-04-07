<?php

namespace App\Command;

use duncan3dc\Sonos\Controller;
use duncan3dc\Sonos\Interfaces\SpeakerInterface;
use duncan3dc\Sonos\Network;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SonosWhat extends Command
{
    private Network $network;

    public function __construct(Network $network)
    {
        parent::__construct();
        $this->network = $network;
    }

    protected function configure()
    {
        $this->setName('sonos:what')
            ->setDescription('Whats going on, on the sonos network?');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);
        $table->setHeaders(['Controller', 'Speaker', 'Volume', 'Playing', 'Bass', 'Treble']);

        $controllers = $this->network->getControllers();

        foreach ($controllers as $controller) {
            $speakers = $controller->getSpeakers();

            foreach ($speakers as $speaker) {
                $state = $controller->getStateDetails();

                $playing = 'Unknown';
                if ($state->isStreaming()) {
                    $playing = 'Streaming: ' . $state->getStream()->getTitle();
                }

                if (!$state->isStreaming()) {
                    $playing = $state->getArtist() . ' - ' . $state->getTitle() . ' (' . $state->getPosition() . '/' . $state->getDuration() . ')';
                }

                $table->addRow([
                    $controller->getName(),
                    $speaker->getIp() . ' :: ' . $speaker->getRoom(),
                    $speaker->getVolume(),
                    $playing,
                    $speaker->getBass(),
                    $speaker->getTreble(),
                ]);
            }
        }
        $table->render();
        return 0;
    }
}
