<?php

namespace app\modules\vendas\models;

use Yii;

/**
 * This is the model class for table "prest_usuarios".
 *
 * @property string $id Identificador único do usuário (UUID).
 * @property string $nome Nome completo do usuário.
 * @property string|null $email Email para login e contato, deve ser único.
 * @property string $hash_senha Senha do usuário armazenada de forma segura (hash).
 * @property string $data_criacao
 * @property string $data_atualizacao
 * @property string $cpf
 * @property string $telefone
 * @property string|null $auth_key
 *
 * @property SisModulos[] $modulos
 * @property PrestCarteiraCobranca[] $prestCarteiraCobrancas
 * @property PrestCategorias[] $prestCategorias
 * @property PrestClientes[] $prestClientes
 * @property PrestColaboradores[] $prestColaboradores
 * @property PrestComissoes[] $prestComissoes
 * @property PrestConfiguracoes $prestConfiguracoes
 * @property PrestEstoqueMovimentacoes[] $prestEstoqueMovimentacoes
 * @property PrestFormasPagamento[] $prestFormasPagamentos
 * @property PrestHistoricoCobranca[] $prestHistoricoCobrancas
 * @property PrestOrcamentos[] $prestOrcamentos
 * @property PrestParcelas[] $prestParcelas
 * @property PrestPeriodosCobranca[] $prestPeriodosCobrancas
 * @property PrestProdutos[] $prestProdutos
 * @property PrestRegioes[] $prestRegioes
 * @property PrestRegrasParcelamento[] $prestRegrasParcelamentos
 * @property PrestRotasCobranca[] $prestRotasCobrancas
 * @property PrestVendas[] $prestVendas
 * @property PrestVendedores[] $prestVendedores
 * @property SisAssinaturas[] $sisAssinaturas
 * @property SisPagamentos[] $sisPagamentos
 * @property SisUsuarioModulos[] $sisUsuarioModulos
 */
class Usuarios extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_usuarios';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['email', 'auth_key'], 'default', 'value' => null],
            [['id', 'nome', 'hash_senha', 'cpf', 'telefone'], 'required'],
            [['id'], 'string'],
            [['data_criacao', 'data_atualizacao'], 'safe'],
            [['nome', 'email'], 'string', 'max' => 100],
            [['hash_senha'], 'string', 'max' => 255],
            [['cpf'], 'string', 'max' => 20],
            [['telefone'], 'string', 'max' => 30],
            [['auth_key'], 'string', 'max' => 32],
            [['cpf'], 'unique'],
            [['id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nome' => 'Nome',
            'email' => 'Email',
            'hash_senha' => 'Hash Senha',
            'data_criacao' => 'Data Criacao',
            'data_atualizacao' => 'Data Atualizacao',
            'cpf' => 'Cpf',
            'telefone' => 'Telefone',
            'auth_key' => 'Auth Key',
        ];
    }

    /**
     * Gets query for [[Modulos]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\SisModulosQuery
     */
    public function getModulos()
    {
        return $this->hasMany(SisModulos::class, ['id' => 'modulo_id'])->viaTable('sis_usuario_modulos', ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[PrestCarteiraCobrancas]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestCarteiraCobrancaQuery
     */
    public function getPrestCarteiraCobrancas()
    {
        return $this->hasMany(PrestCarteiraCobranca::class, ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[PrestCategorias]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestCategoriasQuery
     */
    public function getPrestCategorias()
    {
        return $this->hasMany(PrestCategorias::class, ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[PrestClientes]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestClientesQuery
     */
    public function getPrestClientes()
    {
        return $this->hasMany(PrestClientes::class, ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[PrestColaboradores]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestColaboradoresQuery
     */
    public function getPrestColaboradores()
    {
        return $this->hasMany(PrestColaboradores::class, ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[PrestComissoes]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestComissoesQuery
     */
    public function getPrestComissoes()
    {
        return $this->hasMany(PrestComissoes::class, ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[PrestConfiguracoes]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestConfiguracoesQuery
     */
    public function getPrestConfiguracoes()
    {
        return $this->hasOne(PrestConfiguracoes::class, ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[PrestEstoqueMovimentacoes]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestEstoqueMovimentacoesQuery
     */
    public function getPrestEstoqueMovimentacoes()
    {
        return $this->hasMany(PrestEstoqueMovimentacoes::class, ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[PrestFormasPagamentos]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestFormasPagamentoQuery
     */
    public function getPrestFormasPagamentos()
    {
        return $this->hasMany(PrestFormasPagamento::class, ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[PrestHistoricoCobrancas]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestHistoricoCobrancaQuery
     */
    public function getPrestHistoricoCobrancas()
    {
        return $this->hasMany(PrestHistoricoCobranca::class, ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[PrestOrcamentos]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestOrcamentosQuery
     */
    public function getPrestOrcamentos()
    {
        return $this->hasMany(PrestOrcamentos::class, ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[PrestParcelas]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestParcelasQuery
     */
    public function getPrestParcelas()
    {
        return $this->hasMany(PrestParcelas::class, ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[PrestPeriodosCobrancas]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestPeriodosCobrancaQuery
     */
    public function getPrestPeriodosCobrancas()
    {
        return $this->hasMany(PrestPeriodosCobranca::class, ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[PrestProdutos]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestProdutosQuery
     */
    public function getPrestProdutos()
    {
        return $this->hasMany(PrestProdutos::class, ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[PrestRegioes]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestRegioesQuery
     */
    public function getPrestRegioes()
    {
        return $this->hasMany(PrestRegioes::class, ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[PrestRegrasParcelamentos]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestRegrasParcelamentoQuery
     */
    public function getPrestRegrasParcelamentos()
    {
        return $this->hasMany(PrestRegrasParcelamento::class, ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[PrestRotasCobrancas]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestRotasCobrancaQuery
     */
    public function getPrestRotasCobrancas()
    {
        return $this->hasMany(PrestRotasCobranca::class, ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[PrestVendas]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestVendasQuery
     */
    public function getPrestVendas()
    {
        return $this->hasMany(PrestVendas::class, ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[PrestVendedores]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestVendedoresQuery
     */
    public function getPrestVendedores()
    {
        return $this->hasMany(PrestVendedores::class, ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[SisAssinaturas]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\SisAssinaturasQuery
     */
    public function getSisAssinaturas()
    {
        return $this->hasMany(SisAssinaturas::class, ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[SisPagamentos]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\SisPagamentosQuery
     */
    public function getSisPagamentos()
    {
        return $this->hasMany(SisPagamentos::class, ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[SisUsuarioModulos]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\SisUsuarioModulosQuery
     */
    public function getSisUsuarioModulos()
    {
        return $this->hasMany(SisUsuarioModulos::class, ['usuario_id' => 'id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\vendas\query\UsuariosQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\vendas\query\UsuariosQuery(get_called_class());
    }

}
