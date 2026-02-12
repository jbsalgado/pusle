# Como Integrar Seu Catálogo com Plataformas de Redes Sociais

## RESUMO EXECUTIVO

⚠️ **REALIDADE**: Buffer, Metricool, Later e Hootsuite **NÃO SE INTEGRAM DIRETAMENTE** com seu catálogo interno.

Existem 3 caminhos possíveis:

1. **Integração via API** (somente Hootsuite e Buffer têm APIs robustas)
2. **Fluxo Semi-Automático** (exportar → importar)
3. **Uso Manual** (copiar e colar produtos)

---

## CENÁRIO 1: INTEGRAÇÃO VIA API (Automática)

### O que é?
Seu catálogo "conversa" diretamente com a plataforma através de código, enviando produtos automaticamente.

### Quem oferece API?

| Plataforma | API Disponível? | Qualidade da API | Complexidade |
|------------|----------------|------------------|--------------|
| **Hootsuite** | ✅ Sim | ⭐⭐⭐⭐⭐ Excelente | Alta |
| **Buffer** | ✅ Sim | ⭐⭐⭐⭐ Boa | Média |
| **Metricool** | ❌ Não (beta limitada) | ⭐⭐ Muito limitada | N/A |
| **Later** | ❌ Não oficial | ⭐ Inexistente | N/A |

### Como Funciona (Buffer - Exemplo)

```
[SEU CATÁLOGO] 
      ↓
   (seleciona produto)
      ↓
[SEU BACKEND]
      ↓
   (envia via API)
      ↓
[BUFFER API]
      ↓
[INSTAGRAM/FACEBOOK]
```

---

## INTEGRAÇÃO COM BUFFER (Via API)

### Passo 1: Obter Access Token

1. Criar conta no Buffer
2. Acessar: https://publish.buffer.com/developers
3. Gerar Access Token
4. Guardar token (nunca expor publicamente)

### Passo 2: Conectar Perfis Sociais

```bash
# Listar seus perfis conectados
GET https://api.bufferapp.com/1/profiles.json?access_token=SEU_TOKEN
```

Resposta:
```json
[
  {
    "id": "5f3a2b1c",
    "service": "instagram",
    "formatted_username": "@sua_loja"
  },
  {
    "id": "6g4b3c2d",
    "service": "facebook",
    "formatted_username": "Sua Loja Facebook"
  }
]
```

### Passo 3: Código de Integração (Node.js)

```javascript
// buffer-integration.js
const axios = require('axios');

class BufferIntegration {
  constructor(accessToken) {
    this.accessToken = accessToken;
    this.baseUrl = 'https://api.bufferapp.com/1';
  }

  // Enviar produto do catálogo para Buffer
  async agendarProduto(produto, profileIds, agendarPara = null) {
    const texto = this.gerarLegenda(produto);
    const imageUrl = produto.imagem_url;

    for (const profileId of profileIds) {
      try {
        const update = await this.criarUpdate(
          profileId,
          texto,
          imageUrl,
          agendarPara
        );
        
        console.log(`Produto ${produto.nome} agendado no perfil ${profileId}`);
        console.log(`Update ID: ${update.id}`);
      } catch (error) {
        console.error(`Erro ao agendar: ${error.message}`);
      }
    }
  }

  // Criar update no Buffer
  async criarUpdate(profileId, texto, imageUrl, agendarPara) {
    const url = `${this.baseUrl}/updates/create.json`;
    
    const data = {
      access_token: this.accessToken,
      profile_ids: [profileId],
      text: texto,
      media: {
        photo: imageUrl,
        thumbnail: imageUrl
      }
    };

    // Se tiver agendamento específico
    if (agendarPara) {
      data.scheduled_at = Math.floor(new Date(agendarPara).getTime() / 1000);
    } else {
      data.now = true; // Publicar agora
    }

    const response = await axios.post(url, data);
    return response.data;
  }

  // Gerar legenda a partir dos dados do produto
  gerarLegenda(produto) {
    return `
