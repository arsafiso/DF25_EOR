<script setup lang="ts">
import { useStructureStore } from '@/stores/structureStore';
import { useUserStore } from '@/stores/userStore';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const structureStore = useStructureStore();
const userStore = useUserStore();
const { statusOptions, cdaClassificationOptions } = structureStore;
const toast = useToast();

const confirm = useConfirm();

const searchQuery = ref('');
const selectedStatus = ref(null);
const selectedClassification = ref(null);

const isAdmin = computed(() => {
    return userStore.isAdmin;
});

const isSuperAdmin = computed(() => {
    return userStore.isSuperAdmin;
});

const filters = computed(() => {
    const activeFilters = [];

    if (searchQuery.value) {
        activeFilters.push({ field: 'purpose', operator: 'contains', value: searchQuery.value });
    }

    if (selectedStatus.value) {
        activeFilters.push({ field: 'status', operator: 'eq', value: selectedStatus.value });
    }

    if (selectedClassification.value) {
        activeFilters.push({ field: 'federal_classification', operator: 'eq', value: selectedClassification.value });
    }

    return activeFilters;
});

// Fetch structures on mount
onMounted(() => {
    structureStore.fetchStructures();
});

// Watch for filter changes
watch([searchQuery, selectedStatus, selectedClassification], () => {
    structureStore.clearFilters();
    filters.value.forEach((filter) => structureStore.addFilter(filter));
});

// Create a new structure
const createStructure = () => {
    router.push('/structures/new');
};

// Edit a structure
const editStructure = (id) => {
    router.push(`/structures/${id}`);
};

// Delete a structure
const deleteStructure = async (id) => {
    confirm.require({
        message: `Você tem certeza que deseja excluir esta estrutura?`,
        acceptLabel: 'Sim, excluir',
        rejectLabel: 'Não, cancelar',
        header: 'Confirmação',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: async () => {
            const deleted = await structureStore.deleteStructure(id);
            if (!deleted) return toast.add({ severity: 'error', summary: 'Erro', detail: 'Erro ao remover estrutura', life: 3000 });

            toast.add({ severity: 'success', summary: 'Sucesso', detail: 'Estrutura removida', life: 3000 });
            structureStore.fetchStructures();
        }
    });
};

const getStatusSeverity = (status) => {
    switch (status) {
        default:
            return 'primary';
    }
};
</script>

<template>
    <div class="structures-list p-4">
        <h1>Lista de Estruturas</h1>

        <Card class="mb-4">
            <template #content>
                <Toolbar class="mb-4">
                    <template #start>
                        <div class="flex flex-wrap gap-2">
                            <InputText v-model="searchQuery" placeholder="Buscar estruturas..." class="p-inputtext-sm" />
                            <Select v-model="selectedStatus" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Filtrar por status" class="p-inputtext-sm" :showClear="true" />
                            <Select v-model="selectedClassification" :options="cdaClassificationOptions" optionLabel="label" optionValue="value" placeholder="Filtrar por classificação" class="p-inputtext-sm" :showClear="true" />
                        </div>
                    </template>
                    <template #end>
                        <Button label="Criar Estrutura" icon="pi pi-plus" severity="success" @click="createStructure" v-if="isAdmin || isSuperAdmin" />
                    </template>
                </Toolbar>

                <DataTable
                    :value="structureStore.paginatedStructures"
                    :paginator="true"
                    :rows="structureStore.pagination.perPage"
                    :totalRecords="structureStore.pagination.total"
                    :loading="structureStore.loading"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                    :rowsPerPageOptions="[5, 10, 25, 50]"
                    @page="(e) => structureStore.setPage(e.page + 1)"
                    @rows="(e) => structureStore.setPerPage(e)"
                    stripedRows
                    tableStyle="min-width: 50rem"
                    class="p-datatable-sm"
                >
                    <Column field="finalidade" header="Finalidade" sortable></Column>
                    <Column header="Status" sortable :sortField="'status'">
                        <template #body="{ data }">
                            <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
                        </template>
                    </Column>
                    <Column field="projetistas" header="Projetistas" sortable>
                        <template #body="{ data }">
                            {{ data.projetistas !== null ? data.projetistas : '-' }}
                        </template>
                    </Column>
                    <Column field="classificacao_federal" header="Classificação Federal" sortable> </Column>
                    <Column header="Ações" style="width: 15rem">
                        <template #body="{ data }">
                            <div class="flex gap-2">
                                <Button icon="pi pi-pencil" severity="info" text rounded aria-label="Edit" @click="editStructure(data.id)" />
                                <Button icon="pi pi-trash" severity="danger" text rounded aria-label="Delete" @click="deleteStructure(data.id)" v-if="isAdmin || isSuperAdmin" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>
    </div>
</template>

<style scoped>
.structures-list h1 {
    margin-bottom: 1.5rem;
}
</style>
