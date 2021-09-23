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

    public function __construct(?string $name = null, Network $network)
    {
        parent::__construct($name);
        $this->network = $network;
    }

    protected function configure()
    {
        $this->setName('sonos:queue')
            ->setDescription('What\'s in the queue?');
        $this->addOption('remove', 'rm', InputOption::VALUE_OPTIONAL, 'Remove song from the queue', false);
        $this->addOption('add', null, InputOption::VALUE_OPTIONAL, 'Add song to the queue', false);
        $this->addOption('queue', null, InputOption::VALUE_OPTIONAL, 'Play from queue', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);
        $table->setHeaders(['Queue #', 'Artist', 'Title']);

        $controllers = $this->network->getControllers();
        $options = $input->getOptions();

        foreach ($controllers as $controller) {
            $tracks = $controller->getQueue()->getTracks();
            if ($controller->isUsingQueue()){
                $output->writeln('We are using the queue now');
            }

            if (!$controller->isUsingQueue()){
                $output->writeln('We are NOT using the queue now');
                if (!empty($options['queue'])) {
                    $controller->useQueue();
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
                    usleep(500000);
                }
            }

            if (!empty($options['add']) && is_string($options['add'])) {
                $controller->getQueue()->addTrack(
                    new Spotify($options['add']),
                );
                $output->writeln('Added new song to the back of the queue');
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
