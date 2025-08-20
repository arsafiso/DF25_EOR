<script setup>

import { useRoute, useRouter } from 'vue-router';
import { ref, computed, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import axios from '@/libs/axios';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const classificacaoId = computed(() => route.params.id);
const isEditing = computed(() => classificacaoId.value !== undefined);
const formTitle = computed(() => (isEditing.value ? 'Editar Classificação' : 'Criar Classificação'));

const nome = ref('');
const tipo = ref('estadual');
const descricao = ref('');
const validationErrors = ref({});

onMounted(async () => {
    if (isEditing.value) {
        try {
            const response = await axios.get(`/classificacao-estrutura/${classificacaoId.value}`);
            if (response.status === 200) {
                nome.value = response.data.nome;
                tipo.value = response.data.tipo;
                descricao.value = response.data.descricao;
            } else {
                toast.add({ severity: 'error', summary: 'Erro', detail: 'Classificação não encontrada', life: 3000 });
                router.push('/classificacao');
            }
        } catch (e) {
            toast.add({ severity: 'error', summary: 'Erro', detail: 'Falha ao carregar Classificação', life: 3000 });
            router.push('/classificacao');
        }
    }
});

const validateForm = () => {
    const errors = {};
    if (!nome.value || nome.value.trim() === '') {
        errors.nome = 'O nome da Classificação é obrigatório';
    } else if (nome.value.length < 3) {
        errors.nome = 'O nome deve ter pelo menos 3 caracteres';
    }
    if (!tipo.value || (tipo.value !== 'estadual' && tipo.value !== 'federal')) {
        errors.tipo = 'Selecione o tipo: federal ou estadual';
    }
    validationErrors.value = errors;
    return Object.keys(errors).length === 0;
};

const saveClassificacao = async () => {
    if (!validateForm()) {
        toast.add({
            severity: 'error',
            summary: 'Erro de Validação',
            detail: 'Por favor, verifique os erros no formulário',
            life: 3000
        });
        return;
    }

    const payload = {
        nome: nome.value,
        tipo: tipo.value,
        descricao: descricao.value
    };

    try {
        if (isEditing.value) {
            const response = await axios.put(`/classificacao-estrutura/${classificacaoId.value}`, payload);
            if (response.status === 200) {
                toast.add({
                    severity: 'success',
                    summary: 'Sucesso',
                    detail: 'Classificação atualizada com sucesso',
                    life: 3000
                });
                router.push('/classificacao');
            } else {
                toast.add({ severity: 'error', summary: 'Erro', detail: 'Erro ao atualizar Classificação', life: 3000 });
            }
        } else {
            const response = await axios.post('/classificacao-estrutura', payload);
            if (response.status === 201 || response.status === 200) {
                toast.add({
                    severity: 'success',
                    summary: 'Sucesso',
                    detail: 'Classificação criada com sucesso',
                    life: 3000
                });
                router.push('/classificacao');
            } else {
                toast.add({ severity: 'error', summary: 'Erro', detail: 'Erro ao criar Classificação', life: 3000 });
            }
        }
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Falha ao salvar Classificação', life: 3000 });
    }
};


const cancel = () => {
    router.push('/classificacao');
};

const breadcrumbItems = ref([
    { label: 'Dashboard', url: '/' },
    { label: 'Classificações', url: '/classificacao' },
    { label: isEditing.value ? 'Editar Classificação' : 'Criar Classificação', url: null }
]);

// Usuários - Aba
const filteredUsers = computed(() => {
    return allUsers.value.filter(user => !users.value.some(u => u.id === user.id));
});

const addUser = () => {
    openUserDialog.value = true;
    selectedUser.value = null;
    validationErrors.value.user = null;
};

const saveUser = async () => {
    if (!selectedUser.value) {
        validationErrors.value.user = 'Selecione um usuário';
        return;
    }
    try {
        await axios.post(`/companies/${companyId.value}/users`, { user_id: selectedUser.value.id });
        users.value.push(selectedUser.value);
        toast.add({ severity: 'success', summary: 'Sucesso', detail: 'Usuário adicionado à Classificação Estadual', life: 3000 });
        openUserDialog.value = false;
        selectedUser.value = null;
        validationErrors.value.user = null;
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Erro ao adicionar usuário', life: 3000 });
    }
};

const confirmDeleteUser = (user) => {
    confirm.require({
        message: `Tem certeza que deseja remover ${user.name || user.nome}?`,
        header: 'Confirmação',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Remover',
        rejectLabel: 'Cancelar',
        accept: async () => {
            try {
                await axios.delete(`/companies/${companyId.value}/users/${user.id}`);
                users.value = users.value.filter(u => u.id !== user.id);
                toast.add({ severity: 'success', summary: 'Sucesso', detail: 'Usuário removido da Classificação Estadual', life: 3000 });
            } catch (e) {
                toast.add({ severity: 'error', summary: 'Erro', detail: 'Erro ao remover usuário', life: 3000 });
            }
        }
    });
};
</script>

<template>
    <div class="classificacao-edit p-4">
        <div>
            <h1 class="text-3xl font-medium text-900 m-0">{{ formTitle }}</h1>
            <Breadcrumb :model="breadcrumbItems" class="my-6 text-sm rounded-lg" />
        </div>

        <Card class="mb-4">
            <template #content>
                <form class="p-fluid">
                    <div class="col-span-12 md:col-span-6 mb-4">
                        <label for="nome" class="block mb-2">Nome</label>
                        <InputText fluid id="nome" v-model="nome" :class="{ 'p-invalid': validationErrors.nome }" />
                        <small v-if="validationErrors.nome" class="p-error text-red-500">{{ validationErrors.nome }}</small>
                    </div>
                    <div class="col-span-12 md:col-span-6 mb-4">
                        <label for="tipo" class="block mb-2">Tipo</label>
                        <Dropdown id="tipo" v-model="tipo" :options="[{ label: 'Federal', value: 'federal' }, { label: 'Estadual', value: 'estadual' }]" optionLabel="label" optionValue="value" placeholder="Selecione o tipo" />
                        <small v-if="validationErrors.tipo" class="p-error text-red-500">{{ validationErrors.tipo }}</small>
                    </div>
                    <div class="col-span-12 mb-4">
                        <label for="descricao" class="block mb-2">Descrição</label>
                        <Textarea fluid id="descricao" v-model="descricao" rows="3" :class="{ 'p-invalid': validationErrors.descricao }" />
                        <small v-if="validationErrors.descricao" class="p-error">{{ validationErrors.descricao }}</small>
                    </div>
                </form>
            </template>
            <template #footer>
                <div class="flex justify-content-end gap-3">
                    <Button label="Cancelar" severity="secondary" outlined @click="cancel" type="button" />
                    <Button label="Salvar" icon="pi pi-save" type="button" @click="saveClassificacao" />
                </div>
            </template>
        </Card>
    </div>
</template>

<style scoped>
.structures-edit h1 {
    margin-bottom: 1.5rem;
}

:deep(.p-tabview-nav) {
    justify-content: center;
}

@media (max-width: 768px) {
    :deep(.p-tabview-nav li) {
        margin: 0;
        flex: 1;
    }

    :deep(.p-tabview-nav li .p-tabview-nav-link) {
        padding: 0.5rem;
    }
}

.custom-file-upload-wrapper {
    display: flex;
    align-items: center;
}
.custom-file-upload-btn {
    display: inline-flex;
    align-items: center;
    background: #2196f3;
    color: #fff;
    padding: 0.5rem 1.25rem;
    border-radius: 0.375rem;
    cursor: pointer;
    font-weight: 500;
    transition: background 0.2s;
    border: none;
    outline: none;
    font-size: 1rem;
}
.custom-file-upload-btn:hover,
.custom-file-upload-btn:focus {
    background: #1769aa;
    color: #fff;
}
input[type="file"].hidden {
    display: none;
}
table {
    border-collapse: collapse;
}
th, td {
    border: 1px solid #e5e7eb;
    padding: 0.5rem;
    text-align: left;
}
summary {
    outline: none;
}
</style>
