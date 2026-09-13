<?php
/** @var yii\web\View $this */
/** @var app\modules\vendas\models\Venda $cartao */
/** @var app\modules\vendas\models\HistoricoCobranca[] $historico */
/** @var string $token */

use yii\helpers\Html;
use yii\helpers\Url;

$usuario = $cartao->usuario;
$lojaNome = $usuario->nome_loja ?? $usuario->nome ?? 'CREDIÁRIOS PULSE';
$lojaTelefone = $usuario->telefone ?? '';
$cliente = $cartao->cliente;
$itens = $cartao->itens;
$parcelas = $cartao->parcelas;
$vendedor = $cartao->vendedor;

$totalPago = 0;
$temAtraso = false;
$hoje = date('Y-m-d');

foreach ($parcelas as $p) {
    if ($p->status_parcela_codigo === 'PAGA') {
        $totalPago += (float)($p->valor_pago ?: $p->valor_parcela);
    } elseif ($p->status_parcela_codigo === 'PENDENTE' && $p->data_vencimento < $hoje) {
        $temAtraso = true;
    }
}

$estaQuitado = in_array($cartao->status_venda_codigo, ['FINALIZADA', 'QUITADA']) || ($totalPago >= (float)$cartao->valor_total && count($parcelas) > 0);
$saldoDevedor = max(0, (float)$cartao->valor_total - $totalPago);

$slugCliente = preg_replace('/[^a-zA-Z0-9_-]/', '_', $cliente->nome ?? $cliente->nome_completo ?? 'cliente');
$nomeArquivo = 'cartao_' . str_pad($cartao->id, 5, '0', STR_PAD_LEFT) . '_' . $slugCliente;
$publicUrl = Url::to(['/prestanista/cartao/publico', 'id' => $cartao->id, 'token' => $token], true);

