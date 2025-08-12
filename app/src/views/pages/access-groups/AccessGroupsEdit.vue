<script setup>
import { useAccessGroupStore } from '@/stores/accessGroupStore';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import { useConfirm } from 'primevue/useconfirm';
const confirm = useConfirm();

const route = useRoute();
const router = useRouter();
const accessGroupStore = useAccessGroupStore();
const toast = useToast();

// Dados do formulário
const name = ref('');
const description = ref('');
const structures = ref([]);
const users = ref([]);
const allUsers = ref([]);

const validationErrors = ref({});

// Determina se estamos editando ou criando
const isEditing = computed(() => route.params.id !== undefined);
const accessGroupId = computed(() => route.params.id);
const formTitle = computed(() => (isEditing.value ? 'Editar Grupo de Acesso' : 'Criar Grupo de Acesso'));

// sem_nivel, leitura, leitura_escrita

onMounted(async () => {
    accessGroupStore.getAllUsers().then((response) => {
        allUsers.value = response.map((user) => ({
            id: user.id,
            name: user.name,
            email: user.email
        }));
    });

    const structuresResponse = await accessGroupStore.getStructureOptions();
    const mappedStructures = structuresResponse.map((structure) => ({
        id: structure.id,
        estrutura_id: structure.id,
        finalidade: structure.finalidade,
        nivel_acesso: structure.nivel_acesso || 'sem_nivel'
    }));

    if (isEditing.value) {
        const accessGroup = await accessGroupStore.getAccessGroupById(accessGroupId.value);

        if (accessGroup) {
            name.value = accessGroup.nome;
            description.value = accessGroup.descricao;
            structures.value = accessGroup.estruturas;

            // Mapeia as estruturas combinando os dados existentes com o acesso do grupo
            structures.value = mappedStructures.reduce((result, structure) => {
                const existingStructure = accessGroup.estruturas.find((s) => s.estrutura_id === structure.id);

                result.push({
                    ...structure,
                    nivel_acesso: existingStructure?.nivel_acesso || structure.nivel_acesso
                });

                return result;
            }, []);

            users.value = (await accessGroupStore.getUsersByAccessGroupId(accessGroupId.value)) || [];
        } else {
            toast.add({
                severity: 'error',
                summary: 'Erro',
                detail: 'Grupo de acesso não encontrado',
                life: 3000
            });
            router.push('/access-groups');
        }
    } else {
        structures.value = mappedStructures;
    }
});

// Enhancing validation
const validateForm = async () => {
    const errors = {};

    if (!name.value || name.value.trim() === '') {
        errors.name = 'O nome do grupo de acesso é obrigatório';
    } else if (name.value.length < 3) {
        errors.name = 'O nome deve ter pelo menos 3 caracteres';
    }

    validationErrors.value = errors;
    return Object.keys(errors).length === 0;
};

const selectedUser = ref(null);
const openUserDialog = ref(false);
const filteredUsers = computed(() => {
    return allUsers.value.filter((user) => {
        return !users.value.some((u) => u.id === user.id);
    });
});

const addUser = async () => {
    openUserDialog.value = true;
    selectedUser.value = null;
    validationErrors.value.user = null;
};

const saveUser = async () => {
    if (!selectedUser.value) {
        validationErrors.value.user = 'Selecione um usuário';
        return;
    }

    const response = await accessGroupStore.addUserToAccessGroup(accessGroupId.value, selectedUser.value.id);
    if (response.error) {
        return toast.add({ severity: 'error', summary: 'Erro', detail: response.error, life: 3000 });
    }

    users.value.push({
        id: selectedUser.value.id,
        name: selectedUser.value.name,
        email: selectedUser.value.email
    });

    openUserDialog.value = false;
    selectedUser.value = null;
    validationErrors.value.user = null;
};

// Salvar grupo de acesso
const saveAccessGroup = async () => {
    const isValid = await validateForm();

    if (!isValid) {
        toast.add({
            severity: 'error',
            summary: 'Erro de Validação',
            detail: 'Por favor, verifique os erros no formulário',
            life: 3000
        });

        return;
    }

    const payload = {
        nome: name.value,
        descricao: description.value,
        estruturas: structures.value.map((structure) => ({
            estrutura_id: structure.estrutura_id,
            nivel_acesso: structure.nivel_acesso
        }))
    };

    try {
        if (isEditing.value) {
            const { error } = await accessGroupStore.updateAccessGroup(accessGroupId.value, payload);
            if (error) throw new Error(error);

            toast.add({
                severity: 'success',
                summary: 'Sucesso',
                detail: 'Grupo de acesso atualizado com sucesso',
                life: 3000
            });

            router.push('/access-groups');
        } else {
            const { error } = await accessGroupStore.createAccessGroup(payload);
            if (error) throw new Error(error);

            toast.add({
                severity: 'success',
                summary: 'Sucesso',
                detail: 'Grupo de acesso criado com sucesso',
                life: 3000
            });
            router.push('/access-groups');
        }
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Erro',
            detail: error || 'Ocorreu um erro ao salvar o grupo de acesso',
            life: 3000
        });
    }
};

