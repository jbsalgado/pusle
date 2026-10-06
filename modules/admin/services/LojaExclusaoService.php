<?php

namespace app\modules\admin\services;

use Yii;
use app\models\Usuario;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;

/**
 * LojaExclusaoService — Serviço de Exclusão Completa e Expurgo Multitenant.
 *
 * Remove atomicamente todos os registros, dependências relacionais e mídias
 * associadas a uma loja (tenant), garantindo integridade referencial e
 * isolamento transacional via SAVEPOINTs do PostgreSQL.
 */
class LojaExclusaoService
{
    /**
     * Exclui uma loja por completo do ecossistema PULSE.
     *
     * @param string $lojaId UUID da loja em prest_usuarios
     * @return array ['success' => bool, 'message' => string, 'registros_removidos' => int]
     * @throws \Exception
     */
    public static function excluirLojaCompleta(string $lojaId): array
    {
        $loja = Usuario::findOne(['id' => $lojaId, 'eh_dono_loja' => true]);
        if (!$loja) {
            throw new NotFoundHttpException('Loja não encontrada.');
        }

        // Trava de segurança absoluta: Super Admin nunca pode ser excluído por este fluxo
        if ($loja->is_admin) {
            throw new ForbiddenHttpException('Operação não permitida: Esta conta possui privilégios de Super Administrador.');
        }

        $nomeLoja = $loja->nome;
        $db = Yii::$app->db;
        $totalRemovidos = 0;

        $transaction = $db->beginTransaction();
        try {
            // 1. Identificar IDs de entidades filhas para deleções em cascata profunda
            $vendaIds = static::queryColumnIfExists('prest_vendas', 'id', 'usuario_id', $lojaId);
            $orcamentoIds = static::queryColumnIfExists('prest_orcamentos', 'id', 'usuario_id', $lojaId);
            $produtoIds = static::queryColumnIfExists('prest_produtos', 'id', 'usuario_id', $lojaId);
            $compraIds = static::queryColumnIfExists('prest_compras', 'id', 'usuario_id', $lojaId);
            
            // Sub-usuários criados como colaboradores desta loja
            $colabUserIds = static::queryColumnIfExists('prest_colaboradores', 'prest_usuario_login_id', 'usuario_id', $lojaId);
            $colabUserIds = array_values(array_filter($colabUserIds)); // remove nulls

            // ─────────────────────────────────────────────────────────────────
            // 2. FILHOS DE ITENS, VENDAS, ESTOQUE E MOVIMENTAÇÕES
            // ─────────────────────────────────────────────────────────────────
            if (!empty($vendaIds)) {
                $totalRemovidos += static::deleteWhereIn('prest_venda_itens', 'venda_id', $vendaIds);
                $totalRemovidos += static::deleteWhereIn('prest_parcelas', 'venda_id', $vendaIds);
                $totalRemovidos += static::deleteWhereIn('prest_comissoes', 'venda_id', $vendaIds);
                $totalRemovidos += static::deleteWhereIn('prest_cupons_fiscais', 'venda_id', $vendaIds);
                $totalRemovidos += static::deleteWhereIn('prest_notas_fiscais', 'venda_id', $vendaIds);
                $totalRemovidos += static::deleteWhereIn('prest_caixa_movimentacoes', 'venda_id', $vendaIds);
                $totalRemovidos += static::deleteWhereIn('prest_gateway_transacoes', 'venda_id', $vendaIds);
                $totalRemovidos += static::deleteWhereIn('saas_financial_logs', 'order_id', $vendaIds);
            }

            if (!empty($orcamentoIds)) {
                $totalRemovidos += static::deleteWhereIn('prest_orcamento_itens', 'orcamento_id', $orcamentoIds);
            }

            if (!empty($compraIds)) {
                $totalRemovidos += static::deleteWhereIn('prest_itens_compra', 'compra_id', $compraIds);
            }

            if (!empty($produtoIds)) {
                $totalRemovidos += static::deleteWhereIn('prest_venda_itens', 'produto_id', $produtoIds);
                $totalRemovidos += static::deleteWhereIn('prest_orcamento_itens', 'produto_id', $produtoIds);
                $totalRemovidos += static::deleteWhereIn('prest_itens_compra', 'produto_id', $produtoIds);
                $totalRemovidos += static::deleteWhereIn('prest_marketplace_pedido_item', 'produto_id', $produtoIds);
                $totalRemovidos += static::deleteWhereIn('prest_produto_kit_itens', 'kit_id', $produtoIds);
                $totalRemovidos += static::deleteWhereIn('prest_produto_kit_itens', 'produto_id', $produtoIds);
                $totalRemovidos += static::deleteWhereIn('prest_produto_variantes', 'produto_id', $produtoIds);
                $totalRemovidos += static::deleteWhereIn('prest_produto_fotos', 'produto_id', $produtoIds);
                $totalRemovidos += static::deleteWhereIn('prest_marketplace_produto', 'produto_id', $produtoIds);
                $totalRemovidos += static::deleteWhereIn('prest_dados_financeiros', 'produto_id', $produtoIds);
                $totalRemovidos += static::deleteWhereIn('prest_estoque_movimentacoes', 'produto_id', $produtoIds);
                $totalRemovidos += static::deleteWhereIn('prest_produto_cards', 'produto_id', $produtoIds);
                $totalRemovidos += static::deleteWhereIn('prest_produto_videos', 'produto_id', $produtoIds);
            }

            $totalRemovidos += static::deleteByTenant('prest_estoque_movimentacoes', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_marketplace_pedido', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_comandas', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_orcamentos', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_vendas', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('orcamentos', 'usuario_id', $lojaId);

            // ─────────────────────────────────────────────────────────────────
            // 3. MÓDULO DE COBRANÇAS, CARTEIRAS, HISTÓRICOS E ROTAS
            // ─────────────────────────────────────────────────────────────────
            $totalRemovidos += static::deleteByTenant('prest_historico_cobranca', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_carteira_cobranca', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_rotas_cobranca', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_cobranca_historico', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_cobranca_template', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_cobranca_configuracao', 'usuario_id', $lojaId);

            // ─────────────────────────────────────────────────────────────────
            // 4. PRODUTOS, VÍDEOS, CARDS E CATEGORIAS
            // ─────────────────────────────────────────────────────────────────
            $totalRemovidos += static::deleteByTenant('prest_produto_cards', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_produto_videos', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_produtos', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_categorias', 'usuario_id', $lojaId);

            // ─────────────────────────────────────────────────────────────────
            // 5. CLIENTES, FORNECEDORES E COMPRAS
            // ─────────────────────────────────────────────────────────────────
            $totalRemovidos += static::deleteByTenant('prest_cliente_inbox', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_clientes', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_compras', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_fornecedores', 'usuario_id', $lojaId);

            // ─────────────────────────────────────────────────────────────────
            // 6. FINANCEIRO, CAIXA, FORMAS DE PAGAMENTO E DISPOSITIVOS
            // ─────────────────────────────────────────────────────────────────
            $totalRemovidos += static::deleteByTenant('prest_caixa', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_contas_pagar', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_dispositivos_pagamento', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_formas_pagamento', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_regras_parcelamento', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_regioes', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_taxas_entrega', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_tipos_despesa', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_periodos_cobranca', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_financeiro_mensal', 'usuario_id', $lojaId);

            // ─────────────────────────────────────────────────────────────────
            // 7. EQUIPE, SETORES E COLABORADORES
            // ─────────────────────────────────────────────────────────────────
            $totalRemovidos += static::deleteByTenant('prest_canal_setor_colaboradores', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_canal_setores', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_comissao_config', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_vendedores', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_colaboradores', 'usuario_id', $lojaId);

            // Exclui contas de acesso criadas exclusivamente para colaboradores da loja
            if (!empty($colabUserIds)) {
                $totalRemovidos += $db->createCommand()
                    ->delete('prest_usuarios', [
                        'and',
                        ['in', 'id', $colabUserIds],
                        ['eh_dono_loja' => false],
                        ['is_admin' => false],
                    ])
                    ->execute();
            }

            // ─────────────────────────────────────────────────────────────────
            // 8. INTEGRAÇÕES, REDES SOCIAIS, WHATSAPP E MARKETPLACES
            // ─────────────────────────────────────────────────────────────────
            $totalRemovidos += static::deleteByTenant('prest_bridge_whatsapp_mensagens', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_bridge_whatsapp_lojas', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('pulse_whatsapp_templates', 'empresa_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('pulse_whatsapp_config', 'empresa_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_marketplace_categoria_map', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_marketplace_config', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_marketplace_sync_log', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_social_posts', 'tenant_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_social_accounts', 'tenant_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_gateway_transacoes', 'tenant_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('saas_financial_logs', 'tenant_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('asaas_cobrancas', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('asaas_clientes', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('mercadopago_preferencias', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_disparos_massa', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_encartes', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_trilhas_sonoras', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_mesas', 'usuario_id', $lojaId);

            // ─────────────────────────────────────────────────────────────────
            // 9. CONFIGURAÇÕES E METADADOS DA LOJA
            // ─────────────────────────────────────────────────────────────────
            $totalRemovidos += static::deleteByTenant('loja_configuracao', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_loja_permissoes', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_saas_loja_config', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_configuracoes', 'usuario_id', $lojaId);

            // ─────────────────────────────────────────────────────────────────
            // 10. ASSINATURAS, PAGAMENTOS E FATURAS SAAS
            // ─────────────────────────────────────────────────────────────────
            $totalRemovidos += static::deleteByTenant('sis_pagamentos', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('sis_assinaturas', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('sis_usuario_modulos', 'usuario_id', $lojaId);
            $totalRemovidos += static::deleteByTenant('prest_saas_faturas', 'usuario_id', $lojaId);

            // ─────────────────────────────────────────────────────────────────
            // 11. EXCLUSÃO DO REGISTRO PRINCIPAL DA LOJA EM PREST_USUARIOS
            // ─────────────────────────────────────────────────────────────────
            $removidosLoja = $db->createCommand()
                ->delete('prest_usuarios', ['id' => $lojaId])
                ->execute();
            $totalRemovidos += $removidosLoja;

            $transaction->commit();

            // 12. Limpeza de arquivos físicos (uploads e logo)
            static::removerArquivosFisicos($loja);

            Yii::info("AUDITORIA: Loja \"{$nomeLoja}\" (ID: {$lojaId}) EXCLUÍDA POR COMPLETO pelo Admin. Total de registros expurgados: {$totalRemovidos}.", 'admin_audit');

            return [
                'success' => true,
                'message' => "Loja \"{$nomeLoja}\" e todos os seus registros associados foram excluídos com sucesso!",
                'registros_removidos' => $totalRemovidos,
            ];
        } catch (\Throwable $t) {
            $transaction->rollBack();
            Yii::error("LojaExclusaoService: Falha ao excluir loja {$lojaId}: " . $t->getMessage() . "\n" . $t->getTraceAsString(), __METHOD__);
            throw $t;
        }
    }

    /**
     * Remove registros por tenant_id/usuario_id verificando se a tabela existe
     * e protegendo o bloco com SAVEPOINT do PostgreSQL para isolamento de erros.
     */
    private static function deleteByTenant(string $tableName, string $column, string $tenantId): int
    {
        if (!static::tableExists($tableName) || !static::columnTypeCompatible($tableName, $column)) {
            return 0;
        }

        $db = Yii::$app->db;
        $sp = 'sp_' . md5($tableName . $column . microtime(true) . rand(1, 1000));
        
        try {
            $db->createCommand("SAVEPOINT {$sp}")->execute();
            $deleted = (int)$db->createCommand()
                ->delete($tableName, [$column => $tenantId])
                ->execute();
            $db->createCommand("RELEASE SAVEPOINT {$sp}")->execute();
            return $deleted;
        } catch (\Throwable $t) {
            try {
                $db->createCommand("ROLLBACK TO SAVEPOINT {$sp}")->execute();
            } catch (\Throwable $eRoll) {
                // ignora erro de rollback de savepoint
            }
            Yii::warning("LojaExclusaoService: Ignorando erro ao limpar {$tableName}.{$column}: " . $t->getMessage(), __METHOD__);
            return 0;
        }
    }

    /**
     * Remove registros por WHERE column IN (...) verificando se a tabela existe
     * e protegendo o bloco com SAVEPOINT do PostgreSQL.
     */
    private static function deleteWhereIn(string $tableName, string $column, array $values): int
    {
        if (empty($values) || !static::tableExists($tableName) || !static::columnTypeCompatible($tableName, $column)) {
            return 0;
        }

        $db = Yii::$app->db;
        $sp = 'sp_' . md5($tableName . $column . microtime(true) . rand(1001, 2000));

        try {
            $db->createCommand("SAVEPOINT {$sp}")->execute();
            $deleted = (int)$db->createCommand()
                ->delete($tableName, ['in', $column, $values])
                ->execute();
            $db->createCommand("RELEASE SAVEPOINT {$sp}")->execute();
            return $deleted;
        } catch (\Throwable $t) {
            try {
                $db->createCommand("ROLLBACK TO SAVEPOINT {$sp}")->execute();
            } catch (\Throwable $eRoll) {
                // ignora
            }
            Yii::warning("LojaExclusaoService: Ignorando erro ao limpar {$tableName} por IN: " . $t->getMessage(), __METHOD__);
            return 0;
        }
    }

    /**
     * Consulta lista de IDs de uma coluna se a tabela existir
     */
    private static function queryColumnIfExists(string $tableName, string $selectCol, string $filterCol, string $filterVal): array
    {
        if (!static::tableExists($tableName) || !static::columnTypeCompatible($tableName, $filterCol)) {
            return [];
        }

        try {
            return (new \yii\db\Query())
                ->select($selectCol)
                ->from($tableName)
                ->where([$filterCol => $filterVal])
                ->column();
        } catch (\Throwable $t) {
            return [];
        }
    }

    /**
     * Verifica rapidamente se uma tabela existe no schema público do PostgreSQL
     */
    private static function tableExists(string $tableName): bool
    {
        static $cachedTables = null;
        if ($cachedTables === null) {
            $cachedTables = Yii::$app->db->createCommand("
                SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'
            ")->queryColumn();
            $cachedTables = array_flip($cachedTables);
        }

        return isset($cachedTables[$tableName]);
    }

    /**
     * Garante que a coluna aceita strings/UUIDs e não tipos incompatíveis como integer ou bigint
     */
    private static function columnTypeCompatible(string $tableName, string $column): bool
    {
        static $cachedColumns = null;
        if ($cachedColumns === null) {
            $rows = Yii::$app->db->createCommand("
                SELECT table_name, column_name, data_type, udt_name
                FROM information_schema.columns
                WHERE table_schema = 'public'
            ")->queryAll();
            $cachedColumns = [];
            foreach ($rows as $r) {
                $cachedColumns[$r['table_name'] . '.' . $r['column_name']] = strtolower($r['data_type'] . ' ' . $r['udt_name']);
            }
        }

        $key = $tableName . '.' . $column;
        if (!isset($cachedColumns[$key])) {
            return false;
        }

        $typeInfo = $cachedColumns[$key];
        // Bloqueia tipos inteiros, booleanos ou datas para evitar "invalid input syntax for type integer: UUID"
        if (preg_match('/(int|bigint|smallint|numeric|decimal|boolean|timestamp|date|time)/', $typeInfo)) {
            return false;
        }

        return true;
    }

    /**
     * Remove arquivos físicos de mídias e logos vinculados à loja
     */
    private static function removerArquivosFisicos(Usuario $loja): void
    {
        try {
            $baseWeb = Yii::getAlias('@app/web');

            // Logo do usuário
            if (!empty($loja->logo_path)) {
                $logoFile = $baseWeb . '/' . ltrim($loja->logo_path, '/');
                if (file_exists($logoFile) && is_file($logoFile)) {
                    @unlink($logoFile);
                }
            }

            // Diretório exclusivo de uploads da loja se houver
            $lojaDir = $baseWeb . '/uploads/lojas/' . $loja->id;
            if (is_dir($lojaDir)) {
                static::deleteDirectoryRecursively($lojaDir);
            }
        } catch (\Throwable $t) {
            Yii::warning("LojaExclusaoService: Erro ao remover arquivos da loja {$loja->id}: " . $t->getMessage(), __METHOD__);
        }
    }

    /**
     * Deleta um diretório e seu conteúdo recursivamente
     */
    private static function deleteDirectoryRecursively(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $items = scandir($dir);
        if ($items === false) {
            return false;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                static::deleteDirectoryRecursively($path);
            } else {
                @unlink($path);
            }
        }

        return @rmdir($dir);
    }
}
