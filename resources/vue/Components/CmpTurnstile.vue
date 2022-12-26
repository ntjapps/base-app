<script setup lang="ts">
import { useMainStore } from "../AppState";

const main = useMainStore();

window.onTurnstileLoad = () => {
    window.turnstile.render("#cf-container", {
        sitekey: import.meta.env.VITE_CHALLENGE_SITE_KEY,
        callback: (token: string) => {
            main.$patch({
                turnstileToken: token,
            });
        },
    });
};

if (typeof window.turnstile === "undefined") {
    const srcJs = document.createElement("script");
    srcJs.setAttribute(
        "src",
        "https://challenges.cloudflare.com/turnstile/v0/api.js?onload=onTurnstileLoad"
    );
    srcJs.async = true;
    srcJs.defer = true;
    document.body.appendChild(srcJs);
}
</script>

<template>
    <div id="cf-container"></div>
</template>
