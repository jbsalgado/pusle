<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\models\Usuario;
use app\modules\vendas\helpers\PricingHelper;

/**
 * ============================================================================================================
 * Model: DadosFinanceiros
 * ============================================================================================================
 * Tabela: prest_dados_financeiros
 *
 * Armazena configurações financeiras para precificação inteligente (Markup Divisor).
 * Pode ser global por loja (produto_id = NULL) ou específica por produto.
 *
 * @property integer $id
 * @property string $usuario_id
 * @property string|null $produto_id
 * @property float $taxa_fixa_percentual
 * @property float $taxa_variavel_percentual
 * @property float $lucro_liquido_percentual
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @property Usuario $usuario
 * @property Produto|null $produto
 */
class DadosFinanceiros extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_dados_financeiros';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'data_criacao',
                'updatedAtAttribute' => 'data_atualizacao',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['usuario_id'], 'required'],
            [['usuario_id', 'produto_id'], 'string'],
            [['taxa_fixa_percentual', 'taxa_variavel_percentual', 'lucro_liquido_percentual'], 'number', 'min' => 0, 'max' => 99.99],
            [['taxa_fixa_percentual', 'taxa_variavel_percentual', 'lucro_liquido_percentual'], 'default', 'value' => 0],
            [['produto_id'], 'default', 'value' => null],
            // Validação: soma das taxas + lucro não pode ser >= 100%
            [['taxa_fixa_percentual', 'taxa_variavel_percentual', 'lucro_liquido_percentual'], 'validateSomaTaxasLucro'],
            // Validação: produto_id deve ser único por usuario_id (se não for NULL)
            [['produto_id'], 'validateProdutoUnico'],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
            [['produto_id'], 'exist', 'skipOnError' => true, 'targetClass' => Produto::class, 'targetAttribute' => ['produto_id' => 'id']],
        ];
    }

    /**
     * Validação customizada: soma das taxas + lucro não pode ser >= 100%
     */
    public function validateSomaTaxasLucro($attribute, $params)
    {
        $soma = ($this->taxa_fixa_percentual ?? 0) + 
                ($this->taxa_variavel_percentual ?? 0) + 
                ($this->lucro_liquido_percentual ?? 0);
        
        if ($soma >= 100) {
            $this->addError($attribute, "A soma das taxas fixas ({$this->taxa_fixa_percentual}%), variáveis ({$this->taxa_variavel_percentual}%) e lucro líquido ({$this->lucro_liquido_percentual}%) não pode ser 100% ou mais. Total: {$soma}%");
        }
    }

    /**
     * Validação: produto_id deve ser único por usuario_id (se não for NULL)
     */
    public function validateProdutoUnico($attribute, $params)
    {
        if ($this->produto_id === null) {
            return; // NULL é permitido (configuração global)
        }

        $query = self::find()
            ->where(['usuario_id' => $this->usuario_id, 'produto_id' => $this->produto_id]);
        
        // Se estiver editando, exclui o próprio registro da verificação
        if (!$this->isNewRecord) {
            $query->andWhere(['!=', 'id', $this->id]);
        }
        
        if ($query->exists()) {
            $this->addError($attribute, 'Este produto já possui uma configuração financeira específica.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Usuário',
            'produto_id' => 'Produto',
            'taxa_fixa_percentual' => 'Taxas Fixas (%)',
            'taxa_variavel_percentual' => 'Taxas Variáveis (%)',
            'lucro_liquido_percentual' => 'Lucro Líquido Desejado (%)',
            'data_criacao' => 'Data de Criação',
            'data_atualizacao' => 'Última Atualização',
        ];
    }

    /**
     * Retorna a relação com Usuario
     */
    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    /**
     * Retorna a relação com Produto (pode ser NULL para configuração global)
     */
    public function getProduto()
    {
        return $this->hasOne(Produto::class, ['id' => 'produto_id']);
    }

    /**
     * Busca ou cria configuração financeira global para o usuário
     * 
     * @param string $usuarioId ID do usuário
     * @return DadosFinanceiros
     */
    public static function getConfiguracaoGlobal($usuarioId)
    {
        $config = self::find()
            ->where(['usuario_id' => $usuarioId, 'produto_id' => null])
            ->one();
        
        if (!$config) {
            // Cria configuração padrão
            $config = new self();
            $config->usuario_id = $usuarioId;
            $config->produto_id = null;
            $config->taxa_fixa_percentual = 0;
            $config->taxa_variavel_percentual = 0;
            $config->lucro_liquido_percentual = 0;
            $config->save(false);
        }
        
        return $config;
    }

    /**
     * Busca configuração financeira para um produto específico
     * Retorna configuração específica do produto, ou global se não houver específica
     * 
     * @param string $produtoId ID do produto
     * @param string $usuarioId ID do usuário
     * @return DadosFinanceiros
     */
    public static function getConfiguracaoParaProduto($produtoId, $usuarioId)
    {
        // Primeiro tenta buscar configuração específica do produto
        $config = self::find()
            ->where(['produto_id' => $produtoId, 'usuario_id' => $usuarioId])
            ->one();
        
        // Se não encontrar, retorna a configuração global
        if (!$config) {
            $config = self::getConfiguracaoGlobal($usuarioId);
        }
        
        return $config;
    }

    /**
     * Calcula o preço de venda sugerido usando esta configuração
     * 
     * @param float $precoCusto Preço de custo (incluindo frete)
     * @return float Preço de venda sugerido
     */
    public function calcularPrecoVendaSugerido($precoCusto)
    {
        return PricingHelper::calcularPrecoPorMarkupDivisor(
            $precoCusto,
            $this->taxa_fixa_percentual,
            $this->taxa_variavel_percentual,
            $this->lucro_liquido_percentual
        );
    }

    /**
     * Verifica se a configuração resultaria em prejuízo para um preço de venda
     * 
     * @param float $precoVenda Preço de venda
     * @param float $precoCusto Preço de custo (incluindo frete)
     * @return bool True se resultar em prejuízo
     */
    public function resultariaEmPrejuizo($precoVenda, $precoCusto)
    {
        $provaReal = PricingHelper::calcularProvaReal(
            $precoVenda,
            $precoCusto,
            $this->taxa_fixa_percentual,
            $this->taxa_variavel_percentual
        );
        
        return $provaReal['lucro_real'] < 0;
    }
}

