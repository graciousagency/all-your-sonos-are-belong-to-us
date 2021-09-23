<?php

namespace App\Command;

use duncan3dc\Sonos\Controller;
use duncan3dc\Sonos\Interfaces\SpeakerInterface;
use duncan3dc\Sonos\Network;
use duncan3dc\Sonos\Tracks\Spotify;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SonosPlay extends Command
{
    /**
     * @var Network
     */
    private $network;

    /**
     * @param null|string $name
     * @param Network $network
     * @param $songs
     */
    public function __construct(?string $name = null, Network $network)
    {
        parent::__construct($name);
        $this->network = $network;
    }

    protected function configure(): void
    {
        $this->setName('sonos:play')
            ->setDescription('Play given song');

        $this->addArgument('song', InputArgument::REQUIRED, 'Song to play');
        $this->addOption('volume', null, InputOption::VALUE_OPTIONAL, 'Adjust volume (1-100)');
        $this->addUsage('spotify:track:1F2WlnUZgreayYJE0cbPk7');
        $this->addUsage('--volume=50 spotify:track:1F2WlnUZgreayYJE0cbPk7');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $song = $input->getArgument('song');
        $volume = $input->getOption('volume');
        $output->writeln('Getting controllers');
        $controllers = $this->network->getControllers();

        preg_match('/track\/(\w+)\?/', $song, $output_array);
        $songId = $output_array[1];

        /** @var Controller $controllers */
        foreach ($controllers as $controller) {
            /** @var Controller $controller */
            $output->writeln('Telling controller ' . $controller->getName() . ' to play our song');
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
                new Spotify($songId),
                $queueNumber
            );

            $controller->setShuffle(true);
            $output->writeln('Skipping to song ' . $queueNumber . ' in the queue');
            $controller->selectTrack($queueNumber-1);

            usleep(10000);

            $controller->play();

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
            usleep(250000);
            $state = $controller->getStateDetails();
            $output->writeln('Now Playing: ' . $state->getArtist() . ' - ' . $state->getTitle());
        }

        $output->writeln('The given song should be playing now');
        return 0;
    }
}
