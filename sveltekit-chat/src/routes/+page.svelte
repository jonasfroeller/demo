<script lang="ts">
    import type { ActionData } from "./$types";
	import { enhance } from '$app/forms';

    $: connected = ((form?.email !== undefined) && (form?.monitor_id !== undefined)) || (form?.messages && form?.messages?.length > 0);
    let message = "";

    export let form: ActionData;
    $: monitor_id = (form?.monitor_id && form?.monitor_id != "") ? form.monitor_id : "";
    $: email = (form?.email && form?.email != "") ? form.email : "";
</script>

<form method="POST" action="?/authenticate" use:enhance style={`display: ${connected ? "none" : "flex"};`}>
    <div>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="j.froe@gmx.at" />
    </div>
    <div>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" value="password" />
    </div>
    <div>
        <label for="monitor_id">Monitor</label>
        <input
            type="text"
            id="monitor_id"
            name="monitor_id"
            value="w32tgse"
        />
    </div>
    <button type="submit">Submit</button>
</form>

{#if email && monitor_id}
    <div style="border: 1px solid white; border-radius: 2rem; padding: 1rem; margin-top: 1.5rem; margin-bottom: 1.5rem">Logged you in as {email} on monitor {monitor_id}!</div>
{/if}

<hr style={`display: ${connected ? "none" : "flex"};`}>

<form method="POST" action="?/send-message" use:enhance style="display: flex;">
    <input disabled={!connected} type="text" id="message" name="message" bind:value={message} />
    <button disabled={!connected} type="submit" style="height: fit-content;">Send</button>
</form>

{#each form?.messages ?? [] as message}
    {message}<br>
{/each}
