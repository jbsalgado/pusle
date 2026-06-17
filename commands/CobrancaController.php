<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use app\modules\cobranca\components\CobrancaProcessor;

/**
 * Comandos de automação de cobranças
 * 
 * Uso:
 * php yii cobranca/processar - Processa todas as cobranças do dia
 * php yii cobranca/teste - Testa o sistema de cobranças
 */
class CobrancaController extends Controller
{
    /**
     * Processa todas as cobranças do dia
     * 
     * Este comando deve ser executado diariamente via cron job
     * Exemplo: 0 9 * * * cd /srv/http/pulse && php yii cobranca/processar
     */
    public function actionProcessar()
    {
        $this->stdout("=== Processamento de Cobranças ===\n", Console::FG_CYAN);
        $this->stdout("Iniciando em: " . date('Y-m-d H:i:s') . "\n\n");

        $processor = new CobrancaProcessor();

        try {
            $stats = $processor->processarCobrancasDoDia();

            $this->stdout("=== Resultado ===\n", Console::FG_GREEN);
            $this->stdout("Total processadas: {$stats['total']}\n");
            $this->stdout("Enviadas com sucesso: {$stats['enviadas']}\n", Console::FG_GREEN);
            $this->stdout("Falhas: {$stats['falhas']}\n", $stats['falhas'] > 0 ? Console::FG_RED : Console::FG_GREEN);
            $this->stdout("\nPor tipo:\n");
            $this->stdout("  - 3 dias antes: {$stats['por_tipo']['ANTES']}\n");
            $this->stdout("  - Dia vencimento: {$stats['por_tipo']['DIA']}\n");
            $this->stdout("  - Após vencimento: {$stats['por_tipo']['APOS']}\n");

            return ExitCode::OK;
        } catch (\Exception $e) {
            $this->stderr("ERRO: " . $e->getMessage() . "\n", Console::FG_RED);
            $this->stderr($e->getTraceAsString() . "\n");

            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Testa o sistema de cobranças
     * 
     * Verifica configurações e conectividade sem enviar mensagens reais
     */
    public function actionTeste()
    {
        $this->stdout("=== Teste do Sistema de Cobranças ===\n\n", Console::FG_CYAN);

        // Verificar módulo
        $this->stdout("1. Verificando módulo cobranca...\n");
        if (Yii::$app->hasModule('cobranca')) {
            $this->stdout("   ✓ Módulo carregado\n", Console::FG_GREEN);
        } else {
            $this->stdout("   ✗ Módulo não encontrado\n", Console::FG_RED);
            return ExitCode::CONFIG;
        }

        // Verificar tabelas
        $this->stdout("\n2. Verificando tabelas do banco...\n");
        $tabelas = ['prest_cobranca_configuracao', 'prest_cobranca_template', 'prest_cobranca_historico'];

        foreach ($tabelas as $tabela) {
            try {
                Yii::$app->db->createCommand("SELECT 1 FROM {$tabela} LIMIT 1")->execute();
                $this->stdout("   ✓ {$tabela}\n", Console::FG_GREEN);
            } catch (\Exception $e) {
                $this->stdout("   ✗ {$tabela} - {$e->getMessage()}\n", Console::FG_RED);
            }
        }

        // Verificar configurações
        $this->stdout("\n3. Verificando configurações de usuários...\n");
        $configs = \app\modules\cobranca\models\CobrancaConfiguracao::find()
            ->where(['ativo' => true])
            ->all();

        if (empty($configs)) {
            $this->stdout("   ⚠ Nenhuma configuração ativa encontrada\n", Console::FG_YELLOW);
        } else {
            $this->stdout("   ✓ {count($configs)} configuração(ões) ativa(s)\n", Console::FG_GREEN);

            foreach ($configs as $config) {
                $this->stdout("\n   Usuário: {$config->usuario->nome}\n");
                $this->stdout("   - Provider: {$config->whatsapp_provider}\n");
                $this->stdout("   - Credenciais: " . ($config->hasCredentials() ? "✓" : "✗") . "\n");
                $this->stdout("   - Pronto: " . ($config->isReady() ? "✓" : "✗") . "\n");
            }
        }

        // Verificar templates
        $this->stdout("\n4. Verificando templates...\n");
        $templates = \app\modules\cobranca\models\CobrancaTemplate::find()
            ->where(['ativo' => true])
            ->count();

        $this->stdout("   ✓ {$templates} template(s) ativo(s)\n", Console::FG_GREEN);

        $this->stdout("\n=== Teste Concluído ===\n", Console::FG_CYAN);

        return ExitCode::OK;
    }
}