// Formatação do número curto
$idCurto = strlen($cartao->id) > 8 ? strtoupper(substr($cartao->id, 0, 8)) : str_pad($cartao->id, 5, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Cartão de Crediário #<?= $idCurto ?> - <?= Html::encode($cliente->nome ?? $cliente->nome_completo ?? 'Cliente') ?></title>
    
    <!-- TailwindCSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        mono: ['Courier New', 'Courier', 'monospace'],
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Bibliotecas para Geração de Imagem e PDF via Client-side -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .font-cupom { font-family: 'Courier New', Courier, monospace; }

        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; padding: 0 !important; color: #000 !important; }
            .print-card-container { box-shadow: none !important; border: 1.5px solid #000 !important; margin: 0 auto !important; }
        }
    </style>
</head>
<body class="min-h-full bg-slate-950 text-slate-100 flex flex-col items-center p-3 sm:p-6 antialiased">

    <!-- Container Central -->
    <div class="w-full max-w-2xl space-y-4">

        <!-- Topo da Consulta Pública -->
        <header class="no-print bg-slate-900/90 border border-slate-800 p-4 rounded-2xl sm:rounded-3xl shadow-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xl">📇</span>
                    <h1 class="text-base sm:text-lg font-black text-white tracking-tight">
                        <?= Html::encode($lojaNome) ?>
                    </h1>
                </div>
                <p class="text-xs text-slate-400 mt-0.5 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Consulta Oficial de Crediário • Atualizado em tempo real</span>
                </p>
            </div>

            <!-- Badge de Status -->
            <div>
                <?php if ($estaQuitado): ?>
                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 rounded-full text-xs font-black uppercase">
                        ✓ Quitado
                    </span>
                <?php elseif ($temAtraso): ?>
                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-rose-500/15 text-rose-400 border border-rose-500/30 rounded-full text-xs font-black uppercase">
                        ⚠️ Parcela Atrasada
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-amber-500/15 text-amber-400 border border-amber-500/30 rounded-full text-xs font-black uppercase">
                        🟡 Em Cobrança
                    </span>
                <?php endif; ?>
            </div>
        </header>

        <!-- Barra de Ações: Baixar PDF, Imagem, WhatsApp e Impressão -->
        <div class="no-print bg-slate-900 border border-slate-800 p-3 rounded-2xl shadow flex items-center justify-between gap-2 flex-wrap">
            <div class="flex items-center gap-1.5 flex-wrap flex-1">
                <!-- Baixar PDF -->
                <button type="button" id="btnBaixarPdf" onclick="baixarPDF()" class="px-3 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs rounded-xl shadow transition active:scale-95 flex items-center gap-1.5">
                    <span>📄</span>
                    <span>Baixar PDF</span>
                </button>

                <!-- Baixar Imagem -->
                <button type="button" id="btnBaixarImg" onclick="baixarImagem()" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white font-bold text-xs rounded-xl border border-slate-700 transition active:scale-95 flex items-center gap-1.5">
                    <span>🖼️</span>
                    <span>Salvar Imagem</span>
                </button>

                <!-- Compartilhar no WhatsApp -->
                <?php 
                    $msgWhats = "Olá! Segue o link para acompanhar o Cartão de Crediário #" . $idCurto . " na " . $lojaNome . ":\n" . $publicUrl . "\n\nSaldo restante: R$ " . number_format($saldoDevedor, 2, ',', '.');
                ?>
                <a href="https://api.whatsapp.com/send?text=<?= urlencode($msgWhats) ?>" target="_blank" class="px-3 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs rounded-xl shadow transition active:scale-95 flex items-center gap-1.5">
                    <span>💬</span>
                    <span>Compartilhar</span>
                </a>

                <!-- Imprimir -->
                <button type="button" onclick="window.print()" class="p-2 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white rounded-xl border border-slate-700 transition active:scale-95" title="Imprimir Cartão">
                    🖨️
                </button>
            </div>

            <!-- Alternador de Visão no Celular -->
            <div class="flex items-center bg-slate-950 p-1 rounded-xl border border-slate-800 text-[11px]">
                <button type="button" id="btnTabTalao" onclick="alternarVisao('talao')" class="px-2.5 py-1 rounded-lg font-bold bg-amber-500 text-slate-950 transition">
                    Talão Físico
                </button>
                <button type="button" id="btnTabFichas" onclick="alternarVisao('fichas')" class="px-2.5 py-1 rounded-lg font-bold text-slate-400 hover:text-white transition">
                    Fichas Digitais
                </button>
            </div>
        </div>

        <!-- Alerta de Toast para Downloads e Cópias -->
        <div id="toastAlert" class="hidden no-print bg-amber-500 text-slate-950 text-xs font-black px-4 py-2.5 rounded-xl shadow-lg flex items-center justify-center gap-2 transition animate-pulse">
            <span id="toastMsg">Gerando documento...</span>
        </div>

        <!-- 1. O TALÃO CLÁSSICO DE CREDIÁRIO (ELEMENTO PARA EXIBIÇÃO E DOWNLOAD) -->
        <div id="visao-talao" class="w-full">
            <div id="cartao-imprimir" class="print-card-container bg-amber-50/95 text-slate-900 border-2 sm:border-4 border-slate-900 rounded-2xl sm:rounded-3xl p-3 sm:p-6 shadow-2xl font-serif relative overflow-hidden">
                
                <!-- Moldura Interna do Talão -->
                <div class="border sm:border-2 border-slate-900 rounded-xl sm:rounded-2xl p-2.5 sm:p-5 bg-white/60">

                    <!-- Cabeçalho do Cartão -->
                    <div class="text-center border-b-2 border-slate-900 pb-2 mb-3">
                        <p class="text-[9px] sm:text-[10px] uppercase font-bold tracking-widest text-slate-600">Nosso prazer é atendê-lo bem</p>
                        <h2 class="text-lg sm:text-2xl font-black uppercase tracking-tight text-slate-950 mt-0.5 leading-tight">
                            <?= Html::encode($lojaNome) ?>
                        </h2>
                        <p class="text-[10px] sm:text-[11px] uppercase font-bold text-slate-700 tracking-wider">CREDIÁRIOS & UTILIDADES</p>

                        <div class="grid grid-cols-3 text-left text-[11px] sm:text-xs font-mono font-bold mt-2 pt-1.5 border-t border-slate-400 items-center">
                            <div>DATA: <span class="font-sans font-black whitespace-nowrap"><?= date('d/m/Y', strtotime($cartao->data_venda)) ?></span></div>
                            <div class="text-center">FLS: <span class="font-sans font-black">01</span></div>
                            <div class="text-right whitespace-nowrap">Nº: <span class="font-sans font-black text-amber-800">#<?= $idCurto ?></span></div>
                        </div>
                    </div>

                    <!-- Dados do Cliente -->
                    <div class="border-b-2 border-slate-900 pb-2 mb-3 text-[11px] sm:text-xs font-mono space-y-1">
                        <div class="flex items-baseline">
                            <span class="font-bold w-14">SR.(A):</span>
                            <span class="font-sans font-black text-slate-950 flex-1 truncate uppercase">
                                <?= Html::encode($cliente->nome ?? $cliente->nome_completo ?? 'Cliente Avulso') ?>
                            </span>
                        </div>
                        <div class="flex items-baseline">
                            <span class="font-bold w-14">END.:</span>
                            <span class="font-sans font-bold text-slate-800 flex-1 truncate">
                                <?= Html::encode($cliente->logradouro ?: $cliente->endereco_logradouro ?: 'Sem endereço') ?>
                                <?= !empty($cliente->numero) ? ', ' . Html::encode($cliente->numero) : '' ?>
                            </span>
                        </div>
                        <div class="flex items-baseline justify-between flex-wrap gap-1">
                            <div>
                                <span class="font-bold">BAIRRO:</span>
                                <span class="font-sans font-bold text-slate-800"><?= Html::encode($cliente->bairro ?: $cliente->endereco_bairro ?: '—') ?></span>
                            </div>
                            <div>
                                <span class="font-bold">CIDADE:</span>
                                <span class="font-sans font-bold text-slate-800"><?= Html::encode($cliente->cidade ?: $cliente->endereco_cidade ?: '—') ?></span>
                            </div>
                        </div>
                        <?php if ($vendedor): ?>
                            <div class="flex items-baseline pt-0.5">
                                <span class="font-bold w-14">VEND.:</span>
                                <span class="font-sans font-bold text-slate-700 flex-1 truncate uppercase">
                                    <?= Html::encode($vendedor->nome_completo) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tabela de Mercadorias (Objetos) -->
                    <div class="mb-3">
                        <div class="overflow-x-auto -mx-1 px-1 sm:mx-0 sm:px-0">
                            <table class="w-full text-[10px] sm:text-xs font-mono border-collapse">
                                <thead>
                                    <tr class="border-b-2 border-slate-900 text-slate-700">
                                        <th class="text-left py-1 w-12">OBJ.</th>
                                        <th class="text-left py-1">MERCADORIAS</th>
                                        <th class="text-center py-1 w-12">QTD</th>
                                        <th class="text-right py-1 w-20 whitespace-nowrap">VL. UNIT.</th>
                                        <th class="text-right py-1 w-20 whitespace-nowrap">VALOR</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($itens)): ?>
                                        <tr class="border-b border-dashed border-slate-300">
                                            <td class="py-1">01</td>
                                            <td class="py-1 font-bold">Compra no Crediário</td>
                                            <td class="py-1 text-center">1</td>
                                            <td class="py-1 text-right whitespace-nowrap">R$ <?= number_format($cartao->valor_total, 2, ',', '.') ?></td>
                                            <td class="py-1 text-right whitespace-nowrap font-bold">R$ <?= number_format($cartao->valor_total, 2, ',', '.') ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($itens as $idx => $item): ?>
                                            <?php 
                                                $qtdFormatada = ($item->quantidade == (int)$item->quantidade) 
                                                    ? (int)$item->quantidade 
                                                    : number_format($item->quantidade, 2, ',', '.');
                                            ?>
                                            <tr class="border-b border-dashed border-slate-300">
                                                <td class="py-1"><?= str_pad($idx + 1, 2, '0', STR_PAD_LEFT) ?></td>
                                                <td class="py-1 font-bold truncate max-w-[130px] sm:max-w-none uppercase">
                                                    <?= Html::encode($item->produto->nome ?? $item->descricao ?? 'Item') ?>
                                                </td>
                                                <td class="py-1 text-center font-bold"><?= $qtdFormatada ?></td>
                                                <td class="py-1 text-right whitespace-nowrap font-mono text-slate-800">
                                                    R$ <?= number_format($item->valor_unitario, 2, ',', '.') ?>
                                                </td>
                                                <td class="py-1 text-right whitespace-nowrap font-bold">
                                                    R$ <?= number_format($item->valor_total, 2, ',', '.') ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="border-t-2 border-slate-900 font-sans font-black">
                                        <td colspan="4" class="text-right py-1.5 uppercase text-[10px] sm:text-xs text-slate-800 pr-2">
                                            TOTAL DO CARTÃO:
                                        </td>
                                        <td class="text-right py-1.5 text-xs sm:text-sm text-slate-950 whitespace-nowrap">
                                            R$ <?= number_format($cartao->valor_total, 2, ',', '.') ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Grade de Prestações (Tabela Completa de Amortização) -->
                    <div>
                        <div class="text-[9px] sm:text-[10px] text-center font-bold text-slate-600 mb-1 no-print">
                            👈 Deslize para o lado para ver Data de Pagamento e Saldo 👉
                        </div>
                        <div class="overflow-x-auto -mx-1 px-1 sm:mx-0 sm:px-0">
                            <table class="w-full text-[9px] sm:text-[10px] font-mono border border-slate-900 border-collapse bg-white">
                                <thead>
                                    <tr class="bg-slate-200/90 text-slate-950 font-black border-b border-slate-900 text-center">
                                        <th class="p-1 border-r border-slate-900">DATA PREST.</th>
                                        <th class="p-1 border-r border-slate-900">VL. PREST.</th>
                                        <th class="p-1 border-r border-slate-900">VL. COMPRA</th>
                                        <th class="p-1 border-r border-slate-900">DATA PAG.</th>
                                        <th class="p-1 border-r border-slate-900">VL. RECEB.</th>
                                        <th class="p-1 border-r border-slate-900">TIPO</th>
                                        <th class="p-1 border-r border-slate-900 text-left pl-2">SALDO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                        $saldoAcumulado = (float)$cartao->valor_total;
                                    ?>
                                    <?php foreach ($parcelas as $p): ?>
                                        <?php 
                                            $isPaga = $p->status_parcela_codigo === 'PAGA';
                                            $vlRecebido = $isPaga ? ($p->valor_pago ?: $p->valor_parcela) : 0;
                                            
                                            if ($isPaga) {
                                                $saldoAcumulado = max(0, $saldoAcumulado - $vlRecebido);
                                                $saldoExibido = 'R$ ' . number_format($saldoAcumulado, 2, ',', '.');
                                            } else {
                                                $saldoExibido = '—';
                                            }

                                            $tipoNome = '—';
                                            if ($isPaga) {
                                                $tipoNome = $p->formaPagamento ? $p->formaPagamento->nome : 'PIX';
                                            }
                                        ?>
                                        <tr class="border-b border-slate-400 text-center font-bold <?= $isPaga ? 'bg-emerald-50/50' : '' ?>">
                                            <td class="p-1 border-r border-slate-400 whitespace-nowrap text-slate-900">
                                                <?= date('d/m/Y', strtotime($p->data_vencimento)) ?>
                                            </td>
                                            <td class="p-1 border-r border-slate-400 whitespace-nowrap text-slate-900">
                                                <?= number_format($p->valor_parcela, 2, ',', '.') ?>
                                            </td>
                                            <td class="p-1 border-r border-slate-400 whitespace-nowrap text-slate-600">
                                                <?= number_format($cartao->valor_total, 2, ',', '.') ?>
                                            </td>
                                            <td class="p-1 border-r border-slate-400 whitespace-nowrap <?= $isPaga ? 'text-emerald-800' : 'text-slate-400' ?>">
                                                <?= $isPaga ? ($p->data_pagamento ? date('d/m/Y', strtotime($p->data_pagamento)) : 'Pago') : '—' ?>
                                            </td>
                                            <td class="p-1 border-r border-slate-400 whitespace-nowrap <?= $isPaga ? 'text-emerald-800' : 'text-slate-400' ?>">
                                                <?= $isPaga ? number_format($vlRecebido, 2, ',', '.') : '—' ?>
                                            </td>
                                            <td class="p-1 border-r border-slate-400 uppercase text-[8px] sm:text-[9px] <?= $isPaga ? 'text-blue-900' : 'text-slate-400' ?>">
                                                <?= Html::encode($tipoNome) ?>
                                            </td>
                                            <td class="p-1 border-r border-slate-900 font-black text-left pl-2 whitespace-nowrap <?= $isPaga ? 'text-amber-950' : 'text-slate-400' ?>">
                                                <?= $saldoExibido ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Resumo Final do Cartão -->
                    <div class="mt-4 pt-3 border-t-2 border-slate-900 grid grid-cols-2 gap-2 text-xs font-mono">
                        <div class="bg-white/80 p-2.5 rounded-xl border border-slate-300">
                            <span class="block text-[10px] text-slate-500 uppercase font-bold">Total Já Pago</span>
                            <span class="text-sm font-black text-emerald-800">
                                R$ <?= number_format($totalPago, 2, ',', '.') ?>
                            </span>
                        </div>
                        <div class="bg-white/80 p-2.5 rounded-xl border border-slate-300 text-right">
                            <span class="block text-[10px] text-slate-500 uppercase font-bold">Saldo Restante</span>
                            <span class="text-sm font-black <?= $saldoDevedor > 0 ? 'text-amber-900' : 'text-emerald-700' ?>">
                                R$ <?= number_format($saldoDevedor, 2, ',', '.') ?>
                            </span>
                        </div>
                    </div>

                    <!-- Rodapé do Cartão Físico -->
                    <div class="text-center text-[8px] text-slate-500 border-t border-slate-400 pt-2 mt-3 font-mono">
                        Documento oficial emitido por <?= Html::encode($lojaNome) ?> • Consulta online em tempo real
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. VISÃO DE FICHAS DIGITAIS (CARDS INDIVIDUAIS PARA SMARTPHONE) -->
        <div id="visao-fichas" class="hidden space-y-3 w-full no-print">
            
            <!-- Resumo Rápido -->
            <div class="bg-slate-900 border border-slate-800 p-4 rounded-2xl grid grid-cols-2 gap-3 text-xs">
                <div>
                    <span class="text-slate-400 text-[11px] block">Valor Total</span>
                    <span class="text-base font-black text-white">R$ <?= number_format($cartao->valor_total, 2, ',', '.') ?></span>
                </div>
                <div class="text-right">
                    <span class="text-slate-400 text-[11px] block">Saldo a Pagar</span>
                    <span class="text-base font-black <?= $saldoDevedor > 0 ? 'text-amber-400' : 'text-emerald-400' ?>">
                        R$ <?= number_format($saldoDevedor, 2, ',', '.') ?>
                    </span>
                </div>
            </div>

            <!-- Lista de Parcelas em Cards -->
            <div class="space-y-2.5">
                <?php 
                    $saldoCard = (float)$cartao->valor_total;
                ?>
                <?php foreach ($parcelas as $idx => $p): ?>
                    <?php
                        $isPaga = $p->status_parcela_codigo === 'PAGA';
                        $isVencida = (!$isPaga && $p->data_vencimento < $hoje);
                        $vlRecebido = $isPaga ? ($p->valor_pago ?: $p->valor_parcela) : 0;
                        if ($isPaga) {
                            $saldoCard = max(0, $saldoCard - $vlRecebido);
                        }
                    ?>
                    <div class="bg-slate-900 border <?= $isPaga ? 'border-emerald-500/30 bg-emerald-950/10' : ($isVencida ? 'border-rose-500/30 bg-rose-950/10' : 'border-slate-800') ?> p-3.5 rounded-2xl shadow-sm flex items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 rounded-lg text-[10px] font-black <?= $isPaga ? 'bg-emerald-500/20 text-emerald-400' : ($isVencida ? 'bg-rose-500/20 text-rose-400' : 'bg-slate-800 text-slate-300') ?>">
                                    <?= $p->numero_parcela ?>ª Parcela
                                </span>
                                <span class="text-xs text-slate-400 font-bold">
                                    Vencimento: <?= date('d/m/Y', strtotime($p->data_vencimento)) ?>
                                </span>
                            </div>
                            <div class="text-xs text-slate-400 space-y-0.5">
                                <?php if ($isPaga): ?>
                                    <p class="text-emerald-400 text-[11px] font-bold">
                                        ✓ Paga em <?= $p->data_pagamento ? date('d/m/Y', strtotime($p->data_pagamento)) : 'sim' ?> via <?= Html::encode($p->formaPagamento->nome ?? 'PIX') ?>
                                    </p>
                                    <p class="text-[11px] text-slate-500 font-mono">
                                        Saldo restante após baixa: R$ <?= number_format($saldoCard, 2, ',', '.') ?>
                                    </p>
                                <?php elseif ($isVencida): ?>
                                    <p class="text-rose-400 text-[11px] font-bold">
                                        ⚠️ Vencida em <?= date('d/m/Y', strtotime($p->data_vencimento)) ?>
                                    </p>
                                <?php else: ?>
                                    <p class="text-slate-400 text-[11px]">
                                        Aguardando pagamento
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="text-right">
                            <span class="block text-xs text-slate-400 uppercase font-bold">Valor</span>
                            <span class="text-sm sm:text-base font-black <?= $isPaga ? 'text-emerald-400' : 'text-white' ?>">
                                R$ <?= number_format($p->valor_parcela, 2, ',', '.') ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Seção de Histórico de Pagamentos (Comprovantes) -->
        <?php if (!empty($historico)): ?>
            <div class="no-print bg-slate-900 border border-slate-800 p-4 rounded-2xl shadow space-y-2">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                    <span>🧾</span> Histórico de Pagamentos Registrados
                </h3>
                <div class="divide-y divide-slate-800 text-xs">
                    <?php foreach ($historico as $h): ?>
                        <div class="py-2 flex items-center justify-between gap-2">
                            <div>
                                <span class="font-bold text-white block">
                                    Recebimento de R$ <?= number_format($h->valor_recebido, 2, ',', '.') ?>
                                </span>
                                <span class="text-[11px] text-slate-400">
                                    <?= date('d/m/Y \à\s H:i', strtotime($h->data_acao)) ?>
                                    <?= $h->cobrador ? '• Cobrador: ' . Html::encode($h->cobrador->nome_completo) : '' ?>
                                </span>
                            </div>
                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-black uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                Confirmado
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Rodapé Institucional Seguro -->
        <footer class="no-print text-center text-xs text-slate-500 py-6 border-t border-slate-900 space-y-1">
            <p>
                🔒 Link Oficial de Consulta • Emitido por <strong class="text-slate-300"><?= Html::encode($lojaNome) ?></strong>
            </p>
            <p class="text-[11px] text-slate-600">
                Página exclusivamente para visualização e conferência. Todos os direitos reservados.
            </p>
        </footer>

    </div>

    <!-- Scripts de Geração de PDF e Imagem -->
    <script>
        const nomeArquivo = '<?= $nomeArquivo ?>';

        function showToast(msg) {
            const toast = document.getElementById('toastAlert');
            const toastMsg = document.getElementById('toastMsg');
            toastMsg.innerText = msg;
            toast.classList.remove('hidden');
        }

        function hideToast() {
            const toast = document.getElementById('toastAlert');
            toast.classList.add('hidden');
        }

        // Alternar entre Talão Tradicional e Fichas Digitais
        function alternarVisao(modo) {
            const visaoTalao = document.getElementById('visao-talao');
            const visaoFichas = document.getElementById('visao-fichas');
            const btnTalao = document.getElementById('btnTabTalao');
            const btnFichas = document.getElementById('btnTabFichas');

            if (modo === 'fichas') {
                visaoTalao.classList.add('hidden');
                visaoFichas.classList.remove('hidden');
                btnFichas.classList.add('bg-amber-500', 'text-slate-950');
                btnFichas.classList.remove('text-slate-400');
                btnTalao.classList.remove('bg-amber-500', 'text-slate-950');
                btnTalao.classList.add('text-slate-400');
            } else {
                visaoFichas.classList.add('hidden');
                visaoTalao.classList.remove('hidden');
                btnTalao.classList.add('bg-amber-500', 'text-slate-950');
                btnTalao.classList.remove('text-slate-400');
                btnFichas.classList.remove('bg-amber-500', 'text-slate-950');
                btnFichas.classList.add('text-slate-400');
            }
        }

        // Baixar Imagem PNG em Alta Resolução
        function baixarImagem() {
            const btn = document.getElementById('btnBaixarImg');
            // Garante que a visão do talão está ativa para capturar a imagem
            alternarVisao('talao');
            const element = document.getElementById('cartao-imprimir');
            btn.disabled = true;
            showToast('Gerando imagem em alta resolução...');

            html2canvas(element, {
                scale: 3,
                useCORS: true,
                backgroundColor: '#ffffff'
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = nomeArquivo + '.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
                hideToast();
                btn.disabled = false;
            }).catch(err => {
                alert('Erro ao gerar imagem: ' + err.message);
                hideToast();
                btn.disabled = false;
            });
        }

        // Baixar PDF no Formato 1/4 Folha A4
        function baixarPDF() {
            const btn = document.getElementById('btnBaixarPdf');
            alternarVisao('talao');
            const element = document.getElementById('cartao-imprimir');
            btn.disabled = true;
            showToast('Gerando arquivo PDF em alta definição...');

            html2canvas(element, {
                scale: 3,
                useCORS: true,
                backgroundColor: '#ffffff'
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const { jsPDF } = window.jspdf;
                // Formato A6: 105mm x 148.5mm
                const pdf = new jsPDF({
                    orientation: 'portrait',
                    unit: 'mm',
                    format: [105, 148.5]
                });

                const imgWidth = 100;
                const pageHeight = 148.5;
                const imgHeight = (canvas.height * imgWidth) / canvas.width;
                
                pdf.addImage(imgData, 'PNG', 2.5, 2.5, imgWidth, Math.min(imgHeight, 143.5));
                pdf.save(nomeArquivo + '.pdf');

                hideToast();
                btn.disabled = false;
            }).catch(err => {
                alert('Erro ao gerar PDF: ' + err.message);
                hideToast();
                btn.disabled = false;
            });
        }
    </script>
</body>
</html>
