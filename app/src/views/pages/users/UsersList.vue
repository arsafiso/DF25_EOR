<script setup>
import { useAccessGroupStore } from '@/stores/accessGroupStore';
import { useUserStore } from '@/stores/userStore';
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
const toast = useToast();
const router = useRouter();

const userStore = useUserStore();
const accessGroupsStore = useAccessGroupStore();

const allGroups = computed(() => accessGroupsStore.accessGroups);

const editingUser = ref(null);
const selectedGroups = ref([]);
const groupDialogVisible = ref(false);
const saving = ref(false);

const searchQuery = ref('');
const selectedStatus = ref(null);

const filters = computed(() => {
    const activeFilters = [];
    if (searchQuery.value) {
        activeFilters.push({ field: 'name', operator: 'contains', value: searchQuery.value });
    }
    return activeFilters;
});

const openGroupDialog = (user) => {
    editingUser.value = user;
    selectedGroups.value = user.grupos.map((g) => g.id);
    groupDialogVisible.value = true;
};

const closeGroupDialog = () => {
    if (saving.value) return;
    groupDialogVisible.value = false;
    editingUser.value = null;
    selectedGroups.value = [];
};

const saveGroupMemberships = async () => {
    if (!editingUser.value) return;
    saving.value = true;
    try {
        // Check for groups to add
        for (const group of selectedGroups.value) {
            if (!editingUser.value.grupos.some((g) => g.id === group)) {
                await accessGroupsStore.addUserToAccessGroup(group, editingUser.value.id);
            }
        }
        // Check for groups to remove
        for (const group of editingUser.value.grupos) {
            if (!selectedGroups.value.includes(group.id)) {
                await accessGroupsStore.removeUserFromAccessGroup(group.id, editingUser.value.id);
            }
        }
        await userStore.fetchUsers();
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: `Informações do usuário ${editingUser.value.name} atualizadas com sucesso!`,
            life: 3000
        });
        groupDialogVisible.value = false;
        editingUser.value = null;
        selectedGroups.value = [];
    } catch (error) {
        console.error('Error saving group memberships:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Falha ao atualizar as informações do usuário',
            life: 3000
        });
    } finally {
        saving.value = false;
    }
};

const createUser = () => {
    router.push('/users/new');
};

const editUser = (user) => {
    router.push(`/users/${user.id}`);
};

onMounted(async () => {
    await Promise.all([userStore.fetchUsers(), accessGroupsStore.fetchAccessGroups()]);
});

watch([searchQuery, selectedStatus], () => {
    userStore.clearFilters();
    filters.value.forEach((filter) => userStore.addFilter(filter));
});
</script>

<template>
    <div class="users-list p-4">
        <h1>Usuários</h1>
        <Card class="mb-4">
            <template #content>
                <Toolbar class="mb-4">
                    <template #start>
                        <div class="flex flex-wrap gap-2">
                            <InputText v-model="searchQuery" placeholder="Pesquisar por nome" class="w-full md:w-20rem" />
                            <Button label="Novo Usuário" icon="pi pi-plus" class="p-button-success" @click="createUser" />
                        </div>
                    </template>
                </Toolbar>
                <DataTable
                    :value="userStore.filteredUsers"
                    :paginator="true"
                    :rows="10"
                    :totalRecords="userStore.pagination.total"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                    :rowsPerPageOptions="[5, 10, 25, 50]"
                    stripedRows
                    tableStyle="min-width: 50rem"
                    class="p-datatable-sm"
                >
                    <Column field="name" header="Nome de Usuário" sortable></Column>
                    <Column field="email" header="Email" sortable></Column>
                    <Column field="company_name" header="Empresa" sortable></Column>
                    <Column header="Admin">
                        <template #body="slotProps">
                            <Badge :value="slotProps.data.role" :severity="(slotProps.data.role == 'admin' || slotProps.data.role == 'superadmin') ? 'success' : 'info'" />
                        </template>
                    </Column>
                    <Column header="Grupos">
                        <template #body="slotProps">
                            <div class="flex flex-wrap align-items-center">
                                <Badge v-if="slotProps.data.grupos.length > 0" :value="slotProps.data.grupos.length" severity="info" />
                                <Badge v-else value="0" severity="danger" />
                            </div>
                        </template>
                    </Column>
                    <Column header="Ações">
                        <template #body="slotProps">
                            <Button
                                icon="pi pi-pencil"
                                severity="info"
                                text
                                rounded
                                aria-label="Editar Grupos"
                                @click="openGroupDialog(slotProps.data)"
                                :disabled="saving"
                                v-tooltip.bottom="'Editar grupos do usuário'"
                            />
                            <Button
                                icon="pi pi-user-edit"
                                severity="primary"
                                text
                                rounded
                                aria-label="Editar Usuário"
                                @click="editUser(slotProps.data)"
                                :disabled="saving"
                                class="ml-2"
                                v-tooltip.bottom="'Editar dados do usuário'"
                            />
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>
        <Dialog v-model:visible="groupDialogVisible" :draggable="false" :style="{ width: '500px' }" header="Gerenciar Associações de Grupos" :modal="true" :closable="!saving">
            <div v-if="editingUser" class="mb-4">
                <div class="flex align-items-center">
                    <div>
                        <h4 class="mb-1 mt-0">{{ editingUser.name }}</h4>
                        <p class="text-sm text-color-secondary m-0">{{ editingUser.email }}</p>
                    </div>
                </div>
                <Divider />
                <p class="font-medium">Selecione os grupos para este usuário:</p>
            </div>
            <div class="field mb-4">
                <div class="p-fluid">
                    <div class="mb-4" v-for="group in allGroups" :key="group.id">
                        <Checkbox :inputId="'group_' + group.id" v-model="selectedGroups" :value="group.id" :binary="false" :disabled="saving" />
                        <label :for="'group_' + group.id" class="ml-2">{{ group.nome }}</label>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Cancelar" icon="pi pi-times" class="p-button-text" @click="closeGroupDialog" :disabled="saving" />
                <Button label="Salvar" icon="pi pi-check" class="p-button-primary" @click="saveGroupMemberships" :loading="saving" />
            </template>
        </Dialog>
    </div>
</template>

<style scoped></style>
