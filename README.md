# Documentação de Instalação

## Visão Geral

Este repositório contém dois projetos principais:

- **Frontend**: Localizado em [`app`](app/), desenvolvido em Vue 3, Pinia, PrimeVue e TailwindCSS.
- **Backend**: Localizado em [`eor-backend`](eor-backend/), desenvolvido em Laravel.

---

## 1. Pré-requisitos

- **Node.js** (recomendado v18+)
- **npm**
- **PHP** (>= 8.1)
- **Composer**
- **Banco de Dados** (MySQL/MariaDB/PostgreSQL/SQLite)
- **Extensões PHP**: pdo, mbstring, openssl, etc.
- **Redis** (opcional, para cache/queue)

---

## 2. Instalação do Backend (`eor-backend`)

### 2.1. Configuração do Ambiente

1. Acesse a pasta do backend:
    ```sh
    cd eor-backend
    ```

2. Copie o arquivo de exemplo de variáveis de ambiente:
    ```sh
    cp .env.example .env
    ```

3. Edite o arquivo `.env` conforme sua configuração de banco de dados, cache, mail, etc.

### 2.2. Instale as dependências PHP

```sh
composer install
```

### 2.3. Gere a chave da aplicação

```sh
php artisan key:generate
```

### 2.4. Execute as migrações do banco de dados

```sh
php artisan migrate
```

### 2.5. (Opcional) Popule o banco com dados fake

```sh
php artisan db:seed
```

### 2.6. Inicie o servidor de desenvolvimento

```sh
php artisan serve
```

O backend estará disponível em [http://localhost:8000](http://localhost:8000).

---

## 3. Instalação do Frontend (`app`)

### 3.1. Configuração do Ambiente

1. Acesse a pasta do frontend:
    ```sh
    cd ../app
    ```

2. Copie o arquivo de variáveis de ambiente:
    ```sh
    cp .env.example .env
    ```

3. No arquivo `.env`, configure a variável `VITE_API_URL` apontando para a URL da API do backend (ex: `http://localhost:8000/api`).

### 3.2. Instale as dependências JavaScript

```sh
npm install

```

Além das dependências padrão, é necessário instalar o pacote `xlsx`:

```sh
npm install xlsx

```

### 3.3. Inicie o servidor de desenvolvimento

```sh
npm run dev

```

O frontend estará disponível em [http://localhost:5173](http://localhost:5173) (ou porta informada pelo Vite).

---

## 5. Deploy

- **Backend**: Siga as práticas padrão do Laravel para produção (configuração de cache, queue, storage, permissões, etc).
- **Frontend**: Gere os arquivos de produção com:

  ```sh
  npm run build
  ```

  Sirva o conteúdo da pasta `dist/`.