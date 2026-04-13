<?php

/**
 * View: Relatório para Impressão de Itens Avulsos
 * @var array $dados
 * @var string $lojaNome
 */

use yii\helpers\Html;

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Itens Avulsos - <?= Html::encode($lojaNome) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none; }
            body { background: white; }
            .print-padding { padding: 0 !important; }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans print-padding">

    <div class="max-w-4xl mx-auto bg-white p-8 my-8 shadow-lg print:shadow-none print:my-0">
        
        <!-- Botão de Ação (não sai na impressão) -->
        <div class="no-print mb-6 flex justify-between items-center border-b pb-4">
            <span class="text-gray-600 text-sm">Este é um modo de visualização para impressão.</span>
            <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-blue-700 transition-all">
                Imprimir Agora
            </button>
        </div>

        <!-- Cabeçalho do Relatório -->
        <div class="flex justify-between items-start mb-8 border-b-2 border-gray-800 pb-4">
            <div>
                <h1 class="text-2xl font-black text-gray-900 uppercase">Relatório de Itens Avulsos</h1>
                <p class="text-gray-600">Pendentes de Cadastro Formal</p>
            </div>
            <div class="text-right">
                <p class="font-bold"><?= Html::encode($lojaNome) ?></p>
                <p class="text-sm text-gray-500">Gerado em: <?= date('d/m/Y H:i') ?></p>
            </div>
        </div>

        <!-- Tabela -->
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-800 text-white">
                    <th class="px-4 py-2 text-left text-sm uppercase">Descrição Manual</th>
                    <th class="px-4 py-2 text-center text-sm uppercase">Vendas</th>
                    <th class="px-4 py-2 text-center text-sm uppercase">Qtd Total</th>
                    <th class="px-4 py-2 text-right text-sm uppercase">Receita</th>
                    <th class="px-4 py-2 text-center text-sm uppercase">Última Venda</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-300">
                <?php if (empty($dados)): ?>
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 italic">
                            Não há itens avulsos pendentes de cadastro.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $totalReceita = 0; 
                    $totalVendas = 0;
                    ?>
                    <?php foreach ($dados as $item): ?>
                        <?php 
                        $totalReceita += $item['total_receita'];
                        $totalVendas += $item['total_vendas'];
                        ?>
                        <tr class="even:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-semibold text-gray-800"><?= Html::encode($item['nome_item_manual']) ?></td>
                            <td class="px-4 py-3 text-center text-sm"><?= $item['total_vendas'] ?></td>
                            <td class="px-4 py-3 text-center text-sm"><?= number_format($item['total_quantidade'], 2, ',', '.') ?></td>
                            <td class="px-4 py-3 text-right text-sm font-bold">R$ <?= number_format($item['total_receita'], 2, ',', '.') ?></td>
                            <td class="px-4 py-3 text-center text-xs text-gray-600"><?= date('d/m/Y', strtotime($item['ultima_venda'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($dados)): ?>
            <tfoot>
                <tr class="bg-gray-100 font-bold border-t-2 border-gray-800">
                    <td class="px-4 py-3 text-right" colspan="1">TOTAIS:</td>
                    <td class="px-4 py-3 text-center"><?= $totalVendas ?></td>
                    <td class="px-4 py-3"></td>
                    <td class="px-4 py-3 text-right text-blue-800">R$ <?= number_format($totalReceita, 2, ',', '.') ?></td>
                    <td></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>

        <!-- Notas de Rodapé -->
        <div class="mt-12 text-[10px] text-gray-500 border-t pt-4 italic">
            <p>Este documento é para uso interno e auxilia na gestão de inventário e padronização de produtos.</p>
            <p>Os itens aqui listados foram vendidos sem código de barras ou cadastro prévio. Recomenda-se o cadastro imediato dos itens com maior volume de venda.</p>
        </div>

    </div>

    <script>
        // Auto-print se não houver parâmetro no-print na URL
        window.onload = function() {
            if (!window.location.search.includes('noprint')) {
                // Pequeno delay para renderizar CSS antes da caixa de impressão
                setTimeout(function() {
                    window.print();
                }, 500);
            }
        };
    </script>
</body>
</html>
