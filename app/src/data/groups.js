// src/data/groupsData.js

const groupsData = {
    groups: {
        list: {
            message: "Grupos de acesso listados com sucesso!",
            data: {
                current_page: 1,
                data: [
                    {
                        id: 1,
                        nome: "Grupo A",
                        descricao: "Descrição do Grupo A",
                        created_at: "2025-05-12T12:00:00.000000Z",
                        updated_at: "2025-05-12T12:00:00.000000Z",
                        estruturas: [
                            {
                                id: 1,
                                grupo_id: 1,
                                estrutura_id: 1,
                                nivel_acesso: "leitura",
                                created_at: "2025-05-12T12:00:00.000000Z",
                                updated_at: "2025-05-12T12:00:00.000000Z"
                            }
                        ]
                    }
                ],
                total: 10,
                per_page: 10,
                last_page: 1
            }
        },
        list_error: {
            message: "Erro ao listar grupos de acesso.",
            error: "Detalhes do erro."
        },
        show: {
            id: 1,
            nome: "Grupo A",
            descricao: "Descrição do Grupo A",
            created_at: "2025-05-12T12:00:00.000000Z",
            updated_at: "2025-05-12T12:00:00.000000Z",
            estruturas: [
                {
                    id: 1,
                    grupo_id: 1,
                    estrutura_id: 1,
                    nivel_acesso: "leitura",
                    created_at: "2025-05-12T12:00:00.000000Z",
                    updated_at: "2025-05-12T12:00:00.000000Z"
                }
            ]
        },
        create: {
            message: "Grupo de acesso criado com sucesso!",
            data: {
                id: 1,
                nome: "Grupo A",
                descricao: "Descrição do Grupo A",
                created_at: "2025-05-12T12:00:00.000000Z",
                updated_at: "2025-05-12T12:00:00.000000Z",
                estruturas: [
                    {
                        id: 1,
                        grupo_id: 1,
                        estrutura_id: 1,
                        nivel_acesso: "leitura",
                        created_at: "2025-05-12T12:00:00.000000Z",
                        updated_at: "2025-05-12T12:00:00.000000Z"
                    },
                    {
                        id: 2,
                        grupo_id: 1,
                        estrutura_id: 2,
                        nivel_acesso: "leitura_escrita",
                        created_at: "2025-05-12T12:00:00.000000Z",
                        updated_at: "2025-05-12T12:00:00.000000Z"
                    }
                ]
            }
        },
        update: {
            message: "Grupo de acesso atualizado com sucesso!",
            data: {
                id: 1,
                nome: "Grupo Atualizado",
                descricao: "Descrição atualizada do Grupo",
                created_at: "2025-05-12T12:00:00.000000Z",
                updated_at: "2025-05-12T13:00:00.000000Z",
                estruturas: [
                    {
                        id: 1,
                        grupo_id: 1,
                        estrutura_id: 1,
                        nivel_acesso: "leitura",
                        created_at: "2025-05-12T13:00:00.000000Z",
                        updated_at: "2025-05-12T13:00:00.000000Z"
                    },
                    {
                        id: 2,
                        grupo_id: 1,
                        estrutura_id: 3,
                        nivel_acesso: "leitura_escrita",
                        created_at: "2025-05-12T13:00:00.000000Z",
                        updated_at: "2025-05-12T13:00:00.000000Z"
                    }
                ]
            }
        },
        delete: {
            message: "Grupo de acesso excluído com sucesso!"
        },
        addUser: {
            message: "Usuário adicionado ao grupo com sucesso!"
        },
        removeUser: {
            message: "Usuário removido do grupo com sucesso!"
        },
        listUsers: {
            message: "Usuários do grupo listados com sucesso!",
            data: [
                {
                    id: 1,
                    name: "Usuário Externo",
                    email: "usuario@externo.com",
                    created_at: "2025-05-12T12:00:00.000000Z",
                    updated_at: "2025-05-12T12:00:00.000000Z"
                },
                {
                    id: 2,
                    name: "Outro Usuário",
                    email: "outro@externo.com",
                    created_at: "2025-05-12T12:00:00.000000Z",
                    updated_at: "2025-05-12T12:00:00.000000Z"
                }
            ]
        }
    }
};

const { groups } = groupsData;
const {
    list,
    show,
    create,
    update,
    delete: deleteResponse,
    addUser,
    removeUser,
    listUsers
} = groups;

export {
    addUser, create, deleteResponse, list, listUsers, removeUser, show, update
};

