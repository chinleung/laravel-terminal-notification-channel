<?php

namespace ChinLeung\TerminalNotificationChannel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Jobs\RedisJob;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class Router
{
    /**
     * Create a new Terminal notification router channel.
     */
    public function __construct(
        protected Application $application,
    ) {
        //
    }

    /**
     * Compile the command to render the notification.
     */
    protected function compileCommand(array $options): string
    {
        $command = Arr::get($options, 'path');

        foreach ($options as $option => $value) {
            $command .= sprintf(
                ' -%s \'%s\'',
                $option,
                addslashes($value),
            );
        }

        return $command;
    }

    /**
     * Compile the options to render the notification.
     */
    protected function compileOptions(Notification $notification): array
    {
        $config = $this->application->config;

        $options = [
            'path' => $config->get('terminal-notification.path'),
            'title' => $config->get('terminal-notification.title'),
            'appIcon' => $config->get('terminal-notification.icon'),
            'sound' => $config->get('terminal-notification.sound'),
        ];

        if (method_exists($notification, 'getEvent')) {
            $event = $notification->getEvent();

            if (property_exists($event, 'job')) {
                $job = $event->job;

                $options['title'] = $job->resolveName();
            }

            if (property_exists($event, 'exception')) {
                $exception = $event->exception;

                $options['message'] = $exception->getMessage();

                if (isset($job) && $this->isHorizonJob($job)) {
                    $options['open'] = $this->application
                        ->make(UrlGenerator::class)
                        ->to("horizon/failed/{$job->getJobId()}");
                }
            }
        }

        return array_filter($options);
    }

    /**
     * Check if the given job is dispatched for Horizon.
     */
    protected function isHorizonJob(Job $job): bool
    {
        if (! $job instanceof RedisJob) {
            return false;
        }

        return get_class($job->getRedisQueue()) === 'Laravel\\Horizon\\RedisQueue';
    }

    /**
     * Send the notification.
     */
    public function send($notifiable, Notification $notification): void
    {
        if ($this->application->environment() !== 'local') {
            return;
        }

        $options = $this->compileOptions($notification);

        if (! Arr::has($options, 'message')) {
            return;
        }

        $command = $this->compileCommand($options);

        Log::debug("Terminal command :: {$command}");

        Process::run($command);
    }
}
