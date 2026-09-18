// Teste automatizado de geração de comprovante para impressão
const fs = require('fs');
const path = require('path');

// Mock browser globals
global.window = {};
global.document = {
    getElementById: () => null
};

// Helper functions needed by pix.js
global.removerAcentos = function(str) {
    if (!str) return '';
    return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
};
global.formatarCpfCnpj = function(val) { return val || ''; };
global.formatarTelefone = function(val) { return val || ''; };

// Load pix.js content and evaluate
const pixJsPath = path.join(__dirname, '../web/venda-direta/js/pix.js');
let pixJsContent = fs.readFileSync(pixJsPath, 'utf8');

// Strip ES module exports/imports for node vm execution
pixJsContent = pixJsContent
    .replace(/^import\s+.*?;\s*$/gm, '')
    .replace(/^export\s+/gm, '');

// Evaluate pix.js in current context
eval(pixJsContent);

console.log("=================================================");
console.log(" TESTE DE GERAÇÃO DE COMPROVANTE (IMPRIMIR)      ");
console.log("=================================================");

let pass = 0;
let fail = 0;

function assert(condition, name) {
    if (condition) {
        console.log(`[PASS] ${name}`);
        pass++;
    } else {
        console.error(`[FAIL] ${name}`);
        fail++;
    }
}

// CENÁRIO 1: Dados completos com subtotalItensLiquido, descontoGlobal e acrescimo
window.dadosComprovanteAtual = {
    carrinho: [
        { nome: 'Camisa Polo Pulse', quantidade: 2, preco: 50.00, unidade_medida: 'un' },
        { nome: 'Bermuda Jeans', quantidade: 1, preco: 80.00, unidade_medida: 'un' }
    ],
    dadosPedido: {
        id: '12345',
        forma_pagamento: 'PIX',
        cpf_consumidor: '12345678901'
    },
    dadosEmpresa: {
        nome_loja: 'Top Construções & Moda',
        cpf_cnpj: '12345678000199',
        telefone: '81999999999',
        endereco: 'Rua Principal, 100'
    },
    valorTotal: 175.00,
    dataHora: '18/09/2026 18:30:00',
    subtotalGeral: 180.00,
    totalDescontos: 10.00,
    subtotalItensLiquido: 170.00,
    descontoGlobalValor: 5.00,
    descontoGlobalTipo: 'fixo',
    descontoGlobalObs: 'Desconto Cliente VIP',
    acrescimoValor: 10.00,
    acrescimoTipo: 'Entrega Expressa',
    acrescimoObs: 'Taxa Motoboy'
};

try {
    const txt1 = window.gerarTextoComprovante();
    assert(typeof txt1 === 'string' && txt1.length > 50, "Cenário 1: Gerou texto com sucesso");
    assert(txt1.includes("SUBTOTAL BRUTO"), "Cenário 1: Contém SUBTOTAL BRUTO");
    assert(txt1.includes("DESCONTOS ITENS"), "Cenário 1: Contém DESCONTOS ITENS");
    assert(txt1.includes("SUBTOTAL"), "Cenário 1: Contém SUBTOTAL");
    assert(txt1.includes("DESCONTO VENDA"), "Cenário 1: Contém DESCONTO VENDA");
    assert(txt1.includes("ACRESCIMO / TAXAS"), "Cenário 1: Contém ACRESCIMO / TAXAS");
    assert(txt1.includes("TOTAL"), "Cenário 1: Contém TOTAL");
} catch (e) {
    assert(false, `Cenário 1 falhou com erro: ${e.message}`);
}

// CENÁRIO 2: subtotalItensLiquido NÃO definido (reprodução exata do erro do usuário)
window.dadosComprovanteAtual = {
    carrinho: [
        { nome: 'Tinta Acrílica Coral', quantidade: 1, preco: 120.00 }
    ],
    dadosPedido: {
        id: '9999',
        forma_pagamento: 'DINHEIRO'
    },
    dadosEmpresa: {
        nome_loja: 'Top Construções'
    },
    valorTotal: 120.00,
    dataHora: '18/09/2026 18:35:00',
    subtotalGeral: 120.00,
    totalDescontos: 0
    // subtotalItensLiquido ausente propositalmente
};

try {
    const txt2 = window.gerarTextoComprovante();
    assert(typeof txt2 === 'string' && txt2.length > 50, "Cenário 2 (sem subtotalItensLiquido prévio): Não disparou ReferenceError!");
    assert(txt2.includes("SUBTOTAL"), "Cenário 2: Calculou e exibiu SUBTOTAL corretamente via fallback");
} catch (e) {
    assert(false, `Cenário 2 falhou com erro: ${e.message}`);
}

// CENÁRIO 3: Chamada de imprimirComprovanteTexto
try {
    let deepLinkChamado = null;
    let janelaAberta = false;
    global.window.location = {
        href: 'https://catalogos.oncode.app.br/venda-direta/'
    };
    global.window.open = () => {
        janelaAberta = true;
        return {
            document: { write: () => {}, close: () => {} },
            focus: () => {}
        };
    };
    global.navigator = { userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)' };
    
    window.dadosComprovanteAtual.htmlComprovante = '<html><body>Comprovante</body></html>';
    window.imprimirComprovanteTexto();
    assert(janelaAberta === true, "imprimirComprovanteTexto executou com sucesso no Desktop sem erros");
} catch (e) {
    assert(false, `imprimirComprovanteTexto falhou: ${e.message}`);
}

console.log("=================================================");
console.log(` RESULTADO: ${pass} PASSOU | ${fail} FALHOU`);
console.log("=================================================");

if (fail > 0) process.exit(1);
process.exit(0);
