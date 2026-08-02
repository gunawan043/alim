<?php

namespace App\Jobs;

use App\Models\NotificationUniversal;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendUniversalEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;

    public $notification;

    public function __construct(User $user, NotificationUniversal $notification)
    {
        $this->user = $user;
        $this->notification = $notification;
    }

    public function handle()
    {
        Mail::send('emails.universal-notification', [
            'user' => $this->user,
            'notification' => $this->notification,
        ], function ($message) {
            $message->to($this->user->email)
                ->subject($this->notification->title);
        });
    }
}
