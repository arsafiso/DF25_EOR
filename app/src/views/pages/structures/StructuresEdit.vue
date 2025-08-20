<script setup>
import { useStructureStore } from '@/stores/structureStore';
import { useUserStore } from '@/stores/userStore';
import { useToast } from 'primevue';
import { onMounted, ref, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '@/libs/axios';
import Calendar from 'primevue/calendar';
import { exportarEstrutura as exportarEstruturaUtil } from '@/utils/expEstrutura';

const justificativa = ref('');
const route = useRoute();
const router = useRouter();
const userStore = useUserStore();
const structureStore = useStructureStore();
const toast = useToast();

const { statusOptions, federalClassificationOptions, stateClassificationOptions, cdaClassificationOptions } = structureStore;

const isAdmin = computed(() => userStore.isAdmin);
const isSuperAdmin = computed(() => userStore.isSuperAdmin);
const canEdit = ref(false);

const structure = ref({
    // Basic Information
    nome: '',
    finalidade: '',
    projetistas: '',
    status: '',
    classificacao_federal: null,
    classificacao_estadual: null,
    classificacao_cda: '',
    empresa_id: null, 

    // Technical Measurements
    elevacao_crista: null,
    unidade_elevacao_crista: 'm',
    altura_maxima_federal: '',
    unidade_altura_maxima_federal: 'm',
    altura_maxima_estadual: '',
    unidade_altura_maxima_estadual: 'm',
    largura_coroamento: '',
    area_reservatorio_crista: null,
    unidade_area_reservatorio_crista: 'km²',
    area_reservatorio_soleira: null,
    unidade_area_reservatorio_soleira: 'km²',
    elevacao_base: '',
    unidade_elevacao_base: 'm',
    altura_maxima_entre_bermas: null,
    unidade_altura_maxima_entre_bermas: 'm',
    largura_bermas: '',
    unidade_largura_bermas: 'm',

    // Structural Details
    tipo_secao: '',
    drenagem_interna: '',
    instrumentacao: '',
    fundacao: '',
    analises_estabilidade: '',

    // Hydraulic Parameters
    area_bacia_contribuicao: null,
    unidade_area_bacia_contribuicao: 'km²',
    area_espelho_dagua: null,
    unidade_area_espelho_dagua: 'km²',
    na_maximo_operacional: null,
    unidade_na_maximo_operacional: 'm',
    na_maximo_maximorum: null,
    unidade_na_maximo_maximorum: 'm',
    borda_livre: null,
    unidade_borda_livre: 'm',
    volume_transito_cheias: null,
    unidade_volume_transito_cheias: 'm³',
    sistema_extravasor: '',

    // Comments
    comentarios: []
});
// Unidades de medida
const unidadeComprimento = ['mm', 'cm', 'dm', 'm', 'dam', 'hm', 'km'];
const unidadesArea = ['mm²', 'cm²', 'dm²', 'm²', 'dam²', 'hm²', 'km²'];
const unidadesVolume = ['mm³', 'cm³', 'dm³', 'm³', 'dam³', 'hm³', 'km³'];

// Validation
const validationErrors = ref({});
const activeTab = ref(0);

// Determine if we're editing or creating
const isEditing = computed(() => route.params.id !== undefined);
const structureId = computed(() => route.params.id);
const formTitle = computed(() => (isEditing.value ? 'Editar Estrutura' : 'Criar Estrutura'));

onMounted(async () => {
    if (!isEditing.value && route.query.empresaId) {
        structure.value.empresa_id = Number(route.query.empresaId);
    }

    if (isEditing.value) {
        const { data, error } = await structureStore.getStructureById(structureId.value);

        if (data) {
            structure.value = { ...data };
            canEdit.value = data.pode_editar;
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error || 'Estrutura não encontrada',
                life: 3000
            });

            router.push('/structures');
        }
    }

    // Carregar opções de classificação
    await structureStore.fetchFederalClassifications();
    await structureStore.fetchStateClassificationOptions();
    

    //console.log('entrou 1');//para debug
    // Buscar comentários da estrutura
    try {
        const comentariosResp = await axios.get(`/estruturas/${structureId.value}/comentarios`);
        //console.log('entrou 2');//para debug

        if (comentariosResp.status >= 200 && comentariosResp.status < 300) {
            //console.log('entrou 3');//para debug
            structure.value.comentarios = comentariosResp.data;
            
        } else {
             console.error('Failed to load comments:', comentariosResp.status, comentariosResp.data);
             toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível carregar os comentários', life: 3000 });
        }
    } catch (e) {
        console.error('Error fetching comments:', e);
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Falha de comunicação ao carregar comentários', life: 3000 });
    }
    //console.log('entrou 4');//para debug

    await carregarArquivosClassificacao();
});

const fieldTabs = {
    0: ['nome', 'finalidade', 'projetistas', 'status', 'classificacao_federal', 'classificacao_estadual', 'classificacao_cda'],
    1: ['elevacao_crista', 'altura_maxima_federal', 'altura_maxima_estadual', 'largura_coroamento', 'area_reservatorio_crista', 'area_reservatorio_soleira', 'elevacao_base', 'altura_maxima_entre_bermas', 'largura_bermas'],
    2: ['tipo_secao', 'drenagem_interna', 'instrumentacao', 'fundacao', 'analises_estabilidade'],
    3: ['area_bacia_contribuicao', 'area_espelho_dagua', 'na_maximo_operacional', 'na_maximo_maximorum', 'borda_livre', 'volume_transito_cheias', 'sistema_extravasor']
};

