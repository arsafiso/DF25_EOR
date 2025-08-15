<script setup>
import { useAccessGroupStore } from '@/stores/accessGroupStore';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const accessGroupStore = useAccessGroupStore();
const toast = useToast();

import { useConfirm } from 'primevue/useconfirm';
const confirm = useConfirm();

const searchQuery = ref('');

const filters = computed(() => {
    const activeFilters = [];

    if (searchQuery.value) {
        activeFilters.push({ field: 'name', operator: 'contains', value: searchQuery.value });
    }

    return activeFilters;
});

onMounted(async () => {
    await accessGroupStore.fetchAccessGroups();
});

watch(searchQuery, () => {
    accessGroupStore.clearFilters();
    filters.value.forEach((filter) => accessGroupStore.addFilter(filter));
});

const createAccessGroup = () => {
    router.push('/access-groups/new');
};

const editAccessGroup = (id) => {
    router.push(`/access-groups/${id}`);
};

// Excluir um grupo de acesso
const deleteAccessGroup = async (id) => {
    confirm.require({
        message: `Você tem certeza que deseja excluir este grupo de acesso?`,
        acceptLabel: 'Sim, excluir',
        rejectLabel: 'Não, cancelar',
        header: 'Confirmação',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: async () => {
            const deleted = await accessGroupStore.deleteAccessGroup(id);
            if (!deleted) return toast.add({ severity: 'error', summary: 'Erro', detail: 'Erro ao remover grupo de acesso', life: 3000 });

            toast.add({ severity: 'success', summary: 'Sucesso', detail: 'Grupo de acesso removido', life: 3000 });
            accessGroupStore.fetchAccessGroups();
        }
    });
};

const formatPermissions = (structures) => {
    const counts = {
        sem_nivel: 0,
        leitura: 0,
        escrita: 0,
        leitura_escrita: 0
    };

    if (!Array.isArray(structures)) return counts;

    structures.forEach((structure) => {
        if (counts[structure.nivel_acesso] !== undefined) {
            counts[structure.nivel_acesso] += 1;
        }
    });

    return counts;
};

</script>

<template>
    <div class="access-groups-list p-4">
        <h1>Grupos de Acesso</h1>

        <Card class="mb-4">
            <template #content>
                <Toolbar class="mb-4">
                    <template #start>
                        <div class="flex flex-wrap gap-2">
                            <InputText v-model="searchQuery" placeholder="Pesquisar grupos de acesso..." fluid />
                        </div>
                    </template>
                    <template #end>
                        <Button label="Adicionar Grupo de Acesso" icon="pi pi-plus" severity="success" @click="createAccessGroup" />
                    </template>
                </Toolbar>

                <DataTable
                    :value="accessGroupStore.paginatedAccessGroups"
                    :paginator="true"
                    :rows="accessGroupStore.pagination.perPage"
                    :totalRecords="accessGroupStore.pagination.total"
                    :loading="accessGroupStore.loading"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                    :rowsPerPageOptions="[5, 10, 25, 50]"
                    @page="(e) => accessGroupStore.setPage(e.page + 1)"
                    @rows="(e) => accessGroupStore.setPerPage(e)"
                    stripedRows
                    tableStyle="min-width: 50rem"
                    class="p-datatable-sm"
                >
                    <Column field="nome" header="Nome" sortable></Column>
                    <Column field="descricao" header="Descrição" sortable></Column>
                    <Column header="Permissões">
                        <template #body="{ data }">
                            <div class="flex gap-2">
                                <span v-if="formatPermissions(data.estruturas).sem_nivel > 0">
                                    <Badge value="N" severity="danger" :title="`${formatPermissions(data.estruturas).sem_nivel} estruturas sem nível de acesso`" />
                                </span>

                                <span v-if="formatPermissions(data.estruturas).leitura > 0">
                                    <Badge value="L" severity="success" :title="`${formatPermissions(data.estruturas).leitura} estruturas com acesso de leitura`" />
                                </span>

                                <span v-if="formatPermissions(data.estruturas).leitura_escrita > 0">
                                    <Badge value="T" severity="contrast" :title="`${formatPermissions(data.estruturas).leitura_escrita} estruturas com acesso de leitura e escrita`" />
                                </span>
                            </div>
                        </template>
                    </Column>
                    <Column header="Ações" style="width: 15rem">
                        <template #body="{ data }">
                            <div class="flex gap-2">
                                <Button icon="pi pi-pencil" severity="info" text rounded aria-label="Editar" @click="editAccessGroup(data.id)" />
                                <Button icon="pi pi-trash" severity="danger" text rounded aria-label="Excluir" @click="deleteAccessGroup(data.id)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>
    </div>
</template>

<style scoped></style>
