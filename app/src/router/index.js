import AppLayout from '@/layout/AppLayout.vue';
import { createRouter, createWebHistory } from 'vue-router';

import UsersList from '@/views/pages/users/UsersList.vue';
import UsersEdit from '@/views/pages/users/UsersEdit.vue';

import AccessGroupsEdit from '@/views/pages/access-groups/AccessGroupsEdit.vue';
import AccessGroupsList from '@/views/pages/access-groups/AccessGroupsList.vue';

import StructuresEdit from '@/views/pages/structures/StructuresEdit.vue';
import StructuresList from '@/views/pages/structures/StructuresList.vue';

import CompaniesEdit from '@/views/pages/companies/CompaniesEdit.vue';
import CompaniesList from '@/views/pages/companies/CompaniesList.vue';

import FederalEdit from '@/views/pages/federal/FederalEdit.vue';
import FederalList from '@/views/pages/federal/FederalList.vue';

import EstadualEdit from '@/views/pages/estadual/EstadualEdit.vue';
import EstadualList from '@/views/pages/estadual/EstadualList.vue';

const guards = (to, from, next) => {
    const hasToken = localStorage.getItem('eor__token');

    if (!hasToken) {
        return next({ name: 'login' });
    }

    next();
};

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            component: AppLayout,
            beforeEnter: guards,
            children: [
                {
                    path: '/',
                    name: '',
                    // dashboard
                    component: () => import('@/views/pages/Dashboard.vue')
                },
                {
                    path: '/users',
                    name: 'Users',
                    component: UsersList
                },
                {
                    path: '/users/new',
                    name: 'NewUser',
                    component: UsersEdit
                },
                {
                    path: '/users/:id',
                    name: 'EditUser',
                    component: UsersEdit
                },
                {
                    path: '/access-groups',
                    name: 'AccessGroups',
                    component: AccessGroupsList
                },
                {
                    path: '/access-groups/new',
                    name: 'NewAccessGroup',
                    component: AccessGroupsEdit
                },
                {
                    path: '/access-groups/:id',
                    name: 'EditAccessGroup',
                    component: AccessGroupsEdit
                },
                {
                    path: '/structures',
                    name: 'Structures',
                    component: StructuresList
                },
                {
                    path: '/structures/new',
                    name: 'NewStructure',
                    component: StructuresEdit
                },
                {
                    path: '/structures/:id',
                    name: 'EditStructure',
                    component: StructuresEdit
                },
                {
                    path: '/companies',
                    name: 'Companies',
                    component: CompaniesList
                },
                {
                    path: '/companies/new',
                    name: 'NewCompany',
                    component: CompaniesEdit
                },
                {
                    path: '/companies/:id',
                    name: 'EditCompany',
                    component: CompaniesEdit
                },

                {
                    path: '/federal',
                    name: 'Federal',
                    component: FederalList
                },
                {
                    path: '/federal/new',
                    name: 'Newfederal',
                    component: FederalEdit
                },
                {
                    path: '/federal/:id',
                    name: 'EditFederal',
                    component: FederalEdit
                },

                {
                    path: '/estadual',
                    name: 'Estadual',
                    component: EstadualList
                },
                {
                    path: '/estadual/new',
                    name: 'NewEstadual',
                    component: EstadualEdit
                },
                {
                    path: '/estadual/:id',
                    name: 'EditEstadual',
                    component: EstadualEdit
                }
            ]
        },
        {
            path: '/auth/login',
            name: 'login',
            component: () => import('@/views/pages/auth/Login.vue')
        }
    ]
});

export default router;