🛍️ ${produto.nome}

${produto.descricao}

💰 R$ ${produto.preco.toFixed(2)}

${produto.em_estoque ? '✅ Em estoque!' : '⚠️ Últimas unidades!'}

#${produto.categoria} #vendas #loja
    `.trim();
  }
}

// ===============================
// EXEMPLO DE USO NO SEU BACKEND
// ===============================

// 1. Importar no seu servidor Express/Node.js
const buffer = new BufferIntegration(process.env.BUFFER_ACCESS_TOKEN);

// 2. Endpoint no seu backend
app.post('/api/publicar-produto', async (req, res) => {
  try {
    const { produtoId } = req.body;
    
    // Buscar produto do seu banco de dados
    const produto = await db.produtos.findById(produtoId);
    
    // IDs dos seus perfis do Buffer (Instagram, Facebook)
    const profileIds = [
      '5f3a2b1c', // Instagram
      '6g4b3c2d'  // Facebook
    ];
    
    // Agendar produto
    await buffer.agendarProduto(produto, profileIds);
    
    res.json({ 
      sucesso: true, 
      mensagem: 'Produto agendado com sucesso!' 
    });
    
  } catch (error) {
    res.status(500).json({ 
      sucesso: false, 
      erro: error.message 
    });
  }
});
```

### Passo 4: Interface no seu Catálogo

```javascript
// Frontend do seu catálogo
function publicarProdutoRedes(produtoId) {
  // Chamar seu backend
  fetch('/api/publicar-produto', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ produtoId })
  })
  .then(response => response.json())
  .then(data => {
    if (data.sucesso) {
      alert('✅ Produto publicado nas redes sociais!');
    } else {
      alert('❌ Erro: ' + data.erro);
    }
  });
}

// Botão no HTML de cada produto
<button onclick="publicarProdutoRedes('prod_123')">
  📱 Publicar nas Redes
</button>
```

---

## INTEGRAÇÃO COM HOOTSUITE (Via API)

### Características da API Hootsuite

- ✅ Mais robusta e completa
- ✅ Suporta múltiplas redes
- ✅ Agendamento avançado
- ❌ Mais complexa de implementar
- ❌ Requer aprovação do app

### Documentação Oficial
https://developer.hootsuite.com/

### Exemplo de Código

```javascript
const axios = require('axios');

class HootsuiteIntegration {
  constructor(accessToken) {
    this.accessToken = accessToken;
    this.baseUrl = 'https://platform.hootsuite.com/v1';
  }

  // Criar mensagem
  async criarMensagem(texto, mediaUrl, socialProfileIds) {
    const response = await axios.post(
      `${this.baseUrl}/messages`,
      {
        text: texto,
        socialProfileIds: socialProfileIds,
        media: [{
          url: mediaUrl
        }]
      },
      {
        headers: {
          'Authorization': `Bearer ${this.accessToken}`,
          'Content-Type': 'application/json'
        }
      }
    );

    return response.data;
  }

  // Agendar mensagem
  async agendarMensagem(messageId, scheduledSendTime) {
    const response = await axios.post(
      `${this.baseUrl}/messages/${messageId}/schedule`,
      {
        scheduledSendTime: scheduledSendTime // ISO 8601 format
      },
      {
        headers: {
          'Authorization': `Bearer ${this.accessToken}`
        }
      }
    );

    return response.data;
  }
}

// Uso
const hootsuite = new HootsuiteIntegration(process.env.HOOTSUITE_TOKEN);

// Do seu catálogo
const produto = {
  nome: 'Tênis Nike Air',
  preco: 399.90,
  imagem_url: 'https://seu-catalogo.com/tenis.jpg'
};

// Criar e agendar
const mensagem = await hootsuite.criarMensagem(
  `Novo: ${produto.nome} - R$ ${produto.preco}`,
  produto.imagem_url,
  ['instagram_profile_id', 'facebook_profile_id']
);

await hootsuite.agendarMensagem(
  mensagem.id,
  '2024-03-20T15:00:00Z'
);
```

