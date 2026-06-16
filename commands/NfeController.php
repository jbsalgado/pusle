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
}
