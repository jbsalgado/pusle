<?php

namespace app\modules\servicos\models;

use Yii;

/**
 * This is the model class for table "cadast_empresas".
 *
 * @property int $id
 * @property string $nome_fantasia
 * @property string $razao_social
 * @property string $cnpj
 * @property string $email_principal
 * @property string $senha
 * @property string $data_cadastro
 * @property bool $ativo
 *
 * @property CadastClientes[] $cadastClientes
 * @property CadastMateriais[] $cadastMateriais
 * @property CadastProdutos[] $cadastProdutos
 * @property CadastTerceiros[] $cadastTerceiros
 * @property CatalogoCategorias[] $catalogoCategorias
 * @property EstoqMovimentacoes[] $estoqMovimentacoes
 * @property FinanContasPagar[] $finanContasPagars
 * @property FinanContasReceber[] $finanContasRecebers
 * @property IndicaQualidadeDefeitos[] $indicaQualidadeDefeitos
 * @property ProdEtapasProducao[] $prodEtapasProducaos
 * @property ProdFichaTecnica[] $prodFichaTecnicas
 * @property ProdLotes[] $prodLotes
 * @property ProdOrdensProducao[] $prodOrdensProducaos
 * @property VendasPedidoItens[] $vendasPedidoItens
 * @property VendasPedidos[] $vendasPedidos
 */
class CadastEmpresas extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cadast_empresas';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nome_fantasia', 'email_principal', 'senha'], 'required'],
            [['data_cadastro'], 'safe'],
            [['ativo'], 'boolean'],
            [['nome_fantasia', 'razao_social'], 'string', 'max' => 150],
            [['cnpj'], 'string', 'max' => 18],
            [['email_principal'], 'string', 'max' => 100],
            [['senha'], 'string', 'max' => 255],
            [['cnpj'], 'unique'],
            [['email_principal'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nome_fantasia' => 'Nome Fantasia',
            'razao_social' => 'Razao Social',
            'cnpj' => 'Cnpj',
            'email_principal' => 'Email Principal',
            'senha' => 'Senha',
            'data_cadastro' => 'Data Cadastro',
            'ativo' => 'Ativo',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCadastClientes()
    {
        return $this->hasMany(CadastClientes::className(), ['empresa_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCadastMateriais()
    {
        return $this->hasMany(CadastMateriais::className(), ['empresa_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCadastProdutos()
    {
        return $this->hasMany(CadastProdutos::className(), ['empresa_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCadastTerceiros()
    {
        return $this->hasMany(CadastTerceiros::className(), ['empresa_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCatalogoCategorias()
    {
        return $this->hasMany(CatalogoCategorias::className(), ['empresa_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEstoqMovimentacoes()
    {
        return $this->hasMany(EstoqMovimentacoes::className(), ['empresa_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFinanContasPagars()
    {
        return $this->hasMany(FinanContasPagar::className(), ['empresa_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFinanContasRecebers()
    {
        return $this->hasMany(FinanContasReceber::className(), ['empresa_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndicaQualidadeDefeitos()
    {
        return $this->hasMany(IndicaQualidadeDefeitos::className(), ['empresa_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProdEtapasProducaos()
    {
        return $this->hasMany(ProdEtapasProducao::className(), ['empresa_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProdFichaTecnicas()
    {
        return $this->hasMany(ProdFichaTecnica::className(), ['empresa_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProdLotes()
    {
        return $this->hasMany(ProdLotes::className(), ['empresa_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProdOrdensProducaos()
    {
        return $this->hasMany(ProdOrdensProducao::className(), ['empresa_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVendasPedidoItens()
    {
        return $this->hasMany(VendasPedidoItens::className(), ['empresa_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVendasPedidos()
    {
        return $this->hasMany(VendasPedidos::className(), ['empresa_id' => 'id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\query\CadastEmpresasQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\servicos\query\CadastEmpresasQuery(get_called_class());
    }
}
