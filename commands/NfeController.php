<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use app\components\nfe\NFeBuilder;
use app\components\nfe\NFeService;
use app\modules\vendas\models\Venda;

/**
 * Comandos para teste de NFe/NFCe
 */
class NfeController extends Controller
{
    /**
     * Testa configuração e status do serviço SEFAZ
     */
    public function actionTestStatus()
    {
        $this->stdout("🧪 Teste de Status SEFAZ\n", \yii\helpers\Console::BOLD);
        $this->stdout(str_repeat("=", 70) . "\n");
        
        try {
            $service = new NFeService();
            $status = $service->consultarStatus('65');
            
            if ($status['success']) {
                $this->stdout("✅ SEFAZ em operação\n", \yii\helpers\Console::FG_GREEN);
                $this->stdout("   Mensagem: " . $status['mensagem'] . "\n");
                $this->stdout("   Código: " . $status['codigo'] . "\n");
            } else {
                $this->stdout("⚠️  SEFAZ indisponível\n", \yii\helpers\Console::FG_YELLOW);
                $this->stdout("   Mensagem: " . $status['mensagem'] . "\n");
            }
            
        } catch (\Exception $e) {
            $this->stdout("❌ Erro: " . $e->getMessage() . "\n", \yii\helpers\Console::FG_RED);
        }
    }
    
    /**
     * Gera XML de teste para uma venda
     * 
     * @param string $vendaId ID da venda
     * @param string $modelo '55' ou '65'
     */
    public function actionGerarXml($vendaId, $modelo = '65')
    {
        $this->stdout("🧪 Teste de Geração de XML\n", \yii\helpers\Console::BOLD);
        $this->stdout(str_repeat("=", 70) . "\n\n");
        
        // Buscar venda
        $venda = Venda::find()
            ->with(['itens.produto', 'cliente'])
            ->where(['id' => $vendaId])
            ->one();
        
        if (!$venda) {
            $this->stdout("❌ Venda não encontrada: {$vendaId}\n", \yii\helpers\Console::FG_RED);
            return self::EXIT_CODE_ERROR;
        }
        
        $this->stdout("✅ Venda encontrada\n");
        $this->stdout("   ID: " . $venda->id . "\n");
        $this->stdout("   Cliente: " . ($venda->cliente->nome ?? 'N/A') . "\n");
        $this->stdout("   Valor: R$ " . number_format($venda->valor_total, 2, ',', '.') . "\n");
        $this->stdout("   Itens: " . count($venda->itens) . "\n\n");
        
        try {
            $this->stdout("Gerando XML...\n");
            $xml = NFeBuilder::buildFromVenda($venda, $modelo);
            
            $this->stdout("✅ XML gerado com sucesso!\n", \yii\helpers\Console::FG_GREEN);
            $this->stdout("   Tamanho: " . strlen($xml) . " bytes\n");
            
            // Salvar XML
            $xmlPath = Yii::getAlias('@runtime') . '/nfe_teste_' . $venda->id . '.xml';
            file_put_contents($xmlPath, $xml);
            $this->stdout("   Salvo em: " . $xmlPath . "\n\n");
            
            // Mostrar preview
            $this->stdout("Preview (primeiras 500 caracteres):\n");
            $this->stdout(str_repeat("-", 70) . "\n");
            $this->stdout(substr($xml, 0, 500) . "...\n");
            $this->stdout(str_repeat("-", 70) . "\n");
            
        } catch (\Exception $e) {
            $this->stdout("❌ Erro ao gerar XML:\n", \yii\helpers\Console::FG_RED);
            $this->stdout("   " . $e->getMessage() . "\n");
            $this->stdout("   Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n");
            return self::EXIT_CODE_ERROR;
        }
        
        return self::EXIT_CODE_NORMAL;
    }
    
    /**
     * Lista vendas disponíveis para teste
     */
    public function actionListarVendas()
    {
        $this->stdout("📋 Vendas Disponíveis para Teste\n", \yii\helpers\Console::BOLD);
        $this->stdout(str_repeat("=", 70) . "\n\n");
        
        $vendas = Venda::find()
            ->with(['cliente'])
            ->where(['IS NOT', 'cliente_id', null])
            ->orderBy(['data_criacao' => SORT_DESC])
            ->limit(10)
            ->all();
        
        if (empty($vendas)) {
            $this->stdout("⚠️  Nenhuma venda encontrada\n", \yii\helpers\Console::FG_YELLOW);
            return self::EXIT_CODE_NORMAL;
        }
        
        foreach ($vendas as $venda) {
            $this->stdout("ID: " . $venda->id . "\n");
            $this->stdout("   Cliente: " . ($venda->cliente->nome ?? 'N/A') . "\n");
            $this->stdout("   Valor: R$ " . number_format($venda->valor_total, 2, ',', '.') . "\n");
            $this->stdout("   Data: " . Yii::$app->formatter->asDatetime($venda->data_criacao) . "\n");
            $this->stdout("\n");
        }
        
        $this->stdout("Para gerar XML, use:\n");
        $this->stdout("php yii nfe/gerar-xml <ID_VENDA>\n", \yii\helpers\Console::FG_CYAN);
    }

