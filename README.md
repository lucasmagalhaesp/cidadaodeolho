# API Cidadão de Olho

## O que é o Cidadão de Olho?
É uma API de acesso ao dados disponibilizados no web service da Assembléia Legislativa de Minas Gerais (ALMG). Ela fornece 3 serviços principais:
-  Mostra os top 5 deputados que mais pediram reembolso de verbas indenizatórias por mês, considerando somente o ano de 2019;
-  Mostra o ranking das redes sociais mais utilizadas dentre os deputados;
-  Grava em um banco de dados (MongoDB) o retorno de qualquer consulta ao web service da ALMG

## Tecnologias Utilizadas na API
Esse projeto é uma API desenvolvida com o Laravel Framework (versão 11) e usa um banco de dados MongoDB para a gravação das consultas;

## Instale suas dependências
```bash
composer install
```

## Configurações
```bash
Depois de ter instalado e configurado o seu banco de dados MongoDB, informe os dados de conexão no .env:
MONGO_DB_HOST=localhost:27017
MONGO_DB_PORT=27017
MONGO_DB_DATABASE=cidadaodeolho
MONGO_DB_USERNAME=
MONGO_DB_PASSWORD=
```

## Inicie a aplicação em ambiente de desenvolvimento
```bash
php artisan serve
```

## Usar a API
Utilize um software cliente HTTP e acesse os serviços da API conforme descrição abaixo:

### Mostra os top 5 deputados que mais pediram reembolso de verbas indenizatórias por mês, considerando somente o ano de 2019
```bash
método: GET
http:://localhost:8000/api/verbas/reembolso/2019
```

### Mostrar o ranking das redes sociais mais utilizadas dentre os deputados
```bash
método: GET
http:://localhost:8000/api/redessociais/ranking
```

### Grava em um banco de dados (MongoDB) o retorno de qualquer consulta ao web service da ALMG
```bash
método: POST
parâmetro: url -> a URL do web service da ALMG a ser consultada e terá o seu retorno gravado em banco de dados
http:://localhost:8000/api/dadospublicos/almg
```
