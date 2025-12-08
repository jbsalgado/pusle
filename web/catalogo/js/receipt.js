// receipt.js - Geração de comprovante de venda para impressora térmica
// Adaptado do venda-direta para o catálogo

/**
 * Gera comprovante de venda para impressora térmica
 */
export async function gerarComprovanteVenda(carrinho, dadosPedido) {
    const now = new Date();
    const dataHora = formatarDataHora(now);
    
    // Busca dados da loja da API
    let dadosEmpresa = {
        nome: 'Loja',
        cpf_cnpj: '',
        telefone: '',
        email: '',
        endereco_completo: '',
        nome_loja: 'Loja'
    };
    
    try {
        // Importa CONFIG dinamicamente
        const { CONFIG, API_ENDPOINTS } = await import('./config.js');
        
        const response = await fetch(`${API_ENDPOINTS.USUARIO_DADOS_LOJA}?usuario_id=${CONFIG.ID_USUARIO_LOJA}`);
        if (response.ok) {
            const dadosLoja = await response.json();
            dadosEmpresa = {
                nome: dadosLoja.nome_loja || dadosLoja.nome || 'Loja',
                cpf_cnpj: dadosLoja.cpf_cnpj || '',
                telefone: dadosLoja.telefone || '',
                email: dadosLoja.email || '',
                endereco: dadosLoja.endereco || '',
                bairro: dadosLoja.bairro || '',
                cidade: dadosLoja.cidade || '',
                estado: dadosLoja.estado || '',
                endereco_completo: dadosLoja.endereco_completo || '',
                logo_path: dadosLoja.logo_path || '',
                nome_loja: dadosLoja.nome_loja || dadosLoja.nome || 'Loja'
            };
        }
    } catch (error) {
        console.warn('[Receipt] Erro ao buscar dados da loja, usando valores padrão:', error);
    }
    
    // Constrói URL da logo se houver
    let logoUrl = '';
    if (dadosEmpresa.logo_path) {
        let logoPath = dadosEmpresa.logo_path.trim();
        
        // Se não for URL completa (http:// ou https://), precisa construir a URL completa
        if (!logoPath.match(/^https?:\/\//)) {
            // Remove barra inicial se houver
            logoPath = logoPath.replace(/^\//, '');
            
            // Tenta usar CONFIG (pode estar disponível globalmente ou precisa importar)
            let baseUrl = '';
            if (window.CONFIG && window.CONFIG.URL_BASE_WEB) {
                baseUrl = window.CONFIG.URL_BASE_WEB.replace(/\/$/, '');
            } else {
                try {
                    const { CONFIG } = await import('./config.js');
                    if (CONFIG && CONFIG.URL_BASE_WEB) {
                        baseUrl = CONFIG.URL_BASE_WEB.replace(/\/$/, '');
                    }
                } catch (e) {
                    console.warn('[Receipt] Não foi possível importar CONFIG, usando fallback');
                }
            }
            
            // Se ainda não tem baseUrl, usa window.location como fallback
            if (!baseUrl) {
                const pathParts = window.location.pathname.split('/').filter(p => p);
                // Remove 'catalogo' ou 'index.html' do final
                pathParts.pop();
                baseUrl = window.location.origin + (pathParts.length > 0 ? '/' + pathParts.join('/') : '');
            }
            
            logoUrl = baseUrl + '/' + logoPath;
        } else {
            // URL completa - usa como está
            logoUrl = logoPath;
        }
    }
    
    // Formata CPF/CNPJ
    const cpfCnpjLimpo = dadosEmpresa.cpf_cnpj ? dadosEmpresa.cpf_cnpj.replace(/[^\d]/g, '') : '';
    const cpfCnpjFormatado = formatarCpfCnpj(dadosEmpresa.cpf_cnpj);
    const isCNPJ = cpfCnpjLimpo.length === 14;
    
    // Formata telefone
    const telefoneFormatado = formatarTelefone(dadosEmpresa.telefone);
    
    // Monta endereço completo a partir dos campos individuais ou usa endereco_completo
    let endereco = '';
    let cidade = '';
    
    if (dadosEmpresa.endereco || dadosEmpresa.bairro || dadosEmpresa.cidade || dadosEmpresa.estado) {
        // Usa campos individuais
        endereco = dadosEmpresa.endereco || '';
        const partesCidade = [];
        if (dadosEmpresa.bairro) partesCidade.push(dadosEmpresa.bairro);
        if (dadosEmpresa.cidade) partesCidade.push(dadosEmpresa.cidade);
        if (dadosEmpresa.estado) partesCidade.push(dadosEmpresa.estado);
        cidade = partesCidade.join(', ');
    } else if (dadosEmpresa.endereco_completo) {
        // Fallback: usa endereco_completo e separa
        const enderecoPartes = dadosEmpresa.endereco_completo.split(',');
        endereco = enderecoPartes[0] || '';
        cidade = enderecoPartes.slice(1).join(', ').trim() || '';
    }
    
    // Calcula totais
    const valorTotal = carrinho.reduce((total, item) => {
        const preco = parseFloat(item.preco || item.preco_venda_sugerido || item.preco_unitario || 0);
        const qtd = parseFloat(item.quantidade || 0);
        return total + (preco * qtd);
    }, 0);
    
    // Formata valor
    const valorFormatado = formatarMoeda(valorTotal);
    
    // Função auxiliar para formatar valores no template
    const formatarValor = (val) => {
        return `R$ ${parseFloat(val).toFixed(2).replace('.', ',')}`;
    };
    
    // Cria HTML do comprovante
    const html = `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Comprovante de Venda</title>
    <style>
        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }
            body {
                margin: 0;
                padding: 5mm;
            }
        }
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            width: 80mm;
            margin: 0 auto;
            padding: 5mm;
            line-height: 1.3;
        }
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
            margin-bottom: 5px;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 5px;
        }
        .logo-container img {
            max-width: 60mm;
            max-height: 30mm;
            height: auto;
            object-fit: contain;
        }
        .empresa-nome {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 3px;
        }
        .empresa-dados {
            font-size: 10px;
            margin: 2px 0;
        }
        .titulo {
            text-align: center;
            font-weight: bold;
            font-size: 13px;
            margin: 8px 0;
            text-transform: uppercase;
        }
        .linha {
            border-bottom: 1px dashed #000;
            margin: 5px 0;
            padding-bottom: 3px;
        }
        .item {
            margin: 4px 0;
        }
        .item-descricao {
            font-weight: bold;
            margin-bottom: 2px;
        }
        .item-detalhes {
            font-size: 10px;
            display: flex;
            justify-content: space-between;
        }
        .total {
            text-align: right;
            font-weight: bold;
            font-size: 13px;
            margin-top: 8px;
            padding-top: 5px;
            border-top: 2px solid #000;
        }
        .pagamento {
            margin: 8px 0;
            padding: 5px 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
        }
        .pagamento-tipo {
            font-weight: bold;
            margin-bottom: 3px;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 10px;
            padding-top: 5px;
            border-top: 1px dashed #000;
        }
        .data-hora {
            text-align: center;
            font-size: 10px;
            margin: 5px 0;
        }
        .separador {
            text-align: center;
            margin: 5px 0;
            font-size: 10px;
        }
        .tabela-parcelas {
            margin: 10px 0;
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        .tabela-parcelas th,
        .tabela-parcelas td {
            border: 1px solid #000;
            padding: 3px;
            text-align: left;
        }
        .tabela-parcelas th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .tabela-parcelas .col-par {
            width: 15%;
            text-align: center;
        }
        .tabela-parcelas .col-data {
            width: 45%;
            text-align: center;
        }
        .tabela-parcelas .col-valor {
            width: 40%;
            text-align: right;
        }
        .parcela-paga {
            text-decoration: line-through;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        ${logoUrl ? `
        <div class="logo-container">
            <img src="${logoUrl}" alt="Logo" onerror="this.style.display='none';">
        </div>
        ` : ''}
        <div class="empresa-nome">${dadosEmpresa.nome_loja || dadosEmpresa.nome}</div>
        ${cpfCnpjFormatado ? `<div class="empresa-dados">${isCNPJ ? 'CNPJ' : 'CPF'}: ${cpfCnpjFormatado}</div>` : ''}
        ${endereco ? `<div class="empresa-dados">${endereco}</div>` : ''}
        ${cidade ? `<div class="empresa-dados">${cidade}</div>` : ''}
        ${telefoneFormatado ? `<div class="empresa-dados">Fone: ${telefoneFormatado}</div>` : ''}
    </div>
    
    <div class="titulo">COMPROVANTE DE VENDA</div>
    
    <div class="data-hora">
        ${dataHora}
    </div>
    
    <div class="linha"></div>
    
    <div class="separador">--------------------------------</div>
    
    ${carrinho.map(item => {
        const preco = parseFloat(item.preco || item.preco_venda_sugerido || item.preco_unitario || 0);
        const qtd = parseFloat(item.quantidade || 0);
        const subtotal = preco * qtd;
        const nomeProduto = item.nome || item.descricao || item.nome_produto || 'Produto';
        return `
        <div class="item">
            <div class="item-descricao">${nomeProduto}</div>
            <div class="item-detalhes">
                <span>${qtd.toFixed(2)} x ${formatarValor(preco)}</span>
                <span>${formatarValor(subtotal)}</span>
            </div>
        </div>
    `;
    }).join('')}
    
    <div class="separador">--------------------------------</div>
    
    <div class="total">
        TOTAL: ${valorFormatado}
    </div>
    
    <div class="pagamento">
        <div class="pagamento-tipo">FORMA DE PAGAMENTO: ${dadosPedido.forma_pagamento || 'Não informado'}</div>
        ${dadosPedido.numero_parcelas === 1 ? `<div>VALOR PAGO: ${valorFormatado}</div>` : `<div>${dadosPedido.numero_parcelas}x de ${formatarMoeda(valorTotal / dadosPedido.numero_parcelas)}</div>`}
    </div>
    
    ${(() => {
        const temParcelas = dadosPedido.parcelas && Array.isArray(dadosPedido.parcelas) && dadosPedido.parcelas.length > 0;
        const numeroParcelas = dadosPedido.numero_parcelas || 0;
        const deveMostrar = temParcelas && numeroParcelas > 1;
        
        if (!deveMostrar) {
            return '';
        }
        
        return `
    <div class="separador" style="margin-top: 10px;">--------------------------------</div>
    <div style="margin-top: 10px;">
        <div style="font-weight: bold; text-align: center; margin-bottom: 5px; font-size: 11px;">PARCELAS</div>
        <table class="tabela-parcelas">
            <thead>
                <tr>
                    <th style="width: 15%; text-align: center;">PAR.</th>
                    <th style="width: 45%; text-align: center;">DT VENCIMENTO</th>
                    <th style="width: 40%; text-align: right;">VALOR PREST.</th>
                </tr>
            </thead>
            <tbody>
                ${dadosPedido.parcelas.map((parcela, index) => {
                    const dataVenc = new Date(parcela.data_vencimento);
                    const dataFormatada = String(dataVenc.getDate()).padStart(2, '0') + '/' + 
                                        String(dataVenc.getMonth() + 1).padStart(2, '0') + '/' +
                                        String(dataVenc.getFullYear());
                    const valorParcela = parseFloat(parcela.valor_parcela).toFixed(2).replace('.', ',');
                    const numeroParcela = String(index + 1).padStart(2, '0');
                    const isPaga = parcela.status_parcela_codigo === 'PAGA' || parcela.data_pagamento;
                    const classePaga = isPaga ? 'parcela-paga' : '';
                    
                    return `
                    <tr class="${classePaga}">
                        <td style="text-align: center;">${numeroParcela}</td>
                        <td style="text-align: center;">${dataFormatada}</td>
                        <td style="text-align: right;">${valorParcela}</td>
                    </tr>
                    `;
                }).join('')}
            </tbody>
        </table>
    </div>
    `;
    })()}
    
    <div class="footer">
        <div>Obrigado pela preferência!</div>
        <div style="margin-top: 5px;">${dadosEmpresa.nome_loja || dadosEmpresa.nome}</div>
    </div>
    
    <div class="separador" style="margin-top: 10px;">================================</div>
</body>
</html>
    `;
    
    // Abre janela de impressão
    const janelaImpressao = window.open('', '_blank', 'width=300,height=600');
    janelaImpressao.document.write(html);
    janelaImpressao.document.close();
    
    // Aguarda carregamento e imprime
    setTimeout(() => {
        janelaImpressao.focus();
        janelaImpressao.print();
    }, 250);
}

/**
 * Formata data e hora para o comprovante
 */
function formatarDataHora(data) {
    const dia = String(data.getDate()).padStart(2, '0');
    const mes = String(data.getMonth() + 1).padStart(2, '0');
    const ano = data.getFullYear();
    const hora = String(data.getHours()).padStart(2, '0');
    const minuto = String(data.getMinutes()).padStart(2, '0');
    const segundo = String(data.getSeconds()).padStart(2, '0');
    
    return `${dia}/${mes}/${ano} ${hora}:${minuto}:${segundo}`;
}

/**
 * Formata valor monetário
 */
function formatarMoeda(valor) {
    return `R$ ${parseFloat(valor).toFixed(2).replace('.', ',')}`;
}

/**
 * Formata CPF ou CNPJ
 */
function formatarCpfCnpj(cpfCnpj) {
    if (!cpfCnpj) return '';
    const limpo = cpfCnpj.replace(/[^\d]/g, '');
    
    if (limpo.length === 11) {
        // CPF: 000.000.000-00
        return limpo.replace(/^(\d{3})(\d{3})(\d{3})(\d{2})$/, '$1.$2.$3-$4');
    } else if (limpo.length === 14) {
        // CNPJ: 00.000.000/0000-00
        return limpo.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
    }
    
    return cpfCnpj;
}

/**
 * Formata telefone
 */
function formatarTelefone(telefone) {
    if (!telefone) return '';
    const limpo = telefone.replace(/[^\d]/g, '');
    
    if (limpo.length === 11) {
        // (00) 00000-0000
        return limpo.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
    } else if (limpo.length === 10) {
        // (00) 0000-0000
        return limpo.replace(/^(\d{2})(\d{4})(\d{4})$/, '($1) $2-$3');
    }
    
    return telefone;
}