---

## CENÁRIO 2: FLUXO SEMI-AUTOMÁTICO (CSV/Excel)

### Como Funciona

```
[SEU CATÁLOGO]
      ↓
(exporta CSV/Excel com produtos)
      ↓
[ARQUIVO EXCEL]
      ↓
(importa manualmente na plataforma)
      ↓
[METRICOOL/LATER/BUFFER]
      ↓
(agendar posts em lote)
      ↓
[REDES SOCIAIS]
```

### Passo a Passo

**1. No Seu Catálogo - Exportar Produtos**

```javascript
// Exemplo: Exportar produtos selecionados para CSV
function exportarParaCSV(produtos) {
  let csv = 'Nome,Descrição,Preço,URL_Imagem,Categoria\n';
  
  produtos.forEach(produto => {
    csv += `"${produto.nome}","${produto.descricao}",${produto.preco},"${produto.imagem_url}","${produto.categoria}"\n`;
  });
  
  // Download do arquivo
  const blob = new Blob([csv], { type: 'text/csv' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = 'produtos_para_publicar.csv';
  link.click();
}
```

**2. Preparar Formato para Metricool**

Metricool aceita CSV com estas colunas:
- `message` - texto do post
- `image_url` - URL da imagem
- `publish_date` - data de publicação
- `publish_time` - hora de publicação
- `social_networks` - redes (facebook,instagram)

```javascript
function exportarParaMetricool(produtos) {
  let csv = 'message,image_url,publish_date,publish_time,social_networks\n';
  
  produtos.forEach((produto, index) => {
    const mensagem = `${produto.nome} - R$ ${produto.preco}`;
    const data = new Date();
    data.setDate(data.getDate() + index); // Um por dia
    
    csv += `"${mensagem}","${produto.imagem_url}","${data.toISOString().split('T')[0]}","14:00:00","facebook,instagram"\n`;
  });
  
  return csv;
}
```

**3. Importar no Metricool**

1. Login no Metricool
2. Ir em "Planner"
3. Clicar em "Upload CSV"
4. Selecionar arquivo
5. Mapear colunas
6. Confirmar importação

### Vantagens
- ✅ Funciona com Metricool, Later e outras
- ✅ Pode publicar muitos produtos de uma vez
- ✅ Não precisa de programação complexa

### Desvantagens
- ❌ Processo manual
- ❌ Não é em tempo real
- ❌ Precisa preparar arquivo toda vez

---

## CENÁRIO 3: USO MANUAL (Copiar & Colar)

### Como Funciona

1. Você acessa seu catálogo
2. Copia informações do produto
3. Abre Buffer/Metricool/Later
4. Cola e ajusta
5. Publica

### Quando Usar
- ✅ Poucos produtos por dia (1-5)
- ✅ Quer personalizar cada post
- ✅ Sem recursos para desenvolver integração

### Otimização - "Atalho Rápido"

Adicione um botão no seu catálogo que facilite:

```html
<!-- No seu catálogo -->
<div class="produto">
  <img src="tenis.jpg">
  <h3>Tênis Nike Air</h3>
  <p>R$ 399,90</p>
  
  <!-- Botão de atalho -->
  <button onclick="copiarParaRedes(this)" data-produto-id="123">
    📋 Copiar para Redes Sociais
  </button>
</div>

<script>
function copiarParaRedes(btn) {
  const produtoId = btn.dataset.produtoId;
  const produto = obterProduto(produtoId);
  
  // Formatar texto pronto
  const texto = `
🛍️ ${produto.nome}

${produto.descricao}

💰 R$ ${produto.preco}

✅ Em estoque!

