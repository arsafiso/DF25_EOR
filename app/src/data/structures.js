// src/data/structuresData.js

const structuresData = {
    structures: {
        list: {
            message: "Estruturas listadas com sucesso!",
            data: [
                {
                    id: 1,
                    finalidade: "Irrigação",
                    projetistas: "Engenheiro A",
                    status: "Ativo",
                    classificacao_federal: "Classe A",
                    created_at: "2025-05-12T12:00:00.000000Z",
                    updated_at: "2025-05-12T12:00:00.000000Z"
                },
                {
                    id: 2,
                    finalidade: "Abastecimento",
                    projetistas: "Engenheiro B",
                    status: "Inativo",
                    classificacao_federal: "Classe B",
                    created_at: "2025-05-12T12:00:00.000000Z",
                    updated_at: "2025-05-12T12:00:00.000000Z"
                }
            ]
        },
        list_error: {
            message: "Erro ao listar estruturas.",
            error: "Detalhes do erro."
        },
        show: {
            id: 1,
            finalidade: "Irrigação",
            projetistas: "Engenheiro A",
            status: "Ativo",
            classificacao_federal: "Classe A",
            created_by: 1,
            updated_by: 1,
            created_at: "2025-05-12T12:00:00.000000Z",
            updated_at: "2025-05-12T12:00:00.000000Z"
        },
        create: {
            message: "Estrutura criada com sucesso!",
            data: {
                id: 1,
                finalidade: "Irrigação",
                projetistas: "Engenheiro A",
                status: "Ativo",
                classificacao_federal: "Classe A",
                created_by: 1,
                updated_by: 1,
                created_at: "2025-05-12T12:00:00.000000Z",
                updated_at: "2025-05-12T12:00:00.000000Z"
            }
        },
        update: {
            message: "Estrutura atualizada com sucesso!",
            data: {
                id: 1,
                finalidade: "Abastecimento",
                projetistas: "Engenheiro B",
                status: "Inativo",
                classificacao_federal: "Classe B",
                updated_by: 1,
                created_at: "2025-05-12T12:00:00.000000Z",
                updated_at: "2025-05-12T13:00:00.000000Z"
            }
        },
        delete: {
            message: "Estrutura excluída com sucesso!"
        }
    }
};

const { structures } = structuresData;
const { list, show, create, update, delete: deleteResponse } = structures;

export {
    create, deleteResponse, list,
    show, update
};

