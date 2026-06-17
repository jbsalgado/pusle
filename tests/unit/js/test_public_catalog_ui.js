
const fs = require('fs');
const path = require('path');

/**
 * Teste unitário simplificado para validar o template do card de produto no catálogo público
 */

const filePath = '/srv/http/pulse/web/catalogo/js/products.js';
const content = fs.readFileSync(filePath, 'utf8');

console.log('--- Iniciando Testes Unitários de Interface - Catálogo Público (Static Analysis) ---\n');

function runTest(name, assertion) {
    try {
        assertion();
        console.log(`  [OK] ${name}`);
    } catch (error) {
        console.error(`  [FALHA] ${name}: ${error.message}`);
        process.exit(1);
    }
}

// 1. Verificar tamanho da fonte do nome do produto
runTest('Tamanho da fonte do nome deve ser text-[13px]', () => {
    if (!content.includes('h3 class="text-[13px]')) {
        throw new Error('A classe text-[13px] não foi encontrada no h3 do nome do produto.');
    }
    if (content.includes('text-lg font-semibold text-gray-800 truncate')) {
        throw new Error('A classe antiga text-lg ainda está presente.');
    }
});

// 2. Verificar se o truncamento foi removido do nome
runTest('Nome do produto não deve ter a classe truncate ou line-clamp', () => {
    const h3Match = content.match(/<h3[^>]*>([\s\S]*?)<\/h3>/);
    if (!h3Match) throw new Error('Tag h3 não encontrada.');
    const h3Tag = h3Match[0];
    if (h3Tag.includes('truncate') || h3Tag.includes('line-clamp')) {
        throw new Error('O nome do produto ainda possui classes de truncamento.');
    }
});

// 3. Verificar inclusão de EAN/Ref
runTest('Linha de EAN/Referência deve estar presente', () => {
    if (!content.includes('EAN:') || !content.includes('Ref:')) {
        throw new Error('A linha de metadados EAN/Ref não foi encontrada.');
    }
});

// 4. Verificar se a descrição foi removida
runTest('Descrição redundante deve ter sido removida', () => {
    if (content.includes('text-gray-500 mb-2 truncate')) {
        throw new Error('A descrição ainda está presente no HTML.');
    }
});

// 5. Verificar posicionamento do selo NF
runTest('Selo NF deve estar dentro do h3', () => {
    const h3ContentMatch = content.match(/<h3[^>]*>([\s\S]*?)<\/h3>/);
    const h3Content = h3ContentMatch[1];
    if (!h3Content.includes('NF')) {
        throw new Error('O selo NF não está posicionado dentro do h3 do nome do produto.');
    }
});

console.log('\n--- Todos os testes unitários do catálogo público passaram com sucesso! ---');