#${produto.categoria} #vendas
  `.trim();
  
  // Copiar para clipboard
  navigator.clipboard.writeText(texto);
  
  // Abrir Buffer em nova aba
  window.open('https://publish.buffer.com/', '_blank');
  
  alert('✅ Texto copiado! Cole no Buffer e adicione a imagem.');
}
</script>
```

---

## COMPARAÇÃO: QUAL MÉTODO USAR?

| Método | Custo Desenvolvimento | Velocidade | Automação | Recomendado Para |
|--------|----------------------|------------|-----------|------------------|
| **API (Buffer/Hootsuite)** | Alto (R$ 5.000-15.000) | ⚡ Rápido | 100% | Grandes volumes, muitos produtos/dia |
| **CSV Semi-Auto** | Baixo (R$ 500-2.000) | ⚡⚡ Médio | 50% | Volumes médios, publicações semanais |
| **Manual com Atalhos** | Muito Baixo (R$ 0-500) | ⚡⚡⚡ Lento | 0% | Poucos produtos, curadoria manual |
| **Desenvolver do Zero** | Muito Alto (R$ 15.000-50.000) | ⚡ Muito Rápido | 100% | Controle total, funcionalidades únicas |

---

## MINHA RECOMENDAÇÃO PARA VOCÊ

### Cenário A: Volume BAIXO (1-10 produtos/dia)

**Solução**: Manual com Atalhos + Metricool

1. Adicione botão "Copiar para Redes" no catálogo
2. Use Metricool Starter (R$ 90/mês)
3. Tempo por produto: ~2 minutos
4. **Custo total**: R$ 90/mês + 0 desenvolvimento

### Cenário B: Volume MÉDIO (10-50 produtos/dia)

**Solução**: CSV Semi-Automático + Buffer/Metricool

1. Crie botão "Exportar selecionados para CSV"
2. Uma vez por dia/semana, exporte e importe
3. Use Metricool Starter (R$ 90/mês)
4. **Custo total**: R$ 90/mês + R$ 2.000 desenvolvimento único

### Cenário C: Volume ALTO (50+ produtos/dia)

**Solução**: API Buffer ou Desenvolver do Zero

1. Integração via API do Buffer
2. Botão em cada produto "Publicar agora"
3. Publicação automática
4. **Custo total**: R$ 250/mês Buffer + R$ 8.000-15.000 desenvolvimento

---

## CÓDIGO COMPLETO: INTEGRAÇÃO BUFFER + SEU CATÁLOGO

### Estrutura do Projeto

```
seu-catalogo/
├── backend/
│   ├── server.js                 # Servidor principal
│   ├── integracoes/
│   │   └── buffer.js            # Classe de integração Buffer
│   └── routes/
│       └── publicar.js          # Rotas de publicação
├── frontend/
│   ├── catalogo.html            # Página do catálogo
│   └── js/
│       └── publicador.js        # JavaScript do frontend
└── .env                         # Tokens e configurações
```

### Backend Completo

```javascript
// backend/integracoes/buffer.js
class BufferAPI {
  constructor(accessToken) {
    this.token = accessToken;
    this.baseUrl = 'https://api.bufferapp.com/1';
  }

  async obterPerfis() {
    const response = await fetch(
      `${this.baseUrl}/profiles.json?access_token=${this.token}`
    );
    return response.json();
  }

  async publicarAgora(profileId, texto, imagemUrl) {
    const response = await fetch(
      `${this.baseUrl}/updates/create.json`,
      {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          access_token: this.token,
          profile_ids: [profileId],
          text: texto,
          media: { photo: imagemUrl },
          now: true
        })
      }
    );
    return response.json();
  }

  async agendar(profileId, texto, imagemUrl, dataHora) {
    const timestamp = Math.floor(new Date(dataHora).getTime() / 1000);
    
    const response = await fetch(
      `${this.baseUrl}/updates/create.json`,
      {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          access_token: this.token,
          profile_ids: [profileId],
          text: texto,
          media: { photo: imagemUrl },
          scheduled_at: timestamp
        })
      }
    );
    return response.json();
  }
}

module.exports = BufferAPI;
```

