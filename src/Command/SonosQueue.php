<?php

namespace App\Command;

use duncan3dc\Sonos\Network;
use duncan3dc\Sonos\Tracks\Spotify;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SonosQueue extends Command
{
    private Network $network;

    public function __construct(Network $network)
    {
        parent::__construct();
        $this->network = $network;
    }

    protected function configure()
    {
        $this->setName('sonos:queue')
            ->setDescription('What\'s in the queue?');
        $this->addOption('remove', 'rm', InputOption::VALUE_OPTIONAL, 'Remove song from the queue', false);
        $this->addOption('add', null, InputOption::VALUE_OPTIONAL, 'Add song to the queue', false);
        $this->addOption('nr', null, InputOption::VALUE_OPTIONAL, 'Add song in queue to this spot in the queue', null);
        $this->addOption('queue', null, InputOption::VALUE_OPTIONAL, 'Play from queue', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);
        $table->setHeaders(['Queue #', 'Artist', 'Title']);

        $controllers = $this->network->getControllers();
        $options = $input->getOptions();
        $reset = false;

        foreach ($controllers as $controller) {
            $tracks = $controller->getQueue()->getTracks();
            if ($controller->isUsingQueue()){
                $output->writeln('We are using the queue now');
            }

            if (!$controller->isUsingQueue()){
                $output->writeln('We are NOT using the queue now');
                if (!empty($options['queue'])) {
                    $controller->useQueue();
                    $reset = true;
                    $output->writeln('Forced controller to use the queue instead');
                }
            }

            if (is_numeric($options['remove'])) {
                if (!isset($tracks[$options['remove']])) {
                    $output->writeln('Given track index does not exist!');
                }
                if (isset($tracks[$options['remove']])) {
                    $output->writeln('Removed '. $tracks[$options['remove']]->getArtist() .' - '. $tracks[$options['remove']]->getTitle());
                    $controller->getQueue()->removeTrack($options['remove']);
                    $reset = true;
                }
            }

            if (!empty($options['add']) && is_string($options['add'])) {
                preg_match('/track\/(\w+)\?/', $options['add'], $output_array);
                $controller->getQueue()->addTrack(
                    new Spotify($output_array[1]),
                    $input->getOption('nr')
                );
                $reset = true;
                $output->writeln('Added new song to the back of the queue');
            }

            if ($reset) {
                usleep(500000);
                $tracks = $controller->getQueue()->getTracks();
            }

            foreach ($tracks as $number => $song) {
                $table->addRow([
                    $number,
                    $song->getArtist(),
                    $song->getTitle()
                ]);
            }
            $output->writeln('Total songs in the queue: ' . $controller->getQueue()->count());
        }
        $table->render();
        return 0;
    }
}
