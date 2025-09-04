<?php

namespace app\modules\servicos\models;

use Yii;

/**
 * This is the model class for table "cadast_materiais".
 *
 * @property int $id
 * @property int $empresa_id
 * @property string $ref_material
 * @property string $descricao
 * @property string $unidade_medida
 * @property string $custo_medio
 *
 * @property CadastEmpresas $empresa
 * @property EstoqMovimentacoes[] $estoqMovimentacoes
 * @property ProdFichaTecnica[] $prodFichaTecnicas
 */
class CadastMateriais extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cadast_materiais';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['empresa_id', 'ref_material', 'descricao', 'unidade_medida'], 'required'],
            [['empresa_id'], 'default', 'value' => null],
            [['empresa_id'], 'integer'],
            [['custo_medio'], 'number'],
            [['ref_material'], 'string', 'max' => 50],
            [['descricao'], 'string', 'max' => 200],
            [['unidade_medida'], 'string', 'max' => 10],
            [['empresa_id', 'ref_material'], 'unique', 'targetAttribute' => ['empresa_id', 'ref_material']],
            [['empresa_id'], 'exist', 'skipOnError' => true, 'targetClass' => CadastEmpresas::className(), 'targetAttribute' => ['empresa_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'empresa_id' => 'Empresa ID',
            'ref_material' => 'Ref Material',
            'descricao' => 'Descricao',
            'unidade_medida' => 'Unidade Medida',
            'custo_medio' => 'Custo Medio',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEmpresa()
    {
        return $this->hasOne(CadastEmpresas::className(), ['id' => 'empresa_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEstoqMovimentacoes()
    {
        return $this->hasMany(EstoqMovimentacoes::className(), ['material_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProdFichaTecnicas()
    {
        return $this->hasMany(ProdFichaTecnica::className(), ['material_id' => 'id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\query\CadastMateriaisQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\servicos\query\CadastMateriaisQuery(get_called_class());
    }
}