const validateForm = async () => {
    try {
        validationErrors.value = {};
        // await structureStore.validateStructure(structure.value);

        return true;
    } catch (error) {
        const newErrors = {};
        const tabErrors = new Set();

        for (const err of error.inner) {
            newErrors[err.path] = err.message;

            for (const [tab, fields] of Object.entries(fieldTabs)) {
                if (fields.includes(err.path)) {
                    tabErrors.add(Number(tab));
                    break;
                }
            }
        }

        validationErrors.value = newErrors;
        activeTab.value = Array.from(tabErrors)[0] ?? 0;

        return false;
    }
};

const saveStructure = async () => {
    const isValid = await validateForm();

    if (!isValid) {
        toast.add({
            severity: 'error',
            summary: 'Validation Error',
            detail: 'Por favor, corrija os erros de validação antes de salvar',
            life: 3000
        });
        return;
    }
    if (isEditing.value && (!justificativa.value || !justificativa.value.trim())) {
        toast.add({
            severity: 'error',
            summary: 'Erro de Validação',
            detail: 'O campo Justificativa da Modificação é obrigatório.',
            life: 3000
        });
        return;
    }

    try {
        if (isEditing.value) {
            const result = await structureStore.updateStructure(structureId.value, {...structure.value, justificativa: justificativa.value});

            if (result) {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Estrutura atualizada com sucesso',
                    life: 3000
                });

                router.push('/structures');
            } else {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Erro ao atualizar a estrutura',
                    life: 3000
                });
            }
        } else {
            // Superadmin usa empresa selecionada
            let payload = { ...structure.value, justificativa: justificativa.value };
            if (isSuperAdmin.value && route.query.empresaId) {
                payload.empresa_id = Number(route.query.empresaId);
                payload.company_id = Number(route.query.empresaId);
            }
            const result = await structureStore.createStructure(payload);
            if (result && result.error) {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: result.error,
                    life: 3000
                });
            } else if (result) {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Estrutura criada com sucesso',
                    life: 3000
                });

                router.push('/structures');
            } else {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Erro ao atualizar a estrutura',
                    life: 3000
                });
            }
        }
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Algo deu errado ao salvar a estrutura',
            life: 3000
        });
    }
};

const cancel = () => {
    router.push('/structures');
};

const breadcrumbItems = ref([{ label: 'Dashboard', url: '/' }, { label: 'Estruturas', url: '/structures' }, { label: isEditing.value ? 'Editar Estrutura' : 'Adicionar Estrutura' }]);

const novoComentario = ref('');


onMounted(async () => {
    try {
        const comentariosResp = await axios.get(`/estruturas/${structureId.value}/comentarios`);
        if (comentariosResp.status >= 200 && comentariosResp.status < 300) {
            structure.value.comentarios = comentariosResp.data;
        } else {
             console.error('Failed to load comments:', comentariosResp.status, comentariosResp.data);
             toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível carregar os comentários', life: 3000 });
        }
    } catch (e) {
        console.error('Error fetching comments:', e);
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Falha de comunicação ao carregar comentários', life: 3000 });
    }
    //console.log('entrou 4');//para debug

    await carregarArquivosClassificacao();
});


async function recarregarComentarios() {
    try {
        const comentariosResp = await axios.get(`/estruturas/${structureId.value}/comentarios`);
        if (comentariosResp.status >= 200 && comentariosResp.status < 300) {
            structure.value.comentarios = comentariosResp.data;
        } else {
            toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível carregar os comentários', life: 3000 });
        }
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Falha de comunicação ao carregar comentários', life: 3000 });
    }
}

async function adicionarComentario() {
    if (!novoComentario.value.trim()) return;
    try {
        const response = await axios.post(
            `/estruturas/${structureId.value}/comentarios`,
            { texto: novoComentario.value.trim() },
            { headers: { 'Content-Type': 'application/json' } }
        );
        if (response.status === 201 || response.status === 200) {
            novoComentario.value = '';
            await recarregarComentarios(); // Recarrega os comentários após adicionar
        } else {
            toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível adicionar o comentário', life: 3000 });
        }
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Falha de comunicação com o servidor', life: 3000 });
    }
}

function exportarEstrutura() {
    exportarEstruturaUtil(structure.value);
}

// Arquivos de classificação
const arquivosClassificacao = ref([]);
const uploadLoading = ref(false);

// Carregar arquivos existentes
async function carregarArquivosClassificacao() {
    if (!structureId.value) return;
    try {
        const resp = await axios.get(`/estruturas/${structureId.value}/arquivos-classificacao`);
        arquivosClassificacao.value = resp.data;
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Falha ao carregar arquivos', life: 3000 });
    }
}

// Upload de arquivos
async function uploadArquivosClassificacao(event) {
    const files = event.target.files;
    if (!files.length) return;
    uploadLoading.value = true;
    const formData = new FormData();
    for (const file of files) {
        formData.append('files[]', file);
    }
    try {
        await axios.post(
            `/estruturas/${structureId.value}/arquivos-classificacao`,
            formData,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );
        await carregarArquivosClassificacao();
        toast.add({ severity: 'success', summary: 'Sucesso', detail: 'Arquivo(s) enviado(s) com sucesso', life: 3000 });
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Falha ao enviar arquivo(s)', life: 3000 });
    }
    uploadLoading.value = false;
    event.target.value = '';
}

