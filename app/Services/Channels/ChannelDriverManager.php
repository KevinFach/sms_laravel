<?php

namespace App\Services\Channels;

use App\Enums\ChannelType;
use App\Models\Channel;
use App\Models\Message;

/**
 * Resuelve el driver de envío que corresponde a cada canal.
 */
class ChannelDriverManager
{
    public function driver(Channel $channel): ChannelDriver
    {
        return match ($channel->tipo) {
            ChannelType::Api51x => new Api51xDriver($channel),
            default => new PullDriver,
        };
    }

    /** Driver del canal asignado al mensaje (pull por defecto si no tiene canal). */
    public function forMessage(Message $message): ChannelDriver
    {
        return $message->channel
            ? $this->driver($message->channel)
            : new PullDriver;
    }
}
