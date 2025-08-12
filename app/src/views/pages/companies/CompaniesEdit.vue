<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import axios from '@/libs/axios';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const confirm = useConfirm();

const companyId = computed(() => route.params.id);
const isEditing = computed(() => companyId.value !== undefined);
const formTitle = computed(() => (isEditing.value ? 'Editar Empresa' : 'Criar Empresa'));

const nome = ref('');
const descricao = ref('');
const validationErrors = ref({});

// Usuários relacionados à empresa
const users = ref([]);
const allUsers = ref([]);
const selectedUser = ref(null);
const openUserDialog = ref(false);

onMounted(async () => {
    if (isEditing.value) {
        try {
            const response = await axios.get(`/companies/${companyId.value}`);
            if (response.status === 200) {
                nome.value = response.data.nome;
                descricao.value = response.data.descricao;
            } else {
                toast.add({ severity: 'error', summary: 'Erro', detail: 'Empresa não encontrada', life: 3000 });
                router.push('/companies');
            }
            // Carrega usuários da empresa
            const usersResponse = await axios.get(`/companies/${companyId.value}/users`);
            users.value = usersResponse.data || [];
            // Carrega todos os usuários disponíveis para adicionar
            const allUsersResponse = await axios.get('/users');
            allUsers.value = Array.isArray(allUsersResponse.data.data) ? allUsersResponse.data.data : [];
        } catch (e) {
            toast.add({ severity: 'error', summary: 'Erro', detail: 'Falha ao carregar empresa', life: 3000 });
            router.push('/companies');
        }
    }
});

const validateForm = () => {
    const errors = {};
    if (!nome.value || nome.value.trim() === '') {
        errors.nome = 'O nome da empresa é obrigatório';
    } else if (nome.value.length < 3) {
        errors.nome = 'O nome deve ter pelo menos 3 caracteres';
    }
    validationErrors.value = errors;
    return Object.keys(errors).length === 0;
};

const saveCompany = async () => {
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
        descricao: descricao.value
    };

    try {
        if (isEditing.value) {
            const response = await axios.put(`/companies/${companyId.value}`, payload);
            if (response.status === 200) {
                toast.add({
                    severity: 'success',
                    summary: 'Sucesso',
                    detail: 'Empresa atualizada com sucesso',
                    life: 3000
                });
                router.push('/companies');
            } else {
                toast.add({ severity: 'error', summary: 'Erro', detail: 'Erro ao atualizar empresa', life: 3000 });
            }
        } else {
            const response = await axios.post('/companies', payload);
            if (response.status === 201 || response.status === 200) {
                toast.add({
                    severity: 'success',
                    summary: 'Sucesso',
                    detail: 'Empresa criada com sucesso',
                    life: 3000
                });
                router.push('/companies');
            } else {
                toast.add({ severity: 'error', summary: 'Erro', detail: 'Erro ao criar empresa', life: 3000 });
            }
        }
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Falha ao salvar empresa', life: 3000 });
    }
};

const cancel = () => {
    router.push('/companies');
};

const breadcrumbItems = ref([
    { label: 'Dashboard', url: '/' },
    { label: 'Empresas', url: '/companies' },
    { label: isEditing.value ? 'Editar Empresa' : 'Criar Empresa', url: null }
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
        toast.add({ severity: 'success', summary: 'Sucesso', detail: 'Usuário adicionado à empresa', life: 3000 });
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
                toast.add({ severity: 'success', summary: 'Sucesso', detail: 'Usuário removido da empresa', life: 3000 });
            } catch (e) {
                toast.add({ severity: 'error', summary: 'Erro', detail: 'Erro ao remover usuário', life: 3000 });
            }
        }
    });
};
</script>

<template>
    <div class="companies-edit p-4">
        <div>
            <h1 class="text-3xl font-medium text-900 m-0">{{ formTitle }}</h1>
            <Breadcrumb :model="breadcrumbItems" class="my-6 text-sm rounded-lg" />
        </div>

        <Card class="mb-4">
            <template #content>
                <form class="p-fluid">
                    <Tabs value="0">
                        <TabList>
                            <Tab value="0">Informações</Tab>
                            <Tab v-if="isEditing" value="1">Usuários</Tab>
                        </TabList>
                        <TabPanels>
                            <TabPanel value="0">
                                <div class="col-span-12 md:col-span-6 mb-4">
                                    <label for="nome" class="block mb-2">Nome</label>
                                    <InputText fluid id="nome" v-model="nome" :class="{ 'p-invalid': validationErrors.nome }" />
                                    <small v-if="validationErrors.nome" class="p-error text-red-500">{{ validationErrors.nome }}</small>
                                </div>
                                <div class="col-span-12 mb-4">
                                    <label for="descricao" class="block mb-2">Descrição</label>
                                    <Textarea fluid id="descricao" v-model="descricao" rows="3" :class="{ 'p-invalid': validationErrors.descricao }" />
                                    <small v-if="validationErrors.descricao" class="p-error">{{ validationErrors.descricao }}</small>
                                </div>
                            </TabPanel>
                            <TabPanel v-if="isEditing" value="1">
                                <div class="grid mb-4">
                                    <div class="col-span-6">
                                        <Button icon="pi pi-plus" label="Adicionar Usuário" class="mt-2" @click="addUser" />
                                    </div>
                                </div>
                                <DataTable :value="users" responsiveLayout="scroll" class="p-datatable-sm" stripedRows>
                                    <Column field="name" header="Nome">
                                        <template #body="slotProps">
                                            {{ slotProps.data.name || slotProps.data.nome }}
                                        </template>
                                    </Column>
                                    <Column field="email" header="Email" />
                                    <Column header="Ações" style="width: 8rem">
                                        <template #body="slotProps">
                                            <Button icon="pi pi-trash" @click="confirmDeleteUser(slotProps.data)" severity="danger" />
                                        </template>
                                    </Column>
                                </DataTable>
                            </TabPanel>
                        </TabPanels>
                    </Tabs>
                </form>
            </template>
            <template #footer>
                <div class="flex justify-content-end gap-3">
                    <Button label="Cancelar" severity="secondary" outlined @click="cancel" type="button" />
                    <Button label="Salvar" icon="pi pi-save" type="button" @click="saveCompany" />
                </div>
            </template>
        </Card>

        <Dialog v-model:visible="openUserDialog" :header="`Adicionar Usuário`" :modal="true" :style="{ width: '20vw' }" :draggable="false">
            <div class="grid">
                <div class="col-span-12 mb-4">
                    <label for="user" class="block mb-2">Usuário</label>
                    <Select filter fluid id="user" v-model="selectedUser" :options="filteredUsers" optionLabel="name" placeholder="Selecione um usuário" />
                    <small v-if="validationErrors.user" class="block text-red-500 mt-2">{{ validationErrors.user }}</small>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <Button type="button" label="Cancelar" severity="secondary" @click="openUserDialog = false"></Button>
                <Button type="button" label="Adicionar" icon="pi pi-plus" @click="saveUser" />
            </div>
        </Dialog>
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
