<script setup>
import { onBeforeMount } from 'vue';
import { useUserStore } from './stores/userStore';

const userStore = useUserStore();

onBeforeMount(() => {
    const token = localStorage.getItem('eor__token') || null;
    if (!token) return;

    userStore.init();
});
</script>

<template>
    <router-view />
</template>

<style scoped></style>
