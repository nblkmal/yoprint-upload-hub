<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FileFailedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $fileName;
    public int $fileId;
    public string $error;

    /**
     * Create a new event instance.
     */
    public function __construct(string $fileName = '', int $fileId = 0, string $error = '')
    {
        $this->fileName = $fileName;
        $this->fileId = $fileId;
        $this->error = $error;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'file.failed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'fileName' => $this->fileName,
            'fileId' => $this->fileId,
            'status' => 'failed',
            'error' => $this->error,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('file-uploaded'),
        ];
    }
}
