<script setup>
import { useUserStore } from '@/stores/userStore';
import { computed } from 'vue';
import AppMenuItem from './AppMenuItem.vue';
import { useRouter } from 'vue-router'; // Adicione este import

const userStore = useUserStore();
const model = computed(() => userStore.menu);

const router = useRouter(); // Inicialize o router

function logout() {
    localStorage.removeItem('token');
    router.push('/auth/login');
}
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="item">
            <app-menu-item v-if="!item.separator" :item="item" :index="i"></app-menu-item>
            <li v-if="item.separator" class="menu-separator"></li>
        </template>
    </ul>
    <!-- Botão de logout fixo na parte de baixo do menu -->
    <div class="logout-menu-btn">
        <button @click="logout" class="p-button-sm" style="width: 100%; text-align: left;">
            <i class="pi pi-sign-out" style="margin-right: 0.5rem"></i>
            <span style>Sair</span>
        </button>
    </div>
</template>

<style lang="scss" scoped>
.logout-menu-btn {
    position: absolute;
    bottom: 1rem;
    left: 0;
    width: 100%;
    padding: 1rem 2.5rem;
    box-sizing: border-box;
    text-align: center;
    background: transparent;
}
</style>