// Download de arquivo
async function downloadArquivoClassificacao(arquivo) {
    try {
        // Faz a solicitação para o backend para obter o arquivo
        const response = await axios.get(
            `/estruturas/${structureId.value}/arquivos-classificacao/${arquivo.id}/download`,
            { responseType: 'blob' }
        );

        // Cria uma URL temporária
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', arquivo.nome_arquivo); 
        document.body.appendChild(link);
        link.click(); // Inicia o download
        document.body.removeChild(link); // Remove o link temporário
    } catch (error) {
        console.error('Erro ao baixar o arquivo:', error);
        toast.add({
            severity: 'error',
            summary: 'Erro',
            detail: 'Falha ao baixar o arquivo',
            life: 3000
        });
    }
}

const audits = ref([]);
const auditFilters = ref({
    user: '',
    date: '',
    field: ''
});

// Carregar auditoria ordenada por data decrescente
async function carregarAuditoria() {
    if (!structureId.value) return;
    try {
        const resp = await axios.get(`/estruturas/${structureId.value}/auditoria?order=desc`);
        audits.value = resp.data;
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Falha ao carregar histórico de alterações', life: 3000 });
    }
}

onMounted(async () => {
    // ...existing code...
    await carregarAuditoria();
});

// Filtro de auditoria
const filteredAudits = computed(() => {
    return audits.value.filter(audit => {
        const userMatch = auditFilters.value.user ? (audit.user?.name || '').toLowerCase().includes(auditFilters.value.user.toLowerCase()) : true;
        const dateMatch = auditFilters.value.date ? audit.created_at.startsWith(auditFilters.value.date) : true;
        const fieldMatch = auditFilters.value.field
            ? Object.keys(audit.changes || {}).some(f => f.toLowerCase().includes(auditFilters.value.field.toLowerCase()))
            : true;
        return userMatch && dateMatch && fieldMatch;
    });
});

// Adicione este computed para gerar as opções do filtro de campo
const auditFieldOptions = computed(() => {
    // Extrai todos os campos únicos dos registros de auditoria
    const fields = new Set();
    audits.value.forEach(audit => {
        Object.keys(audit.changes || {}).forEach(field => fields.add(field));
    });
    // Ordena os campos alfabeticamente antes de mapear para o formato do Select
    return Array.from(fields)
        .sort((a, b) => a.localeCompare(b))
        .map(field => ({ label: field, value: field }));
});

// Mapeamento dos nomes dos campos para exibição amigável
const auditFieldLabels = {
    nome: 'Nome',
    finalidade: 'Finalidade',
    projetistas: 'Projetistas',
    status: 'Status',
    classificacao_federal: 'Classificação Federal',
    classificacao_estadual: 'Classificação Estadual',
    classificacao_cda: 'Classificação CDA',
    elevacao_crista: 'Elevação da Crista',
    unidade_elevacao_crista: 'Unidade da Elevação da Crista',
    altura_maxima_federal: 'Altura máxima da barragem (Lei Federal 14.066/2020)',
    unidade_altura_maxima_federal: 'Unidade da Altura Máxima Federal',
    altura_maxima_estadual: 'Altura máxima da barragem (Lei Estadual 23.291/2019)',
    unidade_altura_maxima_estadual: 'Unidade da Altura Máxima Estadual',
    largura_coroamento: 'Largura/comprimento do coroamento',
    area_reservatorio_crista: 'Área do reservatório (até a crista)',
    unidade_area_reservatorio_crista: 'Unidade da Área do Reservatório (Crista)',
    area_reservatorio_soleira: 'Área do reservatório (até a soleira)',
    unidade_area_reservatorio_soleira: 'Unidade da Área do Reservatório (Soleira)',
    elevacao_base: 'Elevação da Base',
    unidade_elevacao_base: 'Unidade da Elevação da Base',
    altura_maxima_entre_bermas: 'Altura Máxima entre Bermas',
    unidade_altura_maxima_entre_bermas: 'Unidade da Altura Máxima entre Bermas',
    largura_bermas: 'Largura das bermas',
    unidade_largura_bermas: 'Unidade da Largura das Bermas',
    tipo_secao: 'Tipo de seção',
    drenagem_interna: 'Drenagem interna',
    instrumentacao: 'Instrumentação',
    fundacao: 'Fundação',
    analises_estabilidade: 'Análises de Estabilidade e Percolação',
    area_bacia_contribuicao: 'Área da Bacia de Contribuição',
    unidade_area_bacia_contribuicao: 'Unidade da Área da Bacia de Contribuição',
    area_espelho_dagua: "Área do Espelho d'água",
    unidade_area_espelho_dagua: "Unidade da Área do Espelho d'água",
    na_maximo_operacional: 'NA Máximo Operacional',
    unidade_na_maximo_operacional: 'Unidade do NA Máximo Operacional',
    na_maximo_maximorum: 'NA Máximo Maximorum',
    unidade_na_maximo_maximorum: 'Unidade do NA Máximo Maximorum',
    borda_livre: 'Borda Livre (NA Máximo Maximorum)',
    unidade_borda_livre: 'Unidade da Borda Livre',
    volume_transito_cheias: 'Volume disponível para trânsito de cheias',
    unidade_volume_transito_cheias: 'Unidade do Volume para Trânsito de Cheias',
    sistema_extravasor: 'Sistema Extravasor',
    file_upload: 'Upload de Arquivo'
};

