import type { Actions } from './$types';
import { connectToChat, sendMessage } from '$lib/server/chat';
import WebSocket from "ws";

let websocketConnection: WebSocket;
export const actions = {
    "authenticate": async (event) => {
        const formData = await event.request.formData();
        const response = await fetch('http://127.0.0.1:6969/login', {
            method: 'POST',
            body: formData
        });

        const token = await response.text();
        const email = formData.get("email") as string;
        const monitor_id = formData.get("monitor_id") as string;
        websocketConnection = connectToChat(monitor_id, token);

        return {
            email,
            monitor_id
        };
    },
    "send-message": async (event) => {
        const formData = await event.request.formData();
        const message = formData.get("message") as string;
        const messages = sendMessage(message, websocketConnection);

        return {
            messages
        };
    },
} satisfies Actions;
