# Magellan Utility

Repositório do site da **Magellan Utilities Consulting LLC** — `magellanuc.com`.

**Status:** o código do site publicado está em [`site/`](site/) (importado da
HostGator em 01/10/2026). A pasta `docs/` guarda a base estratégica e técnica
herdada da landing page da AMT Business. O README será reescrito na pendência MUC17.

## O que tem aqui

| Documento | Conteúdo |
|---|---|
| [`docs/estrutura-herdada.md`](docs/estrutura-herdada.md) | O sistema de design e o inventário de componentes que vêm da LP da AMT — tokens, componentes, práticas, e o que precisa ser recalibrado |
| [`docs/pendencias.md`](docs/pendencias.md) | O que precisa ser decidido antes de qualquer linha de código |

## De onde isso vem

A decisão de 2026-08-05 é reaproveitar **toda a estrutura desenvolvida em
`AMT-Preview/`** (no repositório [`Chiquetti/CW`](https://github.com/Chiquetti/CW))
para o site da Magellan Utility.

Não é copiar a página — é herdar o sistema. A estrutura foi documentada já
separada entre o que é **portável** (grid, tipografia, componentes, práticas) e
o que é **conteúdo da AMT** (paleta, copy, densidade).

Fontes originais, no repositório da CW:

- `docs/amt/lp-estrutura.md` — anatomia completa da LP da AMT
- `docs/magellan-uc/reaproveitamento.md` — a decisão e suas ressalvas
- `docs/magellan-group/arquitetura-de-marca.md` — onde a MUC se encaixa no grupo
- `docs/magellan-group/direcao-de-design.md` — a regra de densidade por público

## O aviso mais importante

Herdar os componentes **não** significa herdar a página. A LP da AMT foi
calibrada para um contractor no celular, em campo, parado sem o equipamento de
que precisa — telefone acima da dobra, barra fixa de ligação, urgência.

O comprador de consultoria e compliance não lê assim. Ele avalia com calma, no
desktop, e decide por credibilidade e método, não por disponibilidade. Copiar a
densidade da AMT entrega uma página que grita para quem está pesquisando com
cuidado. Detalhes em [`docs/estrutura-herdada.md`](docs/estrutura-herdada.md) §4.
