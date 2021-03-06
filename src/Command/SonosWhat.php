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
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);
        $table->setHeaders(['Controller', 'Speaker', 'Volume', 'Playing']);

        /** @var Controller $controllers */
        $controllers = $this->network->getControllers();

        /** @var Controller $controller */
        foreach ($controllers as $controller) {
            $speakers = $controller->getSpeakers();

            /** @var SpeakerInterface $speaker */
            foreach ($speakers as $speaker) {
                $state = $controller->getStateDetails();

                $playing = 'Unknown';
                if ($state->isStreaming()) {
                    $playing = 'Streaming: ' . $state->getStream()->getTitle();
                }

                if (!$state->isStreaming()) {
                    $playing = 'Playing: ' . $state->getArtist() . ' - ' . $state->getTitle() . ' (' . $state->getPosition() . '/' . $state->getDuration() . ')';
                }

                $table->addRow([
                    $controller->getName(),
                    $speaker->getIp() . ' :: ' . $speaker->getRoom(),
                    $speaker->getVolume(),
                    $playing
                ]);
            }
        }
        $table->render();
        return 0;
    }
}
