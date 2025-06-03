<template>
    <div class="flex flex-col items-center justify-center">
        <svg class="h-10 w-10" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle
                class="text-gray-300"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
            />
            <path
                class="text-primary"
                fill="currentColor"
                d="M12 2a10 10 0 0 1 10 10h-4a6 6 0 0 0-6-6V2z"
            >
                <animateTransform
                    attributeName="transform"
                    type="rotate"
                    from="0 12 12"
                    to="360 12 12"
                    dur="1s"
                    repeatCount="indefinite"
                />
            </path>
        </svg>
    </div>
</template>
<script setup lang="ts">
// No script needed
</script>
<style scoped></style>
