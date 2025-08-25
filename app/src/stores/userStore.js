import { makeApiRequest } from '@/libs/helpers';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

export const useUserStore = defineStore('users', () => {
    const me = ref(null);
    const users = ref([]);
    const loading = ref(false);
    const error = ref(null);

    const menu = ref([]);

    const pagination = ref({
        page: 1,
        perPage: 10,
        total: 0
    });

    const filters = ref([]);

    const isAdmin = computed(() => {
        if (!me.value) return false;

        return me.value.role === 'admin';
    });

    const isSuperAdmin = computed(() => {
        if (!me.value) return false;

        return me.value.role === 'superadmin';
    });

    const setUser = (user) => {
        me.value = user;
        isAdmin.value = user?.role === 'admin';
        isSuperAdmin.value = user?.role === 'superadmin';

        setupMenu();
    };

    const setupMenu = () => {
        if (!me.value) return [];

        const adminOnlyMenu = [
            { label: 'Gerenciar Grupos de Acesso', icon: 'pi pi-fw pi-users', to: '/access-groups' },
            { label: 'Gerenciar Usuários', icon: 'pi pi-fw pi-users', to: '/users' },
            { label: isSuperAdmin.value ? 'Gerenciar Empresas' : 'Gerenciar Empresa', icon: 'pi pi-building', to: '/companies' },
        ];
        const superadminOnlyMenu = [
            { label: 'Classificação de Estruturas', icon: 'pi pi-id-card', to: '/classificacao' }
        ]


        const adminMenu = (isAdmin.value || isSuperAdmin.value) ? adminOnlyMenu : [];
        const superadminMenu = isSuperAdmin.value ? superadminOnlyMenu : [];

        menu.value = [
            {
                label: 'Home',
                items: [{ label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/' }]
            },
            {
                label: '',
                items: [
                    { label: 'Gerenciar Estruturas', icon: 'pi pi-fw pi-cog', to: '/structures' }, ...adminMenu, ...superadminMenu
                ]
            },
        ];
    };

    // Filtered users
    const filteredUsers = computed(() => {
        if (filters.value.length === 0) return users.value;

        return users.value.filter((user) => {
            return filters.value.every((filter) => {
                const field = filter.field;

                if (!user[field]) return false;

                const value = user[field];

                if (filter.operator === 'eq') {
                    return value === filter.value;
                } else if (filter.operator === 'contains') {
                    if (typeof value === 'string') {
                        return value.toLowerCase().includes(filter.value.toLowerCase());
                    }
                    return false;
                }

                return true;
            });
        });
    });

    // Paginated users
    const paginatedUsers = computed(() => {
        const start = (pagination.value.valueOf.page - 1) * pagination.value.valueOf.perPage;
        const end = start + pagination.value.valueOf.perPage;
        return filteredUsers.value.slice(start, end);
    });

    async function init() {
        const { data, error: errorResponse } = await makeApiRequest({
            url: `auth/current-account`,
            method: 'get'
        });

        if (errorResponse.value) {
            return console.error('Failed to fetch current account:', errorResponse.value);
        }

        const { data: profileData } = data.value;
        console.log('Fetched current account:', profileData);

        setUser(profileData);
    }

    // Actions
    async function fetchUsers() {
        loading.value = true;
        error.value = null;

        try {
            const { data, error: errorResponse } = await makeApiRequest({
                url: `/users`,
                method: 'get'
            });

            if (errorResponse.value) {
                error.value = 'Failed to fetch users';
                console.error('Failed to fetch users:', error.value);

                users.value = [];
                return;
            }

            loading.value = false;
            users.value = data.value?.data || [];
            console.log('Fetched users:', users.value);
            pagination.value.total = data?.total || users.value.length;
        } catch (err) {
            error.value = 'Failed to fetch users';
            console.error(err);
        } finally {
            loading.value = false;
        }
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

    return {
        init,
        users,
        loading,
        error,
        pagination,
        filters,
        filteredUsers,
        paginatedUsers,
        fetchUsers,
        setPage,
        setPerPage,
        addFilter,
        removeFilter,
        clearFilters,
        isAdmin,
        isSuperAdmin,
        setUser,
        menu
    };
});
