<script setup lang="ts">
const year = new Date().getFullYear();
</script>

<template>
    <div class="footer-container bg-base-200">
        <div class="footer pl-2.5">
            &copy; {{ year }} NTJ Application Studio
        </div>
    </div>
</template>
