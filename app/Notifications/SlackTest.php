<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class SlackTest extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['slack'];
    }

    /**
     * Get the Slack representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return SlackMessage
     */
    public function toSlack()
    {
        $settings = Setting::getSettings();
        return (new SlackMessage)
            ->from($settings->slack_botname, ':heart:')
            ->to($settings->slack_channel)
            ->image('https://snipeitapp.com/favicon.ico')
            ->content('Oh hai! Looks like your Slack integration with Snipe-IT is working!');
    }

}