```javascript
// backend/routes/publicar.js
const express = require('express');
const router = express.Router();
const BufferAPI = require('../integracoes/buffer');

const buffer = new BufferAPI(process.env.BUFFER_ACCESS_TOKEN);

// Publicar produto agora
router.post('/publicar-agora', async (req, res) => {
  try {
    const { produtoId, redes } = req.body;
    
    // Buscar produto do banco
    const produto = await db.produtos.findById(produtoId);
    
    const texto = `
🛍️ ${produto.nome}

${produto.descricao}

💰 R$ ${produto.preco.toFixed(2)}

${produto.hashtags || '#produtos #vendas'}
    `.trim();

    const resultados = [];

    // Publicar em cada rede selecionada
    for (const rede of redes) {
      const profileId = process.env[`BUFFER_${rede.toUpperCase()}_ID`];
      const resultado = await buffer.publicarAgora(
        profileId,
        texto,
        produto.imagem_url
      );
      resultados.push({ rede, ...resultado });
    }

    res.json({ 
      sucesso: true, 
      resultados 
    });

  } catch (error) {
    res.status(500).json({ 
      sucesso: false, 
      erro: error.message 
    });
  }
});

// Agendar produto
router.post('/agendar', async (req, res) => {
  try {
    const { produtoId, redes, dataHora } = req.body;
    
    const produto = await db.produtos.findById(produtoId);
    
    const texto = `
🛍️ ${produto.nome}
💰 R$ ${produto.preco.toFixed(2)}
${produto.hashtags}
    `.trim();

    const resultados = [];

    for (const rede of redes) {
      const profileId = process.env[`BUFFER_${rede.toUpperCase()}_ID`];
      const resultado = await buffer.agendar(
        profileId,
        texto,
        produto.imagem_url,
        dataHora
      );
      resultados.push({ rede, ...resultado });
    }

    res.json({ sucesso: true, resultados });

  } catch (error) {
    res.status(500).json({ sucesso: false, erro: error.message });
  }
});

module.exports = router;
```

### Frontend Completo

```html
<!-- frontend/catalogo.html -->
<!DOCTYPE html>
<html>
<head>
  <title>Catálogo - Publicador Social</title>
  <style>
    .produto {
      border: 1px solid #ddd;
      padding: 20px;
      margin: 10px;
      border-radius: 8px;
    }
    .produto img {
      max-width: 200px;
    }
    .acoes-sociais {
      margin-top: 15px;
    }
    .btn-publicar {
      background: #1DA1F2;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    .redes-checkbox {
      margin: 10px 0;
    }
  </style>
</head>
<body>
  <h1>Catálogo de Produtos</h1>

  <div id="produtos-lista">
    <!-- Produtos serão carregados aqui -->
  </div>

  <script src="js/publicador.js"></script>
  <script>
    // Carregar produtos do seu banco
    async function carregarProdutos() {
      const response = await fetch('/api/produtos');
      const produtos = await response.json();
      
      const lista = document.getElementById('produtos-lista');
      
      produtos.forEach(produto => {
        lista.innerHTML += `
          <div class="produto" data-id="${produto.id}">
            <img src="${produto.imagem_url}" alt="${produto.nome}">
            <h3>${produto.nome}</h3>
            <p>${produto.descricao}</p>
            <p class="preco">R$ ${produto.preco.toFixed(2)}</p>
            
            <div class="acoes-sociais">
              <div class="redes-checkbox">
                <label>
                  <input type="checkbox" value="instagram" checked>
                  Instagram
                </label>
                <label>
                  <input type="checkbox" value="facebook" checked>
                  Facebook
                </label>
              </div>
              
              <button class="btn-publicar" onclick="publicarProduto('${produto.id}')">
                📱 Publicar Agora
              </button>
              
              <button class="btn-publicar" onclick="agendarProduto('${produto.id}')">
                ⏰ Agendar
              </button>
            </div>
          </div>
        `;
      });
    }

    carregarProdutos();
  </script>
</body>
</html>
```