    /**
     * Reprocessa notas de sistemas externos que foram autorizadas mas ainda
     * não foram enviadas ao Mercado Livre.
     *
     * Uso: php yii nfe/reprocessar-pendentes-ml [--dias=7]
     *
     * @param int $dias Janela de dias a considerar (default: 7)
     */
    public function actionReprocessarPendentesMl(int $dias = 7): int
    {
        $this->stdout("🔄 Reprocessador de NF-e Pendentes de Envio ao ML\n", \yii\helpers\Console::BOLD);
        $this->stdout(str_repeat("=", 70) . "\n\n");

        $desde = date('Y-m-d H:i:s', strtotime("-{$dias} days"));

        $notas = \app\modules\vendas\models\NotaFiscal::find()
            ->where([
                'status_sefaz' => \app\modules\vendas\models\NotaFiscal::STATUS_AUTORIZADA,
                'enviada_ml'   => false,
            ])
            ->andWhere(['IS NOT', 'chave_acesso', null])
            ->andWhere(['>=', 'data_autorizacao', $desde])
            ->orderBy(['data_autorizacao' => SORT_ASC])
            ->all();

        if (empty($notas)) {
            $this->stdout("✅ Nenhuma nota pendente de envio ao ML nos últimos {$dias} dias.\n",
                \yii\helpers\Console::FG_GREEN);
            return self::EXIT_CODE_NORMAL;
        }

        $this->stdout("📋 Encontradas " . count($notas) . " nota(s) pendentes:\n\n");

        $enfileiradas = 0;
        $erros = 0;

        foreach ($notas as $nota) {
            // Verificar se tem pedido ML vinculado
            $mpPedido = \app\modules\marketplace\models\MarketplacePedido::findOne([
                'venda_id' => $nota->venda_id,
            ]) ?? \app\modules\marketplace\models\MarketplacePedido::findOne([
                'marketplace_pedido_id' => $nota->marketplace_pedido_id,
                'marketplace'           => 'MERCADO_LIVRE',
            ]);

            if (!$mpPedido || $mpPedido->marketplace !== 'MERCADO_LIVRE') {
                $this->stdout("  ⏭️  Nota {$nota->id} — sem pedido ML vinculado. Pulando.\n",
                    \yii\helpers\Console::FG_YELLOW);
                continue;
            }

            try {
                if (Yii::$app->has('queue')) {
                    Yii::$app->queue->push(new \app\jobs\EnviarNFeMercadoLivreJob([
                        'notaFiscalId' => $nota->id,
                    ]));
                    $this->stdout("  ✅ Nota {$nota->id} enfileirada (Chave: {$nota->chave_acesso})\n",
                        \yii\helpers\Console::FG_GREEN);
                    $enfileiradas++;
                } else {
                    $this->stdout("  ⚠️  Fila não disponível (sem componente queue)\n",
                        \yii\helpers\Console::FG_YELLOW);
                    break;
                }
            } catch (\Throwable $e) {
                $this->stdout("  ❌ Erro ao enfileirar nota {$nota->id}: " . $e->getMessage() . "\n",
                    \yii\helpers\Console::FG_RED);
                $erros++;
            }
        }

        $this->stdout("\n" . str_repeat("=", 70) . "\n");
        $this->stdout("✅ Enfileiradas: {$enfileiradas}  |  ❌ Erros: {$erros}\n");
        $this->stdout("Use 'php yii queue/run' para processar imediatamente.\n",
            \yii\helpers\Console::FG_CYAN);

        return $erros > 0 ? self::EXIT_CODE_ERROR : self::EXIT_CODE_NORMAL;
    }

    /**
     * Exibe um relatório consolidado de notas fiscais por fonte de emissão e status.
     *
     * Uso: php yii nfe/relatorio-fontes [--usuario_id=xxx]
     */
    public function actionRelatorioFontes(?string $usuario_id = null): int
    {
        $this->stdout("📊 Relatório de NF-e por Fonte de Emissão\n", \yii\helpers\Console::BOLD);
        $this->stdout(str_repeat("=", 70) . "\n\n");

        $query = Yii::$app->db->createCommand("
            SELECT
                COALESCE(fonte_emissao, 'PULSE_ERP') AS fonte,
                status_sefaz,
                COUNT(*) AS total,
                SUM(CASE WHEN enviada_ml THEN 1 ELSE 0 END) AS enviadas_ml,
                SUM(CASE WHEN NOT enviada_ml AND status_sefaz = 'AUTORIZADA' THEN 1 ELSE 0 END) AS pendentes_ml
            FROM prest_notas_fiscais
            " . ($usuario_id ? "WHERE usuario_id = :uid" : "") . "
            GROUP BY fonte_emissao, status_sefaz
            ORDER BY fonte_emissao, status_sefaz
        ", $usuario_id ? [':uid' => $usuario_id] : []);

        $rows = $query->queryAll();

        if (empty($rows)) {
            $this->stdout("Nenhuma nota fiscal registrada.\n", \yii\helpers\Console::FG_YELLOW);
            return self::EXIT_CODE_NORMAL;
        }

        $this->stdout(sprintf("%-20s %-15s %8s %12s %13s\n",
            'FONTE', 'STATUS', 'TOTAL', 'ENVIADAS_ML', 'PENDENTES_ML'));
        $this->stdout(str_repeat("-", 70) . "\n");

        foreach ($rows as $row) {
            $this->stdout(sprintf("%-20s %-15s %8d %12d %13d\n",
                $row['fonte'],
                $row['status_sefaz'],
                $row['total'],
                $row['enviadas_ml'],
                $row['pendentes_ml']
            ));
        }

        $this->stdout(str_repeat("=", 70) . "\n");
        $this->stdout("\nPara reprocessar pendentes: php yii nfe/reprocessar-pendentes-ml\n",
            \yii\helpers\Console::FG_CYAN);

        return self::EXIT_CODE_NORMAL;
    }
}

