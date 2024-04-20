<script lang="ts">
    import type { ActionData } from "./$types";

    export let form: ActionData;
</script>

<form method="POST">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" value="john.doe@me.com" />
    <label for="password">Password</label>
    <input type="password" id="password" name="password" value="password" />
    <label for="monitor_id">Monitor</label>
    <input
        type="text"
        id="monitor_id"
        name="monitor_id"
        value="1zhi3hi42ihsa43%"
    />
    <button type="submit">Submit</button>
</form>

{#if form}
    Logged you in as {form.email} on monitor {form.monitor_id} <br /><br />
    INFO: {JSON.stringify(form, null, 2)}
{/if}
