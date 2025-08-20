import { makeApiRequest } from '@/libs/helpers';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

export const useAccessGroupStore = defineStore('accessGroups', () => {
    const accessGroups = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const pagination = ref({
        page: 1,
        perPage: 10,
        total: 0
    });

    const filters = ref([]);

    // Filtered access groups
    const filteredAccessGroups = computed(() => {
        return accessGroups.value;
    });

    const paginatedAccessGroups = computed(() => {
        const start = (pagination.value.page - 1) * pagination.value.perPage;
        const end = start + pagination.value.perPage;
        return filteredAccessGroups.value.slice(start, end);
    });

    // Actions
    async function getAccessGroupById(id) {
        loading.value = true;
        error.value = null;

        const { data, error: errorResponse } = await makeApiRequest({
            url: `/grupos-acesso/${id}`,
            method: 'get'
        });

        if (errorResponse.value) return (error.value = 'Failed to fetch access group');
        if (!data.value) return (error.value = 'Access group not found');

        return data.value;
    }

    async function getAllUsers() {
        loading.value = true;
        error.value = null;

        const { data: dataResponse, error: errorResponse } = await makeApiRequest({
            url: '/users',
            method: 'get'
        });

        loading.value = false;

        if (errorResponse.value) {
            error.value = 'Failed to fetch users';
            return [];
        }

        return dataResponse.value?.data || [];
    }

    async function getStructureOptions() {
        loading.value = true;
        error.value = null;

        const { data: dataResponse, error: errorResponse } = await makeApiRequest({
            url: '/estruturas',
            method: 'get'
        });

        if (errorResponse.value) {
            loading.value = false;
            error.value = 'Failed to fetch structure options';
            return [];
        }

        loading.value = false;
        return dataResponse.value?.data || [];
    }

    async function fetchAccessGroups() {
        loading.value = true;
        error.value = null;

        const { data: dataResponse, error: errorResponse } = await makeApiRequest({
            url: '/grupos-acesso',
            method: 'get'
        });

        if (errorResponse.value) return (error.value = 'Failed to fetch access groups');

        accessGroups.value = dataResponse.value?.data || [];
        pagination.value.total = dataResponse.value?.total || accessGroups.value.length;
        loading.value = false;

        return accessGroups.value;
    }

    async function createAccessGroup(payload) {
        loading.value = true;
        error.value = null;

        const { data: dataResponse, error: errorResponse } = await makeApiRequest({
            url: '/grupos-acesso',
            method: 'post',
            requestData: payload
        });

        if (dataResponse.value?.error) {
            loading.value = false;
            error.value = dataResponse.value.error;
            return { error: dataResponse.value.error, data: null };
        }

        if (errorResponse.value) return { error: 'Failed to create access group', data: null };
        accessGroups.value.push(dataResponse.value?.data || {});
        loading.value = false;

        return { data: 'Access group created successfully', error: null };
    }

    async function removeUserFromAccessGroup(groupId, userId) {
        loading.value = true;
        error.value = null;

        const { error: errorResponse } = await makeApiRequest({
            url: `/grupos-acesso/${groupId}/usuarios`,
            method: 'delete',
            params: { usuario_id: userId }
        });

        if (errorResponse.value) {
            loading.value = false;
            error.value = 'Failed to remove user from access group';
            return false;
        }

        return true;
    }

    // /api/grupos-acesso/{grupoId}/usuarios
    async function addUserToAccessGroup(groupId, userId) {
        loading.value = true;
        error.value = null;

        try {
            const response = await makeApiRequest({
                url: `/grupos-acesso/${groupId}/usuarios`,
                method: 'post',
                requestData: JSON.stringify({ usuario_id: userId })
            });

            
            if (response.error.value && response.error.value.response && response.error.value.response.status === 422) {
                const backendMsg = response.error.value.response.data?.error || 'Usuário não pode ser adicionado!';
                return { error: backendMsg, data: null };
            }

            if (response.error.value) {
                error.value = 'Failed to add user to access group';
                return { error: 'Erro ao adicionar usuário', data: null };
            }

            return { error: null, data: response.data.value };
        } finally {
            loading.value = false;
        }
    }

    async function getUsersByAccessGroupId(groupId) {
        loading.value = true;
        error.value = null;

        const { data: dataResponse, error: errorResponse } = await makeApiRequest({
            url: `/grupos-acesso/${groupId}/usuarios`,
            method: 'get'
        });

        if (errorResponse.value) {
            loading.value = false;
            error.value = 'Failed to add users to access group';
            return false;
        }

        return dataResponse.value?.data || [];
    }

    async function updateAccessGroup(id, groupData) {
        loading.value = true;
        error.value = null;

        console.log('Updating access group:', id, groupData, accessGroups.value);
        // const index = accessGroups.value.findIndex((group) => group.id == id);
        // if (index === -1) return { error: 'Access group not found', data: null };

        const { data: dataResponse, error: errorResponse } = await makeApiRequest({
            url: `/grupos-acesso/${id}`,
            method: 'put',
            requestData: groupData
        });

        loading.value = false;
        if (errorResponse.value) return { error: 'Failed to update access group', data: null };
        // accessGroups.value[index] = dataResponse.value?.data || [];

        // refresh the access group list
        return { data: 'Access group updated successfully', error: null };
        const index = accessGroups.value.findIndex((group) => group.id == id);
        if (index === -1) return { error: 'Access group not found', data: null };
    }

    async function deleteAccessGroup(id) {
        // loading.value = true;
        error.value = null;

        const index = accessGroups.value.findIndex((group) => group.id == id);
        const { error: errorResponse } = await makeApiRequest({
            url: `/grupos-acesso/${id}`,
            method: 'delete'
        });

        // loading.value = false;
        if (errorResponse.value) return (error.value = 'Failed to delete access group');
        if (index === -1) return console.error('Access group not found:', id);
        accessGroups.value.splice(index, 1);

        // return true;

        return { data: 'Access group deleted successfully', error: null };
    }

    function setPage(page) {
        pagination.value.page = page;
    }

    function setPerPage(perPage) {
        pagination.value.perPage = perPage;
        pagination.value.page = 1;
    }

    function addFilter(filter) {
        filters.value.push(filter);
        pagination.value.page = 1;
    }

    function removeFilter(index) {
        filters.value.splice(index, 1);
        pagination.value.page = 1;
    }

    function clearFilters() {
        filters.value = [];
        pagination.value.page = 1;
    }

    return {
        accessGroups,
        loading,
        error,
        pagination,
        filters,
        filteredAccessGroups,
        paginatedAccessGroups,
        getAccessGroupById,
        fetchAccessGroups,
        createAccessGroup,
        getStructureOptions,
        addUserToAccessGroup,
        getAllUsers,
        updateAccessGroup,
        removeUserFromAccessGroup,
        getUsersByAccessGroupId,
        deleteAccessGroup,
        setPage,
        setPerPage,
        addFilter,
        removeFilter,
        clearFilters
    };
});
