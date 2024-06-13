<?php

namespace App\Livewire;

# waiting for v3 release (Declaration of WebSocket\Client::setLogger(Psr\Log\LoggerInterface $logger): WebSocket\Client must be compatible with Psr\Log\LoggerAwareInterface::setLogger(Psr\Log\LoggerInterface $logger): void)

// TODO: implement joining, only if user clicks chat in ui, to reduce server load and loading time (far future)

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use WebSocket\Client as WebSocketClient;
use WebSocket\Middleware as WebSocketMiddleware;
use WebSocket\Connection as WebSocketConnection;
use WebSocket\Message\Message as WebSocketMessage;
use InvalidArgumentException;

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

    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }
}

class ChatMessageDTO
{
    public String $id = '';
    public String $email = '';
    public String $timestamp = '';
    public String $text = '';

    public function __construct(String $json)
    {
        $data = json_decode($json, true);

        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }
}

class ChatConnectionsDTO
{
    /**
     * @var array<String, array {
     *     'info' => `array {
     *         'detail' => ChatDTO,
     *         'messages' => ChatMessageDTO[]
     *     },
     *     'websocket' => WebSocketClient,
     *     'lastPing' => float
     * }>
     */
    private array $chats;

    public function setChats(array $chats, Bool $settingMessages = false, Bool $settingWebSocket = false): self
    {
        foreach ($chats as $chat) {
            if (!isset($chat['info']['detail']) || !$chat['info']['detail'] instanceof ChatDTO) {
                throw new InvalidArgumentException('Invalid chat detail');
            }
            if ($settingMessages) {
                if (!isset($chat['info']['messages']) || !is_array($chat['info']['messages'])) {
                    throw new InvalidArgumentException('Invalid chat messages');
                }

                foreach ($chat['info']['messages'] as $message) {
                    if (!$message instanceof ChatMessageDTO) {
                        throw new InvalidArgumentException('Invalid chat message');
                    }
                }
            }
            if ($settingWebSocket) {
                if (!isset($chat['websocket']) || !$chat['websocket'] instanceof WebSocketClient) {
                    throw new InvalidArgumentException('Invalid websocket client');
                }
            }
        }

        $this->chats = $chats;
        return $this;
    }

    public function getChats(): array
    {
        return $this->chats;
    }

    public function getChat(String $monitorId): array
    {
        return $this->chats[$monitorId];
    }

    public function getWebsocket(String $monitorId): object
    {
        return $this->chats[$monitorId]['websocket'];
    }

    public function getDetail(String $monitorId): object
    {
        return $this->chats[$monitorId]['info']['detail'];
    }

    public function getMessages(String $monitorId): object
    {
        return $this->chats[$monitorId]['info']['messages'];
    }

    public function addMessage(String $monitorId, ChatMessageDTO $messages): self
    {
        array_push($this->chats[$monitorId]['info']['messages'], $messages);
        return $this;
    }

    public function getLastPing(String $monitorId): float
    {
        return $this->chats[$monitorId]['lastPing'];
    }

    public function setLastPing(String $monitorId, float $lastPing): self
    {
        $this->chats[$monitorId]['lastPing'] = $lastPing;
        return $this;
    }
}

class ChatClient extends Component
{
    public String $email = 'username@domain.tld';
    public String $password = 'password';
    public String | null $token = null;

    private ?ChatConnectionsDTO $chats = null;

    public function mount()
    {
        $this->chats = new ChatConnectionsDTO();

        $this->login();
    }

    public static function getChatUrl($monitorHash, $auth)
    {
        return "ws://localhost:6969/chat/$monitorHash?auth=$auth";
    }

    public static function getMonitorOfClient(WebSocketClient $client)
    {
        $url = $client->__toString();
        $parts = parse_url($url);
        $path = explode('/', $parts['path']);
        $monitorHash = end($path);

        return rawurldecode($monitorHash);
    }

    public static function parseChats($chats)
    {
        $chatMap = [];

        foreach ($chats as $chat) {
            $monitorHash = $chat['monitor_hash'];
            $chatMap[$monitorHash] = [
                'info' => [
                    'detail' => new ChatDTO($chat),
                    'messages' => [],
                ],
                'websocket' => null,
            ];
        }

        return $chatMap;
    }

