<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import axios from '@/libs/axios';
import { useUserStore } from '@/stores/userStore';


const route = useRoute();
const router = useRouter();
const toast = useToast();
const confirm = useConfirm();

const userId = computed(() => route.params.id);
const isEditing = computed(() => userId.value !== undefined);
const formTitle = computed(() => (isEditing.value ? 'Editar Usuário' : 'Criar Usuário'));

const userStore = useUserStore();
const isSuperAdmin = computed(() => userStore.isSuperAdmin);

const name = ref('');
const email = ref('');
const password = ref('');
const role = ref('');
const companyId = ref(null);
const validationErrors = ref({});

const companies = ref([]);
const groups = ref([]);
const allGroups = ref([]);
const selectedGroup = ref(null);
const openGroupDialog = ref(false);

onMounted(async () => {
    // Carrega empresas para seleção
    const companiesResponse = await axios.get('/companies');
    companies.value = Array.isArray(companiesResponse.data) ? companiesResponse.data : [];

    // Carrega todos os grupos de acesso
    const groupsResponse = await axios.get('/grupos-acesso');
    allGroups.value = Array.isArray(groupsResponse.data.data) ? groupsResponse.data.data : [];

    if (isEditing.value) {
        try {
            const response = await axios.get(`/users/${userId.value}`);
            if (response.status === 200) {
                name.value = response.data.name;
                email.value = response.data.email;
                role.value = response.data.role;
                companyId.value = response.data.company_id;
                groups.value = response.data.grupos || [];
            } else {
                toast.add({ severity: 'error', summary: 'Erro', detail: 'Usuário não encontrado', life: 3000 });
                router.push('/users');
            }
        } catch (e) {
            toast.add({ severity: 'error', summary: 'Erro', detail: 'Falha ao carregar usuário', life: 3000 });
            router.push('/users');
        }
    }
});


const validateForm = () => {
    const errors = {};
    if (!name.value || name.value.trim() === '') {
        errors.name = 'O nome é obrigatório';
    }
    if (!email.value || email.value.trim() === '') {
        errors.email = 'O e-mail é obrigatório';
    }
    if (!isEditing.value && (!password.value || password.value.length < 6)) {
        errors.password = 'A senha deve ter pelo menos 6 caracteres';
    }
    validationErrors.value = errors;
    return Object.keys(errors).length === 0;
};

const saveUser = async () => {
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
        name: name.value,
        email: email.value,
        role: role.value,
        company_id: companyId.value,
        grupos: groups.value.map(g => g.id)
    };
    if (!isEditing.value) {
        payload.password = password.value;
    }

    try {
        if (isEditing.value) {
            const response = await axios.put(`/users/${userId.value}`, payload);
            if (response.status === 200) {
                toast.add({
                    severity: 'success',
                    summary: 'Sucesso',
                    detail: 'Usuário atualizado com sucesso',
                    life: 3000
                });
                router.push('/users');
            } else {
                toast.add({ severity: 'error', summary: 'Erro', detail: 'Erro ao atualizar usuário', life: 3000 });
            }
        } else {
            const response = await axios.post('/users', payload);
            if (response.status === 201 || response.status === 200) {
                toast.add({
                    severity: 'success',
                    summary: 'Sucesso',
                    detail: 'Usuário criado com sucesso',
                    life: 3000
                });
                router.push('/users');
            } else {
                toast.add({ severity: 'error', summary: 'Erro', detail: 'Erro ao criar usuário', life: 3000 });
            }
        }
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Falha ao salvar usuário', life: 3000 });
    }
};

const cancel = () => {
    router.push('/users');
};

const breadcrumbItems = ref([
    { label: 'Dashboard', url: '/' },
    { label: 'Usuários', url: '/users' },
    { label: isEditing.value ? 'Editar Usuário' : 'Criar Usuário', url: null }
]);

const filteredGroups = computed(() => {
    console.log('filtered', allGroups.value);
    return allGroups.value;
});

const addGroup = () => {
    openGroupDialog.value = true;
    selectedGroup.value = null;
    validationErrors.value.group = null;
};

const saveGroup = async () => {
    if (!selectedGroup.value) {
        validationErrors.value.group = 'Selecione um grupo';
        return;
    }
    // Aqui você pode fazer uma chamada para associar o grupo ao usuário no backend, se necessário
    groups.value.push(selectedGroup.value);
    toast.add({ severity: 'success', summary: 'Sucesso', detail: 'Grupo adicionado ao usuário', life: 3000 });
    openGroupDialog.value = false;
    selectedGroup.value = null;
    validationErrors.value.group = null;
};

