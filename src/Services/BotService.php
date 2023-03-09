<?php

namespace jangkardev\Services;

class BotService
{
    protected $body;
    protected $service;
    protected $type = 'message';
    protected $to = 'admin';
    public function __construct($type)
    {
        $this->service = new Service($type . '_BOT');
    }

    public function to($user)
    {
        $this->to = $user;
        return $this;
    }

    public function message($message)
    {
        $this->type = 'message';
        $this->body = $message;
        return $this;
    }

    public function sticker($sticker)
    {
        $this->type = 'sticker';
        $this->body = $sticker;
        return $this;
    }

    public function send()
    {
        if (env('APP_ENV') == 'Production') {
            $to = $this->to;
            $type = $this->type;
            $body = $this->body;
            try {
                $this->service->post('message', [
                    'to'   => $to,
                    'body' => $body,
                    'type' => $type
                ]);
            } catch (\Throwable $th) {
                // report($th);
            }
        }
        return $this;
    }
}
