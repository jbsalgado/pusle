<?php

use yii\db\Migration;

/**
 * Class m260831_160000_add_segmento_to_prest_configuracoes
 */
class m260831_160000_add_segmento_to_prest_configuracoes extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableConfig = $this->db->schema->getTableSchema('prest_configuracoes');
        if ($tableConfig) {
            if (!isset($tableConfig->columns['segmento'])) {
                $this->addColumn('prest_configuracoes', 'segmento', $this->string(30)->defaultValue('geral'));
            }
            if (!isset($tableConfig->columns['modulo_food_service'])) {
                $this->addColumn('prest_configuracoes', 'modulo_food_service', $this->boolean()->defaultValue(false));
            }
        }

        $tableCat = $this->db->schema->getTableSchema('prest_categorias');
        if ($tableCat && !isset($tableCat->columns['destino_padrao'])) {
            $this->addColumn('prest_categorias', 'destino_padrao', $this->string(30)->null());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $tableConfig = $this->db->schema->getTableSchema('prest_configuracoes');
        if ($tableConfig) {
            if (isset($tableConfig->columns['segmento'])) {
                $this->dropColumn('prest_configuracoes', 'segmento');
            }
            if (isset($tableConfig->columns['modulo_food_service'])) {
                $this->dropColumn('prest_configuracoes', 'modulo_food_service');
            }
        }

        $tableCat = $this->db->schema->getTableSchema('prest_categorias');
        if ($tableCat && isset($tableCat->columns['destino_padrao'])) {
            $this->dropColumn('prest_categorias', 'destino_padrao');
        }
    }
}
