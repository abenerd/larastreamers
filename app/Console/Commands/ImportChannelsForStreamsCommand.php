<?php

namespace App\Console\Commands;

use App\Facades\YouTube;
use App\Models\Channel;
use App\Models\Stream;
use App\Services\YouTube\StreamData;
use Illuminate\Console\Command;

class ImportChannelsForStreamsCommand extends Command
{
    protected $signature = 'larastreamers:import-streams-channels';

    protected $description = 'Imports channels for given streams.';

    public function handle(): int
    {
        $streamsWithoutChannel = Stream::whereNull('channel_id')
            ->approved()
            ->limit(50)
            ->get();

        if ($streamsWithoutChannel->isEmpty()) {
            $this->info('There are no streams without a channel.');

            return self::SUCCESS;
        }

        $this->info("Fetching {$streamsWithoutChannel->count()} stream(s) from API to check for channel.");

        $youTubeResponse = YouTube::videos($streamsWithoutChannel->pluck('youtube_id'));

        $youTubeResponse->each(function(StreamData $streamData) {
            // Import new channel
            $channelData = YouTube::channel($streamData->channelId);
            $channel = Channel::create(array_merge($channelData->prepareForModel(), ['language_code' => 'en']));
            $stream = Stream::where('youtube_id', $streamData->videoId)->first();
            // TODO, slug is null??
            $stream->update(['channel_id' => $channel->id]);
        });

        $this->info($streamsWithoutChannel->count().' stream channels were imported.');

        return self::SUCCESS;
    }
}
