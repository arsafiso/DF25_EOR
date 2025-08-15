<script setup lang="ts">
import { useStructureStore } from '@/stores/structureStore';
import { useUserStore } from '@/stores/userStore';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, ref, watch } from 'vue';

import axios from '@/libs/axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const structureStore = useStructureStore();
const userStore = useUserStore();
const { statusOptions, federalClassificationOptions } = structureStore;
const toast = useToast();

const confirm = useConfirm();

const searchQuery = ref('');
const selectedStatus = ref(null);
const selectedClassification = ref(null);
const selectedEmpresa = ref(null);

const isAdmin = computed(() => userStore.isAdmin);
const isSuperAdmin = computed(() => userStore.isSuperAdmin);

const filters = computed(() => {
    const activeFilters = [];

    if (searchQuery.value) {
        activeFilters.push({ field: 'finalidade', operator: 'contains', value: searchQuery.value });
    }

    if (selectedStatus.value) {
        activeFilters.push({ field: 'status', operator: 'eq', value: selectedStatus.value });
    }

    if (selectedClassification.value) {
        activeFilters.push({ field: 'classificacao_federal', operator: 'eq', value: selectedClassification.value });
    }

    if (selectedEmpresa.value) {
        activeFilters.push({ field: 'company_id', operator: 'eq', value: selectedEmpresa.value });
    }

    return activeFilters;
});

const filteredStructuresLocal = computed(() => {
    return structureStore.structures.filter(structure => {
        const finalidadeMatch = searchQuery.value
            ? (structure.finalidade || '').toLowerCase().includes(searchQuery.value.toLowerCase())
            : true;
        const statusMatch = selectedStatus.value
            ? structure.status === selectedStatus.value
            : true;
        const classificacaoMatch = selectedClassification.value
            ? structure.classificacao_federal === selectedClassification.value
            : true;
        const empresaMatch = selectedEmpresa.value
            ? structure.company_id === selectedEmpresa.value
            : true;
        return finalidadeMatch && statusMatch && classificacaoMatch && empresaMatch;
    });
});

// Mapeamento de empresas para exibir nome na tabela e para filtro
const empresasMap = ref({});
const empresasOptions = ref([]);

async function fetchEmpresasMap() {
    try {
        const response = await axios.get('/companies');
        // Cria um map id -> nome
        empresasMap.value = Object.fromEntries(response.data.map(e => [e.id, e.nome]));
        // Cria opções para filtro
        empresasOptions.value = response.data.map(e => ({ label: e.nome, value: e.id }));
    } catch (e) {
        empresasMap.value = {};
        empresasOptions.value = [];
    }
}

onMounted(() => {
    structureStore.fetchStructures();
    fetchEmpresasMap();
});

watch(isSuperAdmin, (val) => {
    if (val) fetchEmpresasMap();
});


// Watch for filter changes
watch([searchQuery, selectedStatus, selectedClassification, selectedEmpresa], () => {
    structureStore.filters = [...filters.value];
    structureStore.pagination.page = 1;
});

// Create a new structure

// Seleção de empresa para superadmin e filtro
const showEmpresaModal = ref(false);
const empresas = ref([]);
const empresaSelecionada = ref(null);
const empresaLoading = ref(false);

async function abrirModalEmpresa() {
    empresaLoading.value = true;
    if (!empresas.value.length) {
        try {
            const response = await axios.get('/companies');
            empresas.value = response.data;
        } catch (e) {
            toast.add({ severity: 'error', summary: 'Erro', detail: 'Falha ao carregar empresas', life: 3000 });
        }
    }
    showEmpresaModal.value = true;
    empresaLoading.value = false;
}

function confirmarEmpresa() {
    if (empresaSelecionada.value) {
        router.push({ path: '/structures/new', query: { empresaId: empresaSelecionada.value } });
        showEmpresaModal.value = false;
        empresaSelecionada.value = null;
    }
}

const createStructure = () => {
    if (isSuperAdmin.value) {
        abrirModalEmpresa();
    } else {
        router.push('/structures/new');
    }
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
                            <Select v-model="selectedClassification" :options="federalClassificationOptions" optionLabel="label" optionValue="value" placeholder="Filtrar por classificação federal" class="p-inputtext-sm" :showClear="true" />
                            <Select v-model="selectedEmpresa" :options="empresasOptions" optionLabel="label" optionValue="value" placeholder="Filtrar por empresa" class="p-inputtext-sm" :showClear="true" v-if="isSuperAdmin" />
                        </div>
                    </template>
                    <template #end>
                        <Button label="Criar Estrutura" icon="pi pi-plus" severity="success" @click="createStructure" v-if="isAdmin || isSuperAdmin" />
                    </template>
                </Toolbar>
        <!-- Seletor de empresa para superadmin -->
        <Dialog v-model:visible="showEmpresaModal" modal header="Selecione a Empresa" :closable="false" :style="{ width: '400px' }">
            <div v-if="empresaLoading" class="p-4 text-center">Carregando empresas...</div>
            <div v-else>
                <Select v-model="empresaSelecionada" :options="empresas" optionLabel="nome" optionValue="id" placeholder="Selecione uma empresa" class="w-full mb-3" />
                <div class="flex justify-end gap-2">
                    <Button label="Cancelar" @click="showEmpresaModal = false; empresaSelecionada = null;" text />
                    <Button label="Confirmar" @click="confirmarEmpresa" :disabled="!empresaSelecionada" />
                </div>
            </div>
        </Dialog>

                <DataTable
                    :value="filteredStructuresLocal"
                    :paginator="true"
                    :rows="structureStore.pagination.perPage"
                    :totalRecords="filteredStructuresLocal.length"
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
                    <!-- Coluna Empresa para superadmin -->
                    <Column v-if="isSuperAdmin" header="Empresa" sortable>
                        <template #body="{ data }">
                            {{ empresasMap[data.company_id] || '-' }}
                        </template>
                    </Column>
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
