<?php

namespace App\Services;

class BotService
{
    protected $body;
    protected $service;
    protected $type = 'message';
    protected $to = 'admin';
    public function __construct($type)
    {
        $this->service = new Service($type.'_BOT');
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
        $to = $this->to;
        $type = $this->type;
        $body = $this->body;
        $this->service->post('message', [
            'to'   => $to,
            'body' => $body,
            'type' => $type
        ]);
        return $this;
    }
}
