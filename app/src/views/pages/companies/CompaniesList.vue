
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useUserStore } from '@/stores/userStore';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import axios from '@/libs/axios';

const router = useRouter();
const userStore = useUserStore();
const toast = useToast();
const confirm = useConfirm();

const isSuperAdmin = computed(() => userStore.isSuperAdmin);

const searchQuery = ref('');
const companies = ref([]);
const loading = ref(false);
const pagination = ref({ page: 1, perPage: 10, total: 0 });

const filteredCompanies = computed(() => {
  if (!searchQuery.value) return companies.value;
  return companies.value.filter(c =>
    c.nome.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    (c.descricao || '').toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

async function fetchCompanies() {
  loading.value = true;
  try {
    const response = await axios.get('/companies');
    companies.value = response.data;
    pagination.value.total = response.data.length;
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Erro', detail: 'Falha ao carregar empresas', life: 3000 });
  }
  loading.value = false;
}

onMounted(fetchCompanies);

function createCompany() {
  router.push('/companies/new');
}

function editCompany(id: number) {
  router.push(`/companies/${id}`);
}

function deleteCompany(id: number) {
  confirm.require({
    message: `Você tem certeza que deseja excluir esta empresa?`,
    acceptLabel: 'Sim, excluir',
    rejectLabel: 'Não, cancelar',
    header: 'Confirmação',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await axios.delete(`/companies/${id}`);
        toast.add({ severity: 'success', summary: 'Sucesso', detail: 'Empresa removida', life: 3000 });
        fetchCompanies();
      } catch (e) {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Erro ao remover empresa', life: 3000 });
      }
    }
  });
}
</script>


<template>
  <div class="companies-list p-4">
    <h1>Lista de Empresas</h1>
    <Card class="mb-4">
      <template #content>
        <Toolbar class="mb-4">
          <template #start>
            <div class="flex flex-wrap gap-2">
              <InputText v-model="searchQuery" placeholder="Buscar empresas..." class="p-inputtext-sm" />
            </div>
          </template>
          <template #end>
            <Button label="Criar Empresa" icon="pi pi-plus" severity="success" @click="createCompany" v-if="isSuperAdmin" />
          </template>
        </Toolbar>
        <DataTable
          :value="filteredCompanies"
          :paginator="true"
          :rows="pagination.perPage"
          :totalRecords="pagination.total"
          :loading="loading"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
          :rowsPerPageOptions="[5, 10, 25, 50]"
          stripedRows
          tableStyle="min-width: 40rem"
          class="p-datatable-sm"
        >
          <Column field="nome" header="Nome" sortable></Column>
          <Column field="descricao" header="Descrição" sortable></Column>
          <Column header="Ações" style="width: 12rem">
            <template #body="{ data }">
              <div class="flex gap-2">
                <Button icon="pi pi-pencil" severity="info" text rounded aria-label="Edit" @click="editCompany(data.id)" />
                <Button icon="pi pi-trash" severity="danger" text rounded aria-label="Delete" @click="deleteCompany(data.id)" v-if="isSuperAdmin" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<style scoped>
.companies-list h1 {
  margin-bottom: 1.5rem;
}
</style>
