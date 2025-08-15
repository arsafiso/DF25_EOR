<script setup>
import axios from '@/libs/axios';
import { useUserStore } from '@/stores/userStore';

import { ref, watch } from 'vue';
import { useRouter } from 'vue-router';

const userStore = useUserStore();

const router = useRouter();

const email = ref('');
const password = ref('');
const checked = ref(false);
const validationErrors = ref({});

const login = async () => {
    try {
        await axios.post('/login', {
            email: email.value,
            password: password.value
        });
        // Redireciona ou faz o que precisa após login bem-sucedido
    } catch (e) {
        // Se o backend retornar erro de senha, mostre a mensagem personalizada
            validationErrors.value.password = 'E-mail ou senha incorretos. Tente novamente.';
    }
    watch([email, password], () => {
        validationErrors.value.password = '';
    });
};
</script>

<template>
    <div class="bg-surface-50 flex items-center justify-center min-h-screen min-w-[100vw] overflow-hidden">
        <div class="flex flex-col items-center justify-center">
            <div>
                <div class="w-full bg-surface-0 py-20 px-8 sm:px-20 rounded-lg shadow-lg">
                    <div class="text-center mb-8">
                        <div class="text-3xl font-medium mb-4 text-surface-900">EOR</div>
                    </div>

                    <form @submit.prevent="login" class="flex flex-col">
                        <label for="email" class="block text-surface-900 text-xl font-medium mb-2">Email</label>
                        <InputText dense id="email" type="text" placeholder="Email address" class="w-full md:w-[30rem] mb-8" v-model="email" />

                        <label for="password" class="block text-surface-900 font-medium text-xl mb-2">Senha</label>
                        <Password id="password" v-model="password" placeholder="Password" :toggleMask="true" class="mb-4" fluid :feedback="false"></Password>
                        <span v-if="validationErrors.password" class="text-red-500 text-sm mb-4">{{ validationErrors.password }}</span>

                        <div class="flex items-center justify-between mt-2 mb-8 gap-8">
                            <div class="flex items-center">
                                <Checkbox v-model="checked" id="rememberme1" binary class="mr-2"></Checkbox>
                                <label for="rememberme1">
                                    <span class="text-surface-900 font-medium text-sm">Lembrar de mim</span>
                                </label>
                            </div>
                            <span class="font-medium no-underline ml-2 text-right cursor-pointer text-primary">
                                <a href="#" class="text-sm">Esqueci minha senha</a>
                            </span>
                        </div>

                        <Button class="w-full" label="Entrar" icon="pi pi-sign-in" iconPos="left" type="submit" severity="primary"></Button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
