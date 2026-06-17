<?php

use yii\db\Migration;

/**
 * Class m260611_000001_fix_compras_contas_pagar
 */
class m260611_000001_fix_compras_contas_pagar extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Obter todos os usuario_id que possuem contas a pagar vinculadas a compras e sem tipo_despesa_id
        $usuarios = (new \yii\db\Query())
            ->select(['usuario_id'])
            ->from('prest_contas_pagar')
            ->where(['not', ['compra_id' => null]])
            ->andWhere(['tipo_despesa_id' => null])
            ->distinct()
            ->all();

        foreach ($usuarios as $usr) {
            $usuarioId = $usr['usuario_id'];

            // 2. Verificar se este usuário já possui um tipo de despesa do grupo MERCADORIA
            $tipoDespesa = (new \yii\db\Query())
                ->select(['id'])
                ->from('prest_tipos_despesa')
                ->where(['usuario_id' => $usuarioId, 'grupo' => 'MERCADORIA', 'ativo' => true])
                ->one();

            if (!$tipoDespesa) {
                // Se não existir, gera um UUID e cria o tipo de despesa padrão
                $tipoId = Yii::$app->db->createCommand("SELECT gen_random_uuid()")->queryScalar();
                
                $this->insert('prest_tipos_despesa', [
                    'id' => $tipoId,
                    'usuario_id' => $usuarioId,
                    'nome' => 'Compra de Mercadoria',
                    'grupo' => 'MERCADORIA',
                    'descricao' => 'Gerado automaticamente para compras de mercadorias',
                    'ativo' => true,
                    'data_criacao' => new \yii\db\Expression('NOW()'),
                    'data_atualizacao' => new \yii\db\Expression('NOW()'),
                ]);
            } else {
                $tipoId = $tipoDespesa['id'];
            }

            // 3. Atualizar todas as contas a pagar deste usuário que são de compras e estão sem tipo_despesa_id
            if ($tipoId) {
                $this->update(
                    'prest_contas_pagar',
                    ['tipo_despesa_id' => $tipoId],
                    'usuario_id = :uid AND tipo_despesa_id IS NULL AND compra_id IS NOT NULL',
                    [':uid' => $usuarioId]
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        return true;
    }
}
