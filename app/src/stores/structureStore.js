import { defineStore } from 'pinia';
import { computed, ref, onMounted } from 'vue';
import { makeApiRequest } from '@/libs/helpers';
import axios from '@/libs/axios';

// vamos criar um file para salvar os fake responses de cada request da estrutura

export const useStructureStore = defineStore('structures', () => {
    const structures = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const pagination = ref({
        page: 1,
        perPage: 10,
        total: 0
    });

    const filters = ref([]);

    const getStructureById = async (id) => {
        const { data, error: errorResponse } = await makeApiRequest({
            url: `/estruturas/${id}`,
            method: 'get'
        });

        if (errorResponse.value) {
            const message = errorResponse.value?.response?.data?.message;
            return { error: message, data: null };
        }

        return { error: null, data: data.value };
    };

    const filteredStructures = computed(() => {
        if (filters.value.length === 0) return structures.value;
        return structures.value;
    });

    const paginatedStructures = computed(() => {
        const start = (pagination.value.page - 1) * pagination.value.perPage;
        const end = start + pagination.value.perPage;
        return filteredStructures.value.slice(start, end);
    });

    async function fetchStructures() {
        loading.value = true;
        error.value = null;

        const { data, error: errorResponse } = await makeApiRequest({
            url: '/estruturas',
            method: 'get',
            params: {
                page: pagination.value.page,
                perPage: pagination.value.perPage,
                filters: filters.value
            }
        });

        if (errorResponse.value) {
            error.value = 'Failed to fetch structures';
            console.error('Failed to fetch structures:', error.value);

            structures.value = [];
            return;
        }

        loading.value = false;
        structures.value = data.value?.data || [];
        pagination.value.total = data?.total || structures.value.length;
    }

    async function createStructure(structure) {
        loading.value = true;
        error.value = null;

        const { data, error: errorResponse } = await makeApiRequest({
            url: '/estruturas',
            method: 'post',
            requestData: {
                ...structure
            }
        });

        if (errorResponse.value) {
            error.value = 'Failed to create structure';
            console.error('Failed to create structure:', error.value);
            return null;
        }

        structures.value.push(data.value);
        return data.value;
    }

    async function updateStructure(id, structureData) {
        loading.value = true;
        error.value = null;

        const { error: errorResponse } = await makeApiRequest({
            url: `/estruturas/${id}`,
            method: 'put',
            requestData: {
                ...structureData
            }
        });

        if (errorResponse.value) {
            error.value = 'Failed to update structure';
            console.error('Failed to update structure:', errorResponse.value);
            return false;
        }

        fetchStructures();
        return true;
    }

    async function deleteStructure(id) {
        loading.value = true;
        error.value = null;

        const { error: errorResponse } = await makeApiRequest({
            url: `/estruturas/${id}`,
            method: 'delete'
        });

        if (errorResponse.value) {
            error.value = 'Failed to delete structure';
            console.error('Failed to delete structure:', errorResponse.value);
            return false;
        }

        return true;
    }

    function setPage(page) {
        pagination.value.page = page;
    }

    function setPerPage(perPage) {
        pagination.value.perPage = perPage;
        pagination.value.page = 1; // Reset to first page
    }

    function addFilter(filter) {
        filters.value.push(filter);
        pagination.value.page = 1; // Reset to first page
    }

    function removeFilter(index) {
        filters.value.splice(index, 1);
        pagination.value.page = 1; // Reset to first page
    }

    function clearFilters() {
        filters.value = [];
        pagination.value.page = 1; // Reset to first page
    }

    const statusOptions = [
        { label: 'Ativo', value: 'Ativo' },
        { label: 'Inativo', value: 'Inativo' },

        // fake status options
        { label: 'Operante', value: 'Operante' },
        { label: 'Em Construção', value: 'Em Construção' },
        { label: 'Desativada', value: 'Desativada' },
        { label: 'Planejada', value: 'Planejada' }
    ];

    const federalClassificationOptions = ref([]);
    async function fetchFederalClassifications() {
        try {
            const response = await axios.get('/classificacao-estrutura'); // pega todas as classificações
            federalClassificationOptions.value = response.data
            .filter(item => item.tipo === 'federal') // filtra apenas as classificações federais
            .map(item => ({
                label: item.nome,  // o que vai aparecer no Select
                value: item.id  
            }));
        } catch (e) {
            console.error('Erro ao carregar as opções:', e);
        }
    }

    const stateClassificationOptions = ref([]);
    async function fetchStateClassificationOptions() {
        try {
            const response = await axios.get('/classificacao-estrutura'); // pega todas as classificações
            stateClassificationOptions.value = response.data
            .filter(item => item.tipo === 'estadual') // filtra apenas as classificações estaduais
            .map(item => ({
                label: item.nome,  // o que vai aparecer no Select
                value: item.id  
            }));
        } catch (e) {
            console.error('Erro ao carregar as opções:', e);
        }
    }

    const cdaClassificationOptions = [
        { label: 'Alta', value: 'high' },
        { label: 'Média', value: 'medium' },
        { label: 'Baixa', value: 'low' }
    ];

    return {
        structures,
        loading,
        error,
        pagination,
        filters,
        filteredStructures,
        paginatedStructures,
        getStructureById,
        fetchStructures,
        createStructure,
        updateStructure,
        deleteStructure,
        setPage,
        setPerPage,
        addFilter,
        removeFilter,
        clearFilters,
        fetchFederalClassifications,
        fetchStateClassificationOptions,

        // options
        statusOptions,
        federalClassificationOptions,
        stateClassificationOptions,
        cdaClassificationOptions,
    };
});