const confirmDeleteGroup = (group) => {
    confirm.require({
        message: `Tem certeza que deseja remover o grupo "${group.nome}" do usuário?`,
        header: 'Confirmação',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Remover',
        rejectLabel: 'Cancelar',
        accept: async () => {
            // Aqui você pode fazer uma chamada para remover o grupo do usuário no backend, se necessário
            groups.value = groups.value.filter(g => g.id !== group.id);
            toast.add({ severity: 'success', summary: 'Sucesso', detail: 'Grupo removido do usuário', life: 3000 });
        }
    });
};

// Suponha que você já tenha o papel do usuário logado
// Exemplo: const currentUserRole = ref('admin'); // ou 'superadmin', etc.
const currentUserRole = ref('admin'); // Troque para a fonte real do papel do usuário

const roleOptions = computed(() => {
    if (isSuperAdmin.value) {
        return [
            { label: 'Normal', value: 'normal' },
            { label: 'Admin', value: 'admin' },
            { label: 'Superadmin', value: 'superadmin' }
        ];
    }
    return [
        { label: 'Normal', value: 'normal' },
        { label: 'Admin', value: 'admin' },
    ];
});
</script>

<template>
    <div class="users-edit p-4">
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
                            <Tab v-if="isEditing" value="1">Grupos de Acesso</Tab>
                        </TabList>
                        <TabPanels>
                            <TabPanel value="0">
                                <div class="col-span-12 md:col-span-6 mb-4">
                                    <label for="name" class="block mb-2">Nome</label>
                                    <InputText fluid id="name" v-model="name" :class="{ 'p-invalid': validationErrors.name }" />
                                    <small v-if="validationErrors.name" class="p-error text-red-500">{{ validationErrors.name }}</small>
                                </div>
                                <div class="col-span-12 mb-4">
                                    <label for="email" class="block mb-2">E-mail</label>
                                    <InputText fluid id="email" v-model="email" :class="{ 'p-invalid': validationErrors.email }" />
                                    <small v-if="validationErrors.email" class="p-error">{{ validationErrors.email }}</small>
                                </div>
                                <div class="mb-4" v-if="!isEditing">
                                    <label for="password" class="block mb-2">Senha</label>
                                    <InputText id="password" v-model="password" type="password" :class="{ 'p-invalid': validationErrors.password }" required />
                                    <small v-if="validationErrors.password" class="p-error">{{ validationErrors.password }}</small>
                                </div>
                                <div class="mb-4">
                                        <label for="company" class="block mb-2">Empresa</label>
                                        <Dropdown id="company" v-model="companyId" :options="companies" optionLabel="nome" optionValue="id" placeholder="Selecione a empresa" class="w-[220px]" />
                                </div>
                                <div class="mb-4">
                                        <label for="role" class="block mb-2">Nível Acesso</label>
                                        <Dropdown
                                            id="role"
                                            v-model="role"
                                            :options="roleOptions"
                                            optionLabel="label"
                                            optionValue="value"
                                            placeholder="Selecione o perfil"
                                            class="w-[220px]"
                                        />
                                </div>
                            </TabPanel>
                            <TabPanel v-if="isEditing" value="1">
                                <div class="grid mb-4">
                                    <div class="col-span-6">
                                        <Button icon="pi pi-plus" label="Adicionar Grupo" class="mt-2" @click="addGroup" />
                                    </div>
                                </div>
                                <DataTable :value="groups" responsiveLayout="scroll" class="p-datatable-sm" stripedRows>
                                    <Column field="nome" header="Nome" />
                                    <Column field="descricao" header="Descrição" />
                                    <Column header="Ações" style="width: 8rem">
                                        <template #body="slotProps">
                                            <Button icon="pi pi-trash" @click="confirmDeleteGroup(slotProps.data)" severity="danger" />
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
                    <Button label="Salvar" icon="pi pi-save" type="button" @click="saveUser" />
                </div>
            </template>
        </Card>

        <Dialog v-model:visible="openGroupDialog" :header="`Adicionar Grupo de Acesso`" :modal="true" :style="{ width: '20vw' }" :draggable="false">
            <div class="grid">
                <div class="col-span-12 mb-4">
                    <label for="group" class="block mb-2">Grupo</label>
                    <Select
                        filter
                        fluid
                        id="group"
                        v-model="selectedGroup"
                        :options="filteredGroups"
                        optionLabel="nome"
                        placeholder="Selecione um grupo"
                    />
                    <small v-if="validationErrors.group" class="block text-red-500 mt-2">{{ validationErrors.group }}</small>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <Button type="button" label="Cancelar" severity="secondary" @click="openGroupDialog = false"></Button>
                <Button type="button" label="Adicionar" icon="pi pi-plus" @click="saveGroup" />
            </div>
        </Dialog>
    </div>
</template>