    public function login()
    {
        $response = Http::post('http://localhost:6969/login', [
            'email' => $this->email,
            'password' => $this->password
        ]);

        if ($response && $response->ok()) {
            $json = $response->json();
            if (isset($json['token']) && isset($json['chats'])) {
                $json = $response->json();
                $this->token = $json['token'];
                $chats = $json['chats'];

                $chatMap = self::parseChats($chats);
                $this->chats->setChats($chatMap);

                $this->connectToWebSockets();
            } else {
                echo 'Error (invalid response body): ' . $response->body();
            }
        } else {
            echo 'Error (response is not ok): ' . $response->body();
        }
    }

    public function connectToWebSockets()
    {
        $modifiedChats = [];
        foreach ($this->chats->getChats() as $chat) {
            $monitorId = $chat['info']['detail']->monitor_hash;
            $monitor_hash = rawurlencode($monitorId);
            $wsUri = self::getChatUrl($monitor_hash, $this->token);

            $modifiedChats[$monitorId] = $chat;
            $modifiedChats[$monitorId]['websocket'] = new WebSocketClient($wsUri);
            $this->chats->setChats($modifiedChats);

            $modifiedChats[$monitorId]['websocket']
                ->addMiddleware(new WebSocketMiddleware\CloseHandler())
                ->onText(function (WebSocketClient $client, WebSocketConnection $connection, WebSocketMessage $message) {
                    if (!($message->getContent() === 'ping') && !($message->getContent() === 'pong')) {
                        $monitorId = self::getMonitorOfClient($client);
                        $chatMessage = new ChatMessageDTO($message->getContent());
                        $this->chats->addMessage($monitorId, $chatMessage);

                        $messageAsHTML = "<span class='text-right'>{$message->getContent()}</span>";
                        $this->dispatch('messageReceived', [
                            'monitorId' => $monitorId,
                            'message' => $messageAsHTML
                        ]);
                    }
                })
                ->onConnect(function (WebSocketClient $client, WebSocketConnection $connection) {
                    $messageAsHTML = "<span class='sticky top-0 left-0 px-2 font-black bg-gray-300 text-lime-900'>CONNECTED</span>";
                    $this->dispatch('messageReceived', $messageAsHTML);

                    $monitorId = self::getMonitorOfClient($client);
                    $this->chats->setLastPing($monitorId, microtime(true));
                    /* $monitorId = self::getMonitorOfClient($client);
                while ($this->chats->getWebsocket($monitorId)->isConnected()) {
                    self::sendMessage($monitorId, 'ping');
                    sleep(5);
                } */
                })
                ->onClose(function (WebSocketClient $client, WebSocketConnection $connection) {
                    $messageAsHTML = "<span class='sticky top-0 left-0 px-2 font-black text-red-800 bg-gray-300'>CLOSED</span>";
                    $this->dispatch('messageReceived', $messageAsHTML);
                })
                ->onError(function (WebSocketClient $client, WebSocketConnection | null $connection) {
                    $messageAsHTML = "<span class='sticky top-0 left-0 px-2 font-black text-red-800 bg-gray-300'>ERROR</span>";
                    $this->dispatch('messageReceived', $messageAsHTML);
                })
                ->setTimeout(5)
                /* ->onTick(function (WebSocketClient $client, WebSocketConnection $connection) {
                $monitorId = self::getMonitorOfClient($client);
                $connection->text('ping');
                }) */
                ->onTick(function (WebSocketClient $client) {
                    /* $client->ping('ping'); doesn't work */
                    self::ping($client);
                })
                ->start();
        }

        dump($this->chats);
    }

    public function ping(WebSocketClient $client)
    {
        $monitorId = self::getMonitorOfClient($client);
        self::sendMessage($monitorId, 'ping');
        $this->chats->setLastPing($monitorId, microtime(true));
    }

    public function sendMessage(String $monitorId, String $message)
    {
        $websocket = $this->chats->getWebsocket($monitorId);

        if (
            !isset($websocket) ||
            !$websocket instanceof WebSocketClient ||
            !$websocket->isConnected()
        ) {
            return;
        }

        $websocket->text($message);

        $messageAsHTML = "<span class='text-right'>{$message}</span>";
        $this->dispatch('messageSent', [
            'monitorId' => $monitorId,
            'message' => $messageAsHTML
        ]);
    }

    public function closeConnection(String $monitorId)
    {
        if (isset($this->chats[$monitorId]['websocket']) && $this->chats[$monitorId]['websocket'] instanceof WebSocketClient) {
            $this->chats[$monitorId]['websocket']->close();
        }
    }

    public function render()
    {
        return view('livewire.chat.client');
    }
}
