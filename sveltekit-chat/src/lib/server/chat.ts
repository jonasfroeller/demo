import WebSocket from "ws";

let pingInterval: string | number | NodeJS.Timeout | undefined;
let messages: string[] = [];
export function connectToChat(monitor_id: string, token: string): WebSocket {
    const wsUri = "ws://localhost:6969/chat/" + monitor_id + '?auth=' + token;
    const ws = new WebSocket(wsUri); // wss

    ws.on("open", function open() {
        ws.send("ping");
        pingInterval = setInterval(() => {
            ws.send("ping");
        }, 5000);

        messages.push("OPENED CONNECTION");
    });

    ws.on("close", function close() {
        clearInterval(pingInterval);
        messages.push("CLOSED CONNECTION");
    });

    ws.on("message", function message(data) {
        console.log("RECEIVED: %s", data);
        messages.push(data.toString());
    });

    ws.on("error", function error(data) {
        messages.push("ERROR IN CONNECTION");
    });

    return ws;
}

export function sendMessage(message: string, ws: WebSocket): string[] {
    const trimmedMessage = message.trim();

    if (ws/*  && websocket.readyState === WebSocket.OPEN */) {
        if (trimmedMessage) {
            if (trimmedMessage !== "ping") messages.push(trimmedMessage);
            ws.send(trimmedMessage);
        }
    }

    return messages;
}
