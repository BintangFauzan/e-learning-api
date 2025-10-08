<?php

namespace App\Events;

use App\Models\Reply;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// 1. TAMBAHKAN PrivateChannel dan hapus Channel
use Illuminate\Broadcasting\PrivateChannel; 

class NewReplyPosted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $reply;

    // ... (fungsi __construct sudah benar)
    
    public function broadcastOn(): array
    {
        // 2. UBAH 'Channel' MENJADI 'PrivateChannel'
        return [
            new PrivateChannel('discussion.' . $this->reply->discussion_id),
        ];
    }
    
    // ... (fungsi broadcastAs sudah benar)
}