```javascript
// frontend/js/publicador.js

async function publicarProduto(produtoId) {
  const redes = obterRedesSelecionadas(produtoId);
  
  if (redes.length === 0) {
    alert('Selecione pelo menos uma rede social!');
    return;
  }
  
  if (!confirm(`Publicar agora em: ${redes.join(', ')}?`)) {
    return;
  }

  try {
    const response = await fetch('/api/publicar-agora', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ 
        produtoId, 
        redes 
      })
    });

    const resultado = await response.json();

    if (resultado.sucesso) {
      alert('✅ Produto publicado com sucesso!');
    } else {
      alert('❌ Erro: ' + resultado.erro);
    }

  } catch (error) {
    alert('❌ Erro de conexão: ' + error.message);
  }
}

async function agendarProduto(produtoId) {
  const redes = obterRedesSelecionadas(produtoId);
  
  if (redes.length === 0) {
    alert('Selecione pelo menos uma rede social!');
    return;
  }

  const dataHora = prompt('Data e hora (YYYY-MM-DD HH:mm):');
  
  if (!dataHora) return;

  try {
    const response = await fetch('/api/agendar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ 
        produtoId, 
        redes, 
        dataHora 
      })
    });

    const resultado = await response.json();

    if (resultado.sucesso) {
      alert(`✅ Produto agendado para ${dataHora}!`);
    } else {
      alert('❌ Erro: ' + resultado.erro);
    }

  } catch (error) {
    alert('❌ Erro: ' + error.message);
  }
}

function obterRedesSelecionadas(produtoId) {
  const produto = document.querySelector(`[data-id="${produtoId}"]`);
  const checkboxes = produto.querySelectorAll('.redes-checkbox input:checked');
  return Array.from(checkboxes).map(cb => cb.value);
}
```

### Arquivo .env

```bash
# Buffer API
BUFFER_ACCESS_TOKEN=seu_token_aqui

# IDs dos perfis (obter via API)
BUFFER_INSTAGRAM_ID=5f3a2b1c4d5e6f7g
BUFFER_FACEBOOK_ID=6g7h8i9j0k1l2m3n

# Banco de dados
DATABASE_URL=postgresql://user:pass@localhost/catalogo
```

---

## CUSTOS FINAIS DE CADA OPÇÃO

### Opção 1: Buffer API + Desenvolvimento

- **Desenvolvimento**: R$ 8.000-12.000 (uma vez)
- **Buffer**: R$ 50-250/mês
- **Manutenção**: R$ 500/mês
- **Total ano 1**: ~R$ 14.000
- **Anos seguintes**: ~R$ 3.600/ano

### Opção 2: CSV Semi-Automático

- **Desenvolvimento**: R$ 1.500-3.000 (uma vez)
- **Metricool**: R$ 90/mês
- **Total ano 1**: ~R$ 4.000
- **Anos seguintes**: ~R$ 1.080/ano

### Opção 3: Manual Otimizado

- **Desenvolvimento**: R$ 0-500 (atalhos simples)
- **Metricool**: R$ 90/mês
- **Total ano 1**: ~R$ 1.580
- **Anos seguintes**: ~R$ 1.080/ano

---

## PRÓXIMOS PASSOS

1. **Defina seu volume**: Quantos produtos/dia você publica?
2. **Escolha a abordagem**: API, CSV ou Manual?
3. **Se escolher API**: Contratar desenvolvedor
4. **Se escolher CSV**: Implementar exportação
5. **Se escolher Manual**: Criar atalhos de cópia

---

**Dúvidas?** Me pergunte sobre qualquer parte específica da integração!
