# Magellan Utilities Construction — site

Repositório do site de **Magellan Utilities Construction** — [`magellanuc.com`](https://www.magellanuc.com/).
Empreiteira de infraestrutura subterrânea na Flórida (*A Magellan Group Company*):
perfuração direcional (HDD), construção de utilidades subterrâneas e instalação de fibra,
para primes, concessionárias e construtoras de fibra.

> **Posicionamento atual:** construção (*Construction*), não consultoria. Os documentos
> antigos que tratavam a empresa como *Magellan Utilities Consulting* (consultoria e
> compliance) e propunham herdar a landing page da AMT estão em
> [`docs/arquivo/`](docs/arquivo/) **apenas como histórico** e não devem ser seguidos.

## O que tem aqui

```
site/
├── index.html          # página única (hero, números, capacidades, verticais, por quê, projetos, pedido de bid)
├── thank-you/          # confirmação do formulário
├── bid-request.php     # recebe o formulário "Let's talk about your project"
├── sitemap.xml         # hoje lista só a home
├── robots.txt
├── favicon.ico
└── assets/             # site.css, site.js, logos, ícones das verticais, fotos de projeto, og.jpg
docs/arquivo/           # documentos de 2026-08-05 (posicionamento antigo) — só histórico
```

Sem framework e sem build: um HTML, um CSS (`assets/site.css`, paleta azul `#0A1F8F` + verde
`#00A651`, fonte Montserrat) e pouco JavaScript (`assets/site.js`).

## Conteúdo da home

- **Capacidades:** perfuração direcional horizontal · construção de utilidades subterrâneas · instalação de fibra · coordenação de campo
- **Verticais:** água · energia · óleo e gás · fibra e cabo
- **Projetos:** travessias de estrada, ferrovia e hidrovia · trincheira e vala aberta · relocação de utilidades e novo serviço
- **Faixa de números:** 1M+ pés instalados · 4 verticais · 72h de mobilização típica · FL ativo, 5 estados no alvo
  (os marcadores "a verificar" foram retirados em 01/10/2026; os valores seguem como publicados)

## Formulário de pedido (bid request)

`site/bid-request.php` recebe o formulário (campos `b-*`):

- envia por `mail()` da HostGator para `info@magellanuc.com`, remetente `no-reply@magellanuc.com`;
- tem honeypot, limite de 10 MB somando os anexos e lista de extensões permitidas;
- grava **toda** submissão em `/home2/lhsjeste/form-logs` (fora do `public_html`), mesmo se o e-mail falhar;
- ⚠ o *Email Routing* do domínio precisa estar em **Remote** no cPanel (o MX é do Zoho). Em "Local" o
  `mail()` entrega numa caixa que ninguém abre e os pedidos somem sem erro.

## Como publicar

Não há rotina automática. A hospedagem é HostGator (cPanel), pasta `magellanuc.com` da conta:

1. Alterar os arquivos em `site/` e dar commit/push neste repositório.
2. No cPanel › File Manager › `magellanuc.com`, enviar os arquivos alterados (ou um `.zip`
   e usar *Extract*) sobrescrevendo os existentes.
3. Conferir `https://www.magellanuc.com/` e enviar um pedido de teste.

`site/` espelha a raiz publicada: o que está aqui é o que está no ar, e vice-versa.

## Pendências conhecidas

- `sitemap.xml` lista só a home; atualizar quando a página crescer e reenviar no Search Console.
- Páginas legais (privacidade, termos) não existem — site público que coleta dados de contato.
- Dados de capacidade (metragem, mix, prazo de resposta) e especificações de projetos ainda
  dependem de confirmação da operação.
- Colisão de nome: *Magellan Advisors / magellanbroadband.com* (outro grupo) domina buscas por
  "Magellan" + utilities; usar termos específicos e locais (HDD, Florida, Apopka).
