<script setup lang="ts">
const year = new Date().getFullYear();
</script>

<template>
    <div class="bg-surface-200 dark:bg-surface-800 mt-auto p-2 md:p-2.5">
        <div class="footer pl-2 md:pl-2.5">&copy; {{ year }} NTJ Application Studio</div>
    </div>
</template>
