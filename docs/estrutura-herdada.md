# Estrutura herdada da LP da AMT

Registro do que a Magellan Utility aproveita da landing page construída em
`AMT-Preview/` (repositório [`Chiquetti/CW`](https://github.com/Chiquetti/CW)),
separado em três camadas: **o que atravessa igual**, **o que precisa de troca
mecânica** e **o que precisa de recalibragem de projeto**.

A terceira é a que importa — é onde um reaproveitamento mal feito estraga o
resultado.

---

## 1. Camada técnica — atravessa igual

Nenhuma decisão nova é necessária aqui.

**Arquitetura:** uma página HTML, um arquivo CSS, sem framework, sem build.
JavaScript só onde é inevitável (na AMT são 14 linhas, apenas o toggle do menu).
Fontes do Google Fonts, todo o resto local.

**Grid e escala:**

```css
--shell:1200px
--gutter:clamp(20px,5vw,48px)
--section-y:clamp(58px,8vw,104px)
```

**Tipografia:** Montserrat (display, 700/800) para títulos, Inter (400–700) para
corpo. É a mesma do Magellan Group e da AMT — é o que dá parentesco visual entre
os sites do grupo sem precisar de mais nada. **Não trocar.**

**Escalas de tipo:**

```css
h1 clamp(26px,3.3vw,38px)   h2 clamp(27px,3.7vw,42px)
h3 clamp(20px,2.3vw,28px)   h4 19px
corpo 17px / 1.62           .lede clamp(17px,1.9vw,20px)
.eyebrow 12px / .16em / uppercase
```

---

## 2. Componentes disponíveis

Todos genéricos — trocam-se conteúdo e cores. Copiar do
`AMT-Preview/assets/amt-lp.css`.

| Componente | Classe | O que faz |
|---|---|---|
| Header sticky | `.site-header` `.header-inner` | Colapsa em menu hambúrguer < 1080px |
| Lockup com descritor | `.brand` `.brand-desc` | Marca + régua vertical + descritor; descritor some < 1240px |
| Hero escuro | `.hero` `.hero-bg--ph` | Placeholder listrado em CSS enquanto não há foto |
| Régua de marca | `.rule3` | Três faixas antes de cada eyebrow — device de unidade visual |
| Barra de sinalização | `.signal` `.signal-item` | Quatro fatos curtos com marcador colorido |
| Card de categoria | `.fleet-card` `.fleet-photo` `.fleet-list` | Foto + título + lista + CTA |
| Checklist | `.checklist` | Anel colorido, título em negrito, explicação |
| Passos numerados | `.steps` `.step` | Grid com divisória de 2px |
| Cards de destaque | `.reqs` `.req-card` | Borda colorida à esquerda |
| Chips | `.chips` `.chip` | Lista de públicos, setores ou credenciais |
| Formulário | `.form` `.field` `.field-row` | Campos, dica sob o label, nota destacada |
| Bloco de contato | `.contact-block` `.contact-item` | Telefone, e-mail, endereço, horário |
| Faixa de CTA | `.cta-band` | Título + botões, fecha a página |
| Rodapé | `.site-footer` `.footer-links` | Links em linha, sem colunas de lista |
| Barra fixa de ligação | `.callbar` | Só < 760px — **avaliar se cabe na MUC**, ver §4 |
| Marcação de pendência | `.todo` | Chip âmbar `CONFIRMAR` com detalhe no `title` |
| Flag de preview | `.preview-flag` | Faixa no topo, removida ao publicar |

**Acessibilidade que vem junto:** skip link, `aria-label` no header,
`aria-expanded` no toggle, `aria-hidden` nos ornamentos, foco visível de 2px,
`prefers-reduced-motion` desligando transições, alvos de toque ≥ 44px.

---

## 3. Camada de marca — troca mecânica

A paleta da AMT foi amostrada do logo dela (`#0066BD` e `#3EAF42`) e expandida
em escala de 11 tons + 2 de acento. A MUC precisa da mesma estrutura com as
cores dela:

```css
/* substituir por uma escala da cor da MUC */
--blue-950 … --blue-50     11 tons, do quase-preto ao quase-branco
--green-600  --green-700   acento, 2 tons
--slate:#5E6B75  --white:#FFFFFF
```

**A regra de cor que vale a pena herdar** (não a cor, a regra):

> Uma cor carrega a estrutura. A outra significa **uma coisa só**.

Na AMT: azul é estrutura (fundo, texto, botão, nav) e verde significa
disponibilidade — marcador de frota, borda de requisito, WhatsApp. Verde nunca
em texto corrido, nunca em botão primário.

Na MUC o acento precisa de um significado próprio e único. Se ele não tiver
significado, vira decoração e a página perde hierarquia.

**Lockup:** o header já suporta marca + régua + descritor. Se a marca da MUC
comunicar a categoria sozinha, o descritor pode sair — foi obrigatório na AMT
porque "AMT Business" não diz o que a empresa faz.

---

## 4. Camada de projeto — o que NÃO copiar

Esta seção é o motivo deste documento existir.

### Densidade

A regra vem do §7 de `direcao-de-design.md` (repositório da CW): **mesmo design
system, densidade diferente por público.**

| | LP da AMT | Site da MUC |
|---|---|---|
| Leitor | Contractor, no celular, em campo, com pressa | Comprador avaliando fornecedor, no desktop, sem pressa |
| Decide por | Disponibilidade — "tem ou não tem?" | Credibilidade, credencial, método |
| Conversão | Telefone acima da dobra, barra fixa de ligação | Contato qualificado, agenda, proposta |
| Densidade | Alta, escaneável | Média, com espaço para argumento |

### Traduções sugeridas de componente

| Na AMT | Na MUC |
|---|---|
| Barra fixa de ligação | Provavelmente **sai**. Consultoria não se contrata por ligação de urgência |
| Hero com telefone e `Request a Rental` | Hero com **prova** — credencial, anos, escopo — e CTA de conversa |
| Cards de categoria de frota | Áreas de atuação ou linhas de serviço |
| Barra de sinalização (4 fatos) | Fica — mas com credenciais no lugar de logística |
| Seção de requisitos (filtro de lead) | **Metodologia** ou **credenciais**. Mesmo componente, papel oposto: na AMT filtra, na MUC convence |
| Chips de setores atendidos | Fica igual — setores e tipos de cliente |
| Formulário curto de pedido | Formulário mais longo, com campo de contexto do projeto |

### O que herdar sem pensar duas vezes

As **práticas**, não os pixels:

- `noindex` enquanto for preview
- Marcar com `CONFIRMAR` **todo** dado não verificado, e não publicar com nenhum
  em aberto
- Placeholder de foto em CSS em vez de banco de imagem
- Telefone placeholder obviamente falso, para não haver risco de ir ao ar um
  número que pareça real
- README do diretório listando o que falta antes de publicar

---

## 5. Como copiar, na prática

1. Copiar `AMT-Preview/assets/amt-lp.css` do repositório da CW
2. Trocar o bloco `:root` inteiro pela paleta da MUC
3. Apagar os componentes que não forem usados — CSS morto em arquivo único é
   dívida imediata
4. Reescrever a copy do zero. **Nenhuma frase da AMT serve**: ela vende
   disponibilidade de ativo, a MUC vende julgamento
5. Recalibrar a densidade conforme §4 antes de montar as seções

---

*Documento criado em 2026-08-05, derivado de `docs/amt/lp-estrutura.md` no
repositório `Chiquetti/CW`.*
