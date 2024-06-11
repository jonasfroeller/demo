<?php

namespace App\Livewire;

 # waiting for v3 release (Declaration of WebSocket\Client::setLogger(Psr\Log\LoggerInterface $logger): WebSocket\Client must be compatible with Psr\Log\LoggerAwareInterface::setLogger(Psr\Log\LoggerInterface $logger): void)

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use WebSocket\Client as WebSocketClient;
use WebSocket\Middleware as WebSocketMiddleware;
use WebSocket\Connection as WebSocketConnection;
use WebSocket\Message\Message as WebSocketMessage;

class ChatDTO
{
    public $monitor_hash = '';
    public $organization_name = '';
    public $type = '';
    public $title = '';
    public $short_description = '';
    public $public = false;
    public $created_at = '';
    public $updated_at = '';
    public $project_url = '';
}

class ChatClient extends Component
{
    public WebSocketMessage $message;
    public String $monitorId = '$2y$12$W3pHWdAtePn1wjCm4.t4xO9lY9jOcu8/5SC0bDEsaAfSB8pKA5k.K';
    public String $token;
    /** @var ChatDTO[] */
    public array $chats;
    public String $output = '';
    private WebSocketClient $websocket;

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
        $this->chats = $json['chats']; // TODO: fix Property type not supported in Livewire for property: [{}]

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
                $this->output .= "<span class='text-right'>{$message->getContent()}</span>";
                $this->emit('messageReceived', $message->getContent());
            })
            ->onConnect(function (WebSocketClient $client, WebSocketConnection $connection) {
                $this->output .= "<span class='sticky top-0 left-0 px-2 font-black bg-gray-300 text-lime-900'>CONNECTED</span>";
            })
            ->onClose(function (WebSocketClient $client, WebSocketConnection $connection) {
                $this->output .= "<span class='sticky top-0 left-0 px-2 font-black text-red-800 bg-gray-300'>CLOSED</span>";
            })
            ->onError(function (WebSocketClient $client, WebSocketConnection $connection) {
                $this->output .= "<span class='sticky top-0 left-0 px-2 font-black text-red-800 bg-gray-300'>ERROR</span>";
            })
            ->start();
    }

    public function sendMessage($message)
    {
        if ($this->websocket && $message) {
            $this->output .= "<span class='text-left'>{$message}</span>";
            $this->websocket->text($message);
            $this->message = '';
        }
    }

    public function closeConnection()
    {
        if ($this->websocket) {
            $this->websocket->close();
        }
    }

    public function render()
    {
        return view('livewire.chat.client');
    }
}
