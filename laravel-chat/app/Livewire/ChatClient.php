<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use WebSocket\Client as WebSocketClient; # waiting for v3 release (Declaration of WebSocket\Client::setLogger(Psr\Log\LoggerInterface $logger): WebSocket\Client must be compatible with Psr\Log\LoggerAwareInterface::setLogger(Psr\Log\LoggerInterface $logger): void)
use WebSocket\Middleware as WebSocketMiddleware;
use WebSocket\Connection as WebSocketConnection;
use WebSocket\Message\Message as WebSocketMessage;

class ChatClient extends Component
{
    public $message;
    public $monitorId = '$2y$12$W3pHWdAtePn1wjCm4.t4xO9lY9jOcu8/5SC0bDEsaAfSB8pKA5k.K';
    public $token;
    public $chats;
    public $websocket;
    public $output = '';

    public function mount()
    {
        $this->login();
    }

    public function login()
    {
        $response = Http::post('http://localhost:6969/login', [
            'email' => 'username@domain.tld',
            'password' => 'password'
        ]);

        $json = $response->json();
        $this->token = $json['token'];
        $this->chats = $json['chats'];
        $this->connectToWebSocket();
    }

    public function connectToWebSocket()
    {
        $monitorId = rawurlencode($this->monitorId);
        $wsUri = "ws://localhost:6969/chat/{$monitorId}?auth={$this->token}";
        $this->websocket = new WebSocketClient($wsUri);

        $this->websocket // TODO: implement ping - ping every 2 or 3 seconds
        ->addMiddleware(new WebSocketMiddleware\CloseHandler())
        ->onText(function (WebSocketClient $client, WebSocketConnection $connection, WebSocketMessage $message) {
            $this->output.= "<span class='text-right'>{$message->getContent()}</span>";
        })
        ->onConnect(function (WebSocketClient $client, WebSocketConnection $connection) { // no onOpen implementation in phrity/websocket
            $this->output.= "<span class='sticky top-0 left-0 px-2 font-black bg-gray-300 text-lime-900'>CONNECTED</span>";
        })
        ->onClose(function (WebSocketClient $client, WebSocketConnection $connection) {
            $this->output.= "<span class='sticky top-0 left-0 px-2 font-black text-red-800 bg-gray-300'>CLOSED</span>";
        })
        ->onError(function (WebSocketClient $client, WebSocketConnection $connection) {
            $this->output.= "<span class='sticky top-0 left-0 px-2 font-black text-red-800 bg-gray-300'>ERROR</span>";
        })
        ->start();
    }

    public function sendMessage($message)
    {
        if ($this->websocket && $message) {
            $this->output.= "<span class='text-left'>{$message}</span>";
            $this->websocket->text($message);
            $this->message = '';
        }
    }

    public function closeConnection()
    {
        $this->websocket->close();
    }

    public function render()
    {
        return view('livewire.chat.client');
    }
}
