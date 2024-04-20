import type { Actions } from './$types';

export const actions = {
    default: async (event) => {
        const formData = await event.request.formData();
        const response = await fetch('http://127.0.0.1:6969/login', {
            method: 'POST',
            body: formData,
        });

        const json = await response.json();
        return {
            ...json.body,
            token: json.token,
        };
    },
} satisfies Actions;
