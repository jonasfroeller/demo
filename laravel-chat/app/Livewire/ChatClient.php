<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use WebSocket\Client as WebSocketClient; # waiting for v3 release (Declaration of WebSocket\Client::setLogger(Psr\Log\LoggerInterface $logger): WebSocket\Client must be compatible with Psr\Log\LoggerAwareInterface::setLogger(Psr\Log\LoggerInterface $logger): void)

class ChatClient extends Component
{
    public $message;
    public $monitorId = 'w32tgse';
    public $token;
    public $websocket;
    public $output = '';

    public function mount()
    {
        $this->login();
    }

    public function login()
    {
        $response = Http::post('http://127.0.0.1:6969/login', [
            'email' => 'j.froe@gmx.at',
            'password' => 'password',
            'onitor_id' => $this->monitorId,
        ]);

        $this->token = $response->body();
        $this->connectToWebSocket();
    }

    public function connectToWebSocket()
    {
        $wsUri = "ws://localhost:6969/chat/{$this->monitorId}?auth={$this->token}";
        $this->websocket = new WebSocketClient($wsUri);

        $this->websocket->onOpen = function () {
            $this->output.= "<span class='sticky top-0 left-0 px-2 font-black bg-gray-300 text-lime-900'>CONNECTED</span>";
            $this->sendMessage('ping');
            /* pingInterval = setInterval(function () { // TODO: implement ping
                $this->sendMessage('ping');
            }, 5000); */
        };

        $this->websocket->onClose = function () {
            $this->output.= "<span class='sticky top-0 left-0 px-2 font-black text-red-800 bg-gray-300'>CLOSED</span>";
            /* clearInterval($this->pingInterval); */ // TODO: implement ping
        };

        $this->websocket->onMessage = function ($e) {
            $this->output.= "<span class='text-right'>{$e->getContent()}</span>";
        };

        $this->websocket->onError = function () {
            $this->output.= "<span class='sticky top-0 left-0 px-2 font-black text-red-800 bg-gray-300'>ERROR</span>";
        };
    }

    public function sendMessage($message)
    {
        if ($this->websocket && $message) {
            $this->output.= "<span class='text-left'>{$message}</span>";
            $this->websocket->send($message);
            $this->message = '';
        }
    }

    public function render()
    {
        return view('livewire.chat-client');
    }
}
