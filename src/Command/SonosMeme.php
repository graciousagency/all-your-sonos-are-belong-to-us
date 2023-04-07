<?php

namespace App\Command;

use duncan3dc\Sonos\Controller;
use duncan3dc\Sonos\Interfaces\SpeakerInterface;
use duncan3dc\Sonos\Network;
use duncan3dc\Sonos\Tracks\Spotify;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SonosMeme extends Command
{
    /**
     * @var Network
     */
    private $network;

    /**
     * @var array
     */
    private $songs;

    /**
     * @param null|string $name
     * @param Network $network
     * @param $songs
     */
    public function __construct(Network $network, array $songs)
    {
        parent::__construct();
        $this->network = $network;
        $this->songs = $songs;
    }

    protected function configure(): void
    {
        $this->setName('sonos:meme')
            ->setDescription('Put on a meme song');

        $this->addOption('volume', null, null, 'Adjust volume (0-100)');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Getting controllers');
        $controllers = $this->network->getControllers();
        $randomSongKey = array_rand($this->songs);
        /** @var Controller $controllers */
        foreach ($controllers as $controller) {
            /** @var Controller $controller */
            $output->writeln('Telling controller ' . $controller->getName() . ' to play our meme song');
            $controller->useQueue();

            $queueSize = $controller->getQueue()->count();
            if (!$queueSize) {
                $queueNumber = 1;
            }

            if ($queueSize) {
                $queueNumber = random_int(1, $queueSize);
            }

            $output->writeln('Injecting our song as ' . $queueNumber . ' of ' . $queueSize . ' in the queue');
            $controller->getQueue()->addTrack(
                new Spotify(
                    $this->songs[$randomSongKey]
                ),
                $queueNumber
            );

            $controller->setShuffle(true);
            $output->writeln('Skipping to song ' . $queueNumber . ' in the queue');
            $controller->selectTrack($queueNumber-1);

            usleep(10000);

            $controller->play();

            $volume = $input->getOption('volume');
            if (!empty($volume)) {
                $speakers = $controller->getSpeakers();
                /** @var SpeakerInterface $speaker */
                foreach ($speakers as $speaker) {
                    if ($speaker->getVolume() < $volume) {
                        $output->writeln($speaker->getName() . ' Setting volume to ' . $volume . ', cause the volume was too low');
                        $speaker->setVolume($volume);
                    }
                }
            }

            $state = $controller->getStateDetails();
            $output->writeln('Now Playing: ' . $state->getArtist() . ' - ' . $state->getTitle());
        }

        $output->writeln('Done! our meme song should be playing now!');
        return 0;
    }
}