const cancel = () => {
    router.push('/access-groups');
};

const breadcrumbItems = ref([
    { label: 'Dashboard', url: '/' },
    { label: 'Grupos de Acesso', url: '/access-groups' },
    { label: isEditing.value ? 'Editar Grupo' : 'Criar Grupo', url: null }
]);

const confirmDeleteUser = (user) => {
    confirm.require({
        message: `Tem certeza que deseja remover ${user.name}?`,
        header: 'Confirmação',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Remover',
        rejectLabel: 'Cancelar',
        accept: async () => {
            const userRemoved = await accessGroupStore.removeUserFromAccessGroup(accessGroupId.value, user.id);
            if (!userRemoved) return toast.add({ severity: 'error', summary: 'Erro', detail: 'Erro ao remover usuário', life: 3000 });

            toast.add({ severity: 'success', summary: 'Sucesso', detail: 'Usuário removido', life: 3000 });
            users.value = users.value.filter((u) => u.id !== user.id);
        }
    });
};
</script>

<template>
    <div class="access-groups-edit p-4">
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
                            <Tab value="1">Permissões</Tab>
                            <Tab value="2" :disabled="structures.length === 0 || !isEditing">Usuários</Tab>
                        </TabList>
                        <TabPanels>
                            <TabPanel value="0">
                                <div class="col-span-12 md:col-span-6 mb-4">
                                    <label for="name" class="block mb-2">Nome</label>
                                    <InputText fluid id="name" v-model="name" :class="{ 'p-invalid': validationErrors.name }" />
                                    <small v-if="validationErrors.name" class="p-error text-red-500">{{ validationErrors.name }}</small>
                                </div>

                                <div class="col-span-12 mb-4">
                                    <label for="description" class="block mb-2">Descrição</label>
                                    <Textarea fluid id="description" v-model="description" rows="3" :class="{ 'p-invalid': validationErrors.description }" />
                                    <small v-if="validationErrors.description" class="p-error">{{ validationErrors.description }}</small>
                                </div>
                            </TabPanel>

                            <TabPanel value="1">
                                <DataTable :value="structures" responsiveLayout="scroll" class="p-datatable-sm mb-4">
                                    <Column field="structure" header="Estrutura">
                                        <template #body="{ data, index }">
                                            <Chip class="bg-primary-50 text-primary-700 font-medium">
                                                {{ data.finalidade }}
                                            </Chip>
                                        </template>
                                    </Column>

                                    <Column field="sem_nivel" header="Sem Acesso" class="text-center">
                                        <template #body="{ data, index }">
                                            <RadioButton v-model="structures[index].nivel_acesso" name="structures[index].nivel_acesso" value="sem_nivel" :inputId="`no-access-${data.estrutura_id}`" />
                                        </template>
                                    </Column>

                                    <Column header="Ler" class="text-center">
                                        <template #body="{ data, index }">
                                            <RadioButton v-model="structures[index].nivel_acesso" name="structures[index].nivel_acesso" value="leitura" :inputId="`read-${data.estrutura_id}`" />
                                        </template>
                                    </Column>
                                    <Column header="Completo" class="text-center">
                                        <template #body="{ data, index }">
                                            <RadioButton v-model="structures[index].nivel_acesso" name="structures[index].nivel_acesso" value="leitura_escrita" :inputId="`full-${data.estrutura_id}`" />
                                        </template>
                                    </Column>
                                </DataTable>
                            </TabPanel>

                            <TabPanel value="2">
                                <div class="grid" v-if="structures.length > 0">
                                    <div class="col-span-6 mb-4">
                                        <Button icon="pi pi-plus" label="Adicionar Usuário" class="mt-2" @click="addUser" />
                                    </div>
                                </div>

                                <DataTable :value="users" responsiveLayout="scroll" class="p-datatable-sm" stripedRows>
                                    <Column field="name" header="Nome" />
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
                    <Button label="Salvar" icon="pi pi-save" type="button" @click="saveAccessGroup" />
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