// Função para obter o label amigável do campo
function getAuditFieldLabel(field) {
    return auditFieldLabels[field] || field;
}

function formatDateTime(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleString('pt-BR');
}
</script>

<template>
    <div class="structures-edit p-4">
        <div>
            <h1 class="text-3xl font-medium text-900 m-0">{{ formTitle }}</h1>
            <Breadcrumb :model="breadcrumbItems" class="my-6 text-sm rounded-lg" />
        </div>

        <Card class="mb-4">
            <template #content>
                <form @submit.prevent="saveStructure" class="p-fluid">
                    <Tabs class="mb-4" v-model:value="activeTab">
                        <TabList>
                            <Tab :value="0">Informações Básicas</Tab>
                            <Tab :value="1">Medidas Técnicas</Tab>
                            <Tab :value="2">Detalhes Estruturais</Tab>
                            <Tab :value="3">Parâmetros Hidráulicos</Tab>
                            <Tab v-if="isAdmin || isSuperAdmin || canEdit" :value="4">Histórico de Alterações</Tab>
                        </TabList>

                        <TabPanels>
                            <TabPanel :value="0">
                                <div class="grid gap-4">
                                    <div class="col-span-12 mb-4">
                                        <label for="nome" class="block mb-2">Nome <span style="color: red;">*</span></label>
                                        <InputText fluid id="nome" v-model="structure.nome" :class="{ 'p-invalid': validationErrors.nome }" placeholder="Digite o nome da estrutura" />
                                        <small v-if="validationErrors.nome" class="p-error">{{ validationErrors.nome }}</small>
                                    </div>
                                    <div class="col-span-12 md:col-span-12 mb-4">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label for="finalidade" class="block mb-2">Finalidade</label>
                                                <InputText fluid id="finalidade" v-model="structure.finalidade" :class="{ 'p-invalid': validationErrors.finalidade }" />
                                                <small v-if="validationErrors.finalidade" class="p-error">{{ validationErrors.finalidade }}</small>
                                            </div>
                                            <div>
                                                <label for="status" class="block mb-2">Status</label>
                                                <Select fluid id="status" v-model="structure.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Selecione o status" :class="{ 'p-invalid': validationErrors.status }" />
                                                <small v-if="validationErrors.status" class="p-error">{{ validationErrors.status }}</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-span-12 mb-4">
                                        <label for="projetistas" class="block mb-2">Projetistas</label>
                                        <Textarea fluid id="projetistas" v-model="structure.projetistas" rows="3" :class="{ 'p-invalid': validationErrors.projetistas }" />
                                        <small v-if="validationErrors.projetistas" class="p-error">{{ validationErrors.projetistas }}</small>
                                    </div>

                                    <div class="col-span-12">
                                        <Divider>Classificação</Divider>
                                    </div>

                                    <div class="col-span-12 md:col-4 mb-4">
                                        <label for="classificacao_federal" class="block mb-2">Classificação Federal</label>
                                        <Select
                                            fluid
                                            id="classificacao_federal"
                                            v-model="structure.classificacao_federal"
                                            :options="structureStore.federalClassificationOptions"
                                            optionLabel="label"
                                            optionValue="value"
                                            placeholder="Selecione a classificação"
                                            :class="{ 'p-invalid': validationErrors.classificacao_federal }"
                                        />
                                        <small v-if="validationErrors.classificacao_federal" class="p-error">{{ validationErrors.classificacao_federal }}</small>
                                    </div>

                                    <div class="col-span-12 md:col-4 mb-4">
                                        <label for="classificacao_estadual" class="block mb-2">Classificação Estadual</label>
                                        <Select
                                            fluid
                                            id="classificacao_estadual"
                                            v-model="structure.classificacao_estadual"
                                            :options="structureStore.stateClassificationOptions"
                                            optionLabel="label"
                                            optionValue="value"
                                            placeholder="Selecione a classificação"
                                            :class="{ 'p-invalid': validationErrors.classificacao_estadual }"
                                        />
                                        <small v-if="validationErrors.classificacao_estadual" class="p-error">{{ validationErrors.classificacao_estadual }}</small>
                                    </div>

                                    <div class="col-span-12 md:col-4 mb-4">
                                        <label for="classificacao_cda" class="block mb-2">Classificação CDA</label>
                                        <Select
                                            fluid
                                            id="classificacao_cda"
                                            v-model="structure.classificacao_cda"
                                            :options="cdaClassificationOptions"
                                            optionLabel="label"
                                            optionValue="value"
                                            placeholder="Selecione a classificação"
                                            :class="{ 'p-invalid': validationErrors.classificacao_cda }"
                                        />
                                        <small v-if="validationErrors.classificacao_cda" class="p-error">{{ validationErrors.classificacao_cda }}</small>
                                    </div>

                                    <div class="col-span-12 mb-4">
                                        <label class="block mb-2 font-semibold">Arquivos de Classificação</label>
                                        <div class="flex flex-col gap-2">
                                            <div class="custom-file-upload-wrapper" v-if="isAdmin || isSuperAdmin || canEdit">
                                                <input
                                                    id="file-upload"
                                                    type="file"
                                                    multiple
                                                    accept=".pdf,.csv,.xlsx,.ods"
                                                    @change="uploadArquivosClassificacao"
                                                    :disabled="uploadLoading"
                                                    class="hidden"
                                                />
                                                <label for="file-upload" class="custom-file-upload-btn">
                                                    <i class="pi pi-upload mr-2"></i>
                                                    <span>{{ uploadLoading ? 'Enviando...' : 'Carregar Arquivos' }}</span>
                                                </label>
                                            </div>
                                            <small class="text-gray-500 ml-0">PDF / CSV / XLSX / ODS</small>
                                            <div v-if="arquivosClassificacao.length">
                                                <ul class="list-disc ml-4">
                                                    <li v-for="arquivo in arquivosClassificacao" :key="arquivo.id" class="flex items-center gap-2">
                                                        <span>{{ arquivo.nome_arquivo }}</span>
                                                        <Button
                                                            icon="pi pi-download"
                                                            size="small"
                                                            text
                                                            @click="downloadArquivoClassificacao(arquivo)"
                                                            title="Baixar"
                                                        />
                                                    </li>
                                                </ul>
                                            </div>
                                            <div v-else class="text-gray-400">Nenhum arquivo enviado.</div>
                                        </div>
                                    </div>
                                </div>
                            </TabPanel>

                            <TabPanel :value="1">
                                <div class="grid gap-4">
                                    <div class="col-span-12 md:col-span-6 mb-4">
                                        <label for="elevacao_crista" class="block mb-2">Elevação da Crista</label>
                                        <div class="flex gap-2">
                                            <InputNumber fluid id="elevacao_crista" v-model="structure.elevacao_crista" :minFractionDigits="1" :maxFractionDigits="3" :class="{ 'p-invalid': validationErrors.elevacao_crista }" />
                                            <Select v-model="structure.unidade_elevacao_crista" :options="unidadeComprimento" class="w-24" />
                                        </div>
                                        <small v-if="validationErrors.elevacao_crista" class="p-error">{{ validationErrors.elevacao_crista }}</small>
                                    </div>

                                    <div class="col-span-12 md:col-span-6 mb-4">
                                        <label for="elevacao_base" class="block mb-2">Elevação da Base</label>
                                        <div class="flex gap-2">
                                            <InputNumber fluid id="elevacao_base" v-model="structure.elevacao_base" :minFractionDigits="1" :maxFractionDigits="3" :class="{ 'p-invalid': validationErrors.elevacao_base }" />
                                            <Select v-model="structure.unidade_elevacao_base" :options="unidadeComprimento" class="w-24" />
                                        </div>
                                        <small v-if="validationErrors.elevacao_base" class="p-error">{{ validationErrors.elevacao_base }}</small>
                                    </div>

                                    <div class="col-span-12 md:col-span-6 mb-4">
                                        <label for="altura_maxima_federal" class="block mb-2">Altura máxima da barragem (Lei Federal 14.066/2020)</label>
                                        <div class="flex gap-2">
                                            <InputNumber fluid id="altura_maxima_federal" v-model="structure.altura_maxima_federal" :minFractionDigits="1" :maxFractionDigits="3" :class="{ 'p-invalid': validationErrors.altura_maxima_federal }" />
                                            <Select v-model="structure.unidade_altura_maxima_federal" :options="unidadeComprimento" class="w-24" />
                                        </div>
                                        <small v-if="validationErrors.altura_maxima_federal" class="p-error">{{ validationErrors.altura_maxima_federal }}</small>
                                    </div>

                                    <div class="col-span-12 md:col-span-6 mb-4">
                                        <label for="altura_maxima_estadual" class="block mb-2">Altura máxima da barragem (Lei Estadual 23.291/2019)</label>
                                        <div class="flex gap-2">
                                            <InputNumber fluid id="altura_maxima_estadual" v-model="structure.altura_maxima_estadual" :minFractionDigits="1" :maxFractionDigits="3" :class="{ 'p-invalid': validationErrors.altura_maxima_estadual }" />
                                            <Select v-model="structure.unidade_altura_maxima_estadual" :options="unidadeComprimento" class="w-24" />
                                        </div>
                                        <small v-if="validationErrors.altura_maxima_estadual" class="p-error">{{ validationErrors.altura_maxima_estadual }}</small>
                                    </div>

                                    <div class="col-span-12 mb-4">
                                        <label for="largura_coroamento" class="block mb-2">Largura/comprimento do coroamento</label>
                                        <div class="flex gap-2">
                                            <InputText fluid id="largura_coroamento" v-model="structure.largura_coroamento" :class="{ 'p-invalid': validationErrors.largura_coroamento }" />
                                        </div>
                                        <small v-if="validationErrors.largura_coroamento" class="p-error">{{ validationErrors.largura_coroamento }}</small>
                                    </div>

                                    <div class="col-span-12 md:col-span-6 mb-4">
                                        <label for="area_reservatorio_crista" class="block mb-2">Área do reservatório (até a crista)</label>
                                        <div class="flex gap-2">
                                            <InputNumber fluid id="area_reservatorio_crista" v-model="structure.area_reservatorio_crista" :minFractionDigits="1" :maxFractionDigits="3" :class="{ 'p-invalid': validationErrors.area_reservatorio_crista }" />
                                            <Select v-model="structure.unidade_area_reservatorio_crista" :options="unidadesArea" class="w-24" />
                                        </div>
                                        <small v-if="validationErrors.area_reservatorio_crista" class="p-error">{{ validationErrors.area_reservatorio_crista }}</small>
                                    </div>

                                    <div class="col-span-12 md:col-span-6 mb-4">
                                        <label for="area_reservatorio_soleira" class="block mb-2">Área do reservatório (até a soleira)</label>
                                        <div class="flex gap-2">
                                            <InputNumber
                                                fluid
                                                id="area_reservatorio_soleira"
                                                v-model="structure.area_reservatorio_soleira"
                                                :minFractionDigits="1"
                                                :maxFractionDigits="3"
                                                :class="{ 'p-invalid': validationErrors.area_reservatorio_soleira }"
                                            />
                                            <Select v-model="structure.unidade_area_reservatorio_soleira" :options="unidadesArea" class="w-24" />
                                        </div>
                                        <small v-if="validationErrors.area_reservatorio_soleira" class="p-error">{{ validationErrors.area_reservatorio_soleira }}</small>
                                    </div>

                                    <div class="col-span-12 md:col-span-6 mb-4">
                                        <label for="altura_maxima_entre_bermas" class="block mb-2">Altura Máxima entre Bermas</label>
                                        <div class="flex gap-2">
                                            <InputNumber
                                                fluid
                                                id="altura_maxima_entre_bermas"
                                                v-model="structure.altura_maxima_entre_bermas"
                                                :minFractionDigits="1"
                                                :maxFractionDigits="3"
                                                :class="{ 'p-invalid': validationErrors.altura_maxima_entre_bermas }"
                                            />
                                            <Select v-model="structure.unidade_altura_maxima_entre_bermas" :options="unidadeComprimento" class="w-24" />
                                        </div>
                                        <small v-if="validationErrors.altura_maxima_entre_bermas" class="p-error">{{ validationErrors.altura_maxima_entre_bermas }}</small>
                                    </div>

                                    <div class="col-span-12 md:col-span-6 mb-4">
                                        <label for="largura_bermas" class="block mb-2">Largura das bermas</label>
                                        <div class="flex gap-2">
                                            <InputNumber fluid id="largura_bermas" v-model="structure.largura_bermas" :minFractionDigits="1" :maxFractionDigits="3" :class="{ 'p-invalid': validationErrors.largura_bermas }" />
                                            <Select v-model="structure.unidade_largura_bermas" :options="unidadeComprimento" class="w-24" />
                                        </div>
                                        <small v-if="validationErrors.largura_bermas" class="p-error">{{ validationErrors.largura_bermas }}</small>
                                    </div>
                                </div>
                            </TabPanel>

                            <TabPanel :value="2">
                                <div class="grid gap-4">
                                    <div class="col-span-12 mb-4">
                                        <label for="tipo_secao" class="block mb-2">Tipo de seção</label>
                                        <Textarea fluid id="tipo_secao" v-model="structure.tipo_secao" rows="3" :class="{ 'p-invalid': validationErrors.tipo_secao }" />
                                        <small v-if="validationErrors.tipo_secao" class="p-error">{{ validationErrors.tipo_secao }}</small>
                                    </div>

                                    <div class="col-span-12 mb-4">
                                        <label for="drenagem_interna" class="block mb-2">Drenagem interna</label>
                                        <Textarea fluid id="drenagem_interna" v-model="structure.drenagem_interna" rows="3" :class="{ 'p-invalid': validationErrors.drenagem_interna }" />
                                        <small v-if="validationErrors.drenagem_interna" class="p-error">{{ validationErrors.drenagem_interna }}</small>
                                    </div>

                                    <div class="col-span-12 mb-4">
                                        <label for="instrumentacao" class="block mb-2">Instrumentação</label>
                                        <Textarea fluid id="instrumentacao" v-model="structure.instrumentacao" rows="3" :class="{ 'p-invalid': validationErrors.instrumentacao }" />
                                        <small v-if="validationErrors.instrumentacao" class="p-error">{{ validationErrors.instrumentacao }}</small>
                                    </div>

                                    <div class="col-span-12 mb-4">
                                        <label for="fundacao" class="block mb-2">Fundação</label>
                                        <Textarea fluid id="fundacao" v-model="structure.fundacao" rows="3" :class="{ 'p-invalid': validationErrors.fundacao }" />
                                        <small v-if="validationErrors.fundacao" class="p-error">{{ validationErrors.fundacao }}</small>
                                    </div>

                                    <div class="col-span-12 mb-4">
                                        <label for="analises_estabilidade" class="block mb-2">Análises de Estabilidade e Percolação</label>
                                        <Textarea fluid id="analises_estabilidade" v-model="structure.analises_estabilidade" rows="3" :class="{ 'p-invalid': validationErrors.analises_estabilidade }" />
                                        <small v-if="validationErrors.analises_estabilidade" class="p-error">{{ validationErrors.analises_estabilidade }}</small>
                                    </div>
                                </div>
                            </TabPanel>

                            <TabPanel :value="3">
                                <div class="grid gap-4">
                                    <div class="col-span-12 md:col-span-6 mb-4">
                                        <label for="area_bacia_contribuicao" class="block mb-2">Área da Bacia de Contribuição</label>
                                        <div class="flex gap-2">
                                            <InputNumber fluid id="area_bacia_contribuicao" v-model="structure.area_bacia_contribuicao" :minFractionDigits="1" :maxFractionDigits="3" :class="{ 'p-invalid': validationErrors.area_bacia_contribuicao }" />
                                            <Select v-model="structure.unidade_area_bacia_contribuicao" :options="unidadesArea" class="w-24" />
                                        </div>
                                        <small v-if="validationErrors.area_bacia_contribuicao" class="p-error">{{ validationErrors.area_bacia_contribuicao }}</small>
                                    </div>

                                    <div class="col-span-12 md:col-span-6 mb-4">
                                        <label for="waterMirrorArea" class="block mb-2">Área do Espelho d'água</label>
                                        <div class="flex gap-2">
                                            <InputNumber fluid id="waterMirrorArea" v-model="structure.area_espelho_dagua" :minFractionDigits="1" :maxFractionDigits="3" :class="{ 'p-invalid': validationErrors.area_espelho_dagua }" />
                                            <Select v-model="structure.unidade_area_espelho_dagua" :options="unidadesArea" class="w-24" />
                                        </div>
                                        <small v-if="validationErrors.area_espelho_dagua" class="p-error">{{ validationErrors.area_espelho_dagua }}</small>
                                    </div>

                                    <div class="col-span-12 md:col-span-6 mb-4">
                                        <label for="maximumOperationalNA" class="block mb-2">NA Máximo Operacional</label>
                                        <div class="flex gap-2">
                                            <InputNumber fluid id="maximumOperationalNA" v-model="structure.na_maximo_operacional" :minFractionDigits="1" :maxFractionDigits="3" :class="{ 'p-invalid': validationErrors.na_maximo_operacional }" />
                                            <Select v-model="structure.unidade_na_maximo_operacional" :options="unidadeComprimento" class="w-24" />
                                        </div>
                                        <small v-if="validationErrors.na_maximo_operacional" class="p-error">{{ validationErrors.na_maximo_operacional }}</small>
                                    </div>

                                    <div class="col-span-12 md:col-span-6 mb-4">
                                        <label for="maximumMaximumNA" class="block mb-2">NA Máximo Maximorum</label>
                                        <div class="flex gap-2">
                                            <InputNumber fluid id="maximumMaximumNA" v-model="structure.na_maximo_maximorum" :minFractionDigits="1" :maxFractionDigits="3" :class="{ 'p-invalid': validationErrors.na_maximo_maximorum }" />
                                            <Select v-model="structure.unidade_na_maximo_maximorum" :options="unidadeComprimento" class="w-24" />
                                        </div>
                                        <small v-if="validationErrors.na_maximo_maximorum" class="p-error">{{ validationErrors.na_maximo_maximorum }}</small>
                                    </div>

                                    <div class="col-span-12 md:col-span-6 mb-4">
                                        <label for="freeBoard" class="block mb-2">Borda Livre (NA Máximo Maximorum)</label>
                                        <div class="flex gap-2">
                                            <InputNumber fluid id="freeBoard" v-model="structure.borda_livre" :minFractionDigits="1" :maxFractionDigits="3" :class="{ 'p-invalid': validationErrors.borda_livre }" />
                                            <Select v-model="structure.unidade_borda_livre" :options="unidadeComprimento" class="w-24" />
                                        </div>
                                        <small v-if="validationErrors.borda_livre" class="p-error">{{ validationErrors.borda_livre }}</small>
                                    </div>

                                    <div class="col-span-12 md:col-span-6 mb-4">
                                        <label for="availableVolumeForFloodTransit" class="block mb-2">Volume disponível para trânsito de cheias</label>
                                        <div class="flex gap-2">
                                            <InputNumber
                                                fluid
                                                id="availableVolumeForFloodTransit"
                                                v-model="structure.volume_transito_cheias"
                                                :minFractionDigits="1"
                                                :maxFractionDigits="3"
                                                :class="{ 'p-invalid': validationErrors.volume_transito_cheias }"
                                            />
                                            <Select v-model="structure.unidade_volume_transito_cheias" :options="unidadesVolume" class="w-24" />
                                        </div>
                                        <small v-if="validationErrors.volume_transito_cheias" class="p-error">{{ validationErrors.volume_transito_cheias }}</small>
                                    </div>

                                    <div class="col-span-12 mb-4">
                                        <label for="spillwaySystem" class="block mb-2">Sistema Extravasor</label>
                                        <Textarea fluid id="spillwaySystem" v-model="structure.sistema_extravasor" rows="3" :class="{ 'p-invalid': validationErrors.sistema_extravasor }" />
                                        <small v-if="validationErrors.sistema_extravasor" class="p-error">{{ validationErrors.sistema_extravasor }}</small>
                                    </div>
                                </div>
                            </TabPanel>

                            <TabPanel :value="4" v-if="isAdmin || isSuperAdmin || canEdit">
                                <div class="grid gap-4">
                                    <!-- Filtros -->
                                    <div class="flex flex-wrap gap-2 mb-4 items-center">
                                        <InputText v-model="auditFilters.user" placeholder="Usuário" class="w-40" />
                                        <Calendar
                                            v-model="auditFilters.date"
                                            placeholder="Data"
                                            dateFormat="dd/mm/yy"
                                            class="w-40"
                                            showIcon
                                            @update:modelValue="val => auditFilters.date = val ? val.toISOString().slice(0,10) : ''"
                                        />
                                        <Select
                                            v-model="auditFilters.field"
                                            :options="auditFieldOptions"
                                            optionLabel="label"
                                            optionValue="value"
                                            placeholder="Campo"
                                            class="w-40"
                                            clearable
                                        />
                                        <!-- Botão de limpar filtros -->
                                        <Button
                                            icon="pi pi-filter-slash"
                                            class="p-button-text"
                                            style="margin-left: 0.5rem;"
                                            @click="() => { auditFilters.user = ''; auditFilters.date = ''; auditFilters.field = ''; }"
                                            title="Limpar filtros"
                                        />
                                        <!-- Botão de recarregar -->
                                        <Button
                                            icon="pi pi-refresh"
                                            class="p-button-text"
                                            style="margin-left: 0.5rem;"
                                            @click="carregarAuditoria"
                                            title="Recarregar histórico"
                                        />
                                    </div>
                                    <div v-if="filteredAudits.length === 0" class="text-gray-400">Nenhum registro encontrado.</div>
                                    <div v-for="audit in filteredAudits" :key="audit.id" class="mb-4">
                                        <details>
                                            <summary class="font-semibold cursor-pointer">
                                                {{ audit.user?.name || 'Desconhecido' }} – {{ formatDateTime(audit.created_at) }}
                                            </summary>
                                            <div class="mt-2">
                                                <table class="min-w-full border mb-2">
                                                    <thead>
                                                        <tr class="bg-gray-100">
                                                            <th class="px-2 py-1 border">CAMPO</th>
                                                            <th class="px-2 py-1 border">VALOR ANTERIOR</th>
                                                            <th class="px-2 py-1 border">VALOR NOVO</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(change, field) in audit.changes" :key="field">
                                                            <td class="px-2 py-1 border">{{ getAuditFieldLabel(field) }}</td>
                                                            <td class="px-2 py-1 border">{{ change.old }}</td>
                                                            <td class="px-2 py-1 border">{{ change.new }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <div class="text-sm text-gray-700"><strong>Justificativa:</strong> {{ audit.justificativa || 'Não informada' }}</div>
                                            </div>
                                        </details>
                                    </div>
                                </div>
                            </TabPanel>
                        </TabPanels>
                    </Tabs>
                    
                    <Tabs class="mb-4" style="margin-top: 2rem;">
                        <TabPanels>
                            <TabPanel>
                                <!-- Botões de Exportar e Sair -->
                                <div class="flex justify-content-end gap-3 mt-4">
                                    <Button label="Exportar estrutura" icon="pi pi-download" class="p-button-primary" style="background-color: #2196F3; border-color: #2196F3;" @click="exportarEstrutura" type="button" />
                                    <Button label="Sair" severity="secondary" outlined @click="cancel" type="button" v-if="!(isAdmin || isSuperAdmin || canEdit)" />
                                </div>

                                <!-- Campo de Justificativa -->
                                <div v-if="isAdmin || isSuperAdmin || canEdit" class="mt-4">
                                    <label for="justificativa" class="block mb-2 font-semibold">Justificativa da Modificação <span style="color: red;">*Obrigatório</span>
                                    </label><Textarea v-model="justificativa" id="justificativa" rows="3" placeholder="Informe a justificativa para a modificação" class="w-full" />
                                </div> 

                                <!-- Botões de Cancelar e Salvar -->
                                <div v-if="isAdmin || isSuperAdmin || canEdit" class="flex justify-end gap-3 mt-4">
                                    <Button label="Cancelar" severity="secondary" outlined @click="cancel" type="button" />
                                    <Button label="Salvar" icon="pi pi-save" type="submit" />
                                </div>
                            </TabPanel>
                        </TabPanels>
                    </Tabs>
                </form>
            </template>
        </Card>

        <!-- Quadro de Comentários -->
        <Card class="mb-4">
            <template #content>
                <h2 class="text-xl font-semibold mb-3">Comentários</h2>
                <div class="flex flex-col gap-2 mb-4">
                    <Textarea v-model="novoComentario" placeholder="Adicionar comentário..." class="flex-1" :rows="4" />
                    <!-- Botão Comentar -->
                    <div class="flex justify-end gap-2">
                        <Button
                            icon="pi pi-refresh"
                            class="p-button-text"
                            title="Recarregar comentários"
                            @click="recarregarComentarios"
                            size="small"
                        />
                        <Button label="Comentar" icon="pi pi-send" @click="adicionarComentario" size="small" />
                    </div>
                </div>
                <div v-if="structure.comentarios && structure.comentarios.length">
                    <div
                        v-for="(comentario, idx) in structure.comentarios"
                        :key="idx"
                        class="mb-3 p-3 border rounded bg-gray-50"
                    >
                        <div class="text-xs text-gray-500 mb-1">
                            <span>{{ comentario.user_nome || 'Usuário desconhecido' }}</span> - <span>{{ new Date(comentario.data).toLocaleString('pt-BR') }}</span>
                        </div>
                        <div>{{ comentario.texto }}</div>
                    </div>
                </div>
                <div v-else class="mb-3 text-gray-400">Nenhum comentário ainda.</div>
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
