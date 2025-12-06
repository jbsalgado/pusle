<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\models\Usuario;
use app\modules\vendas\models\Categoria;
use app\modules\vendas\models\VendaItem;
use app\modules\vendas\models\ProdutoFoto;
use app\modules\vendas\helpers\PricingHelper;

/**
 * ============================================================================================================
 * Model: Produto
 * ============================================================================================================
 * Tabela: prest_produtos
 *
 * @property string $id
 * @property string $usuario_id
 * @property string $categoria_id
 * @property string $nome
 * @property string $descricao
 * @property string $codigo_referencia
 * @property float $preco_custo
 * @property float $valor_frete
 * @property float $preco_venda_sugerido
 * @property float $margem_lucro_percentual
 * @property float $markup_percentual
 * @property integer $estoque_atual
 * @property integer $estoque_minimo
 * @property integer $ponto_corte
 * @property boolean $ativo
 * @property string $data_criacao
 * @property string $data_atualizacao
 * @property boolean $permite_parcelamento
 * @property float $preco_promocional
 * @property string $data_inicio_promocao
 * @property string $data_fim_promocao
 *
 * @property Usuario $usuario
 * @property Categoria $categoria
 * @property ProdutoFoto[] $fotos
 * @property VendaItem[] $vendaItens
 */
class Produto extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_produtos';
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
            [['usuario_id', 'nome', 'preco_venda_sugerido'], 'required'],
            [['usuario_id', 'categoria_id'], 'string'],
            [['descricao'], 'string'],
            [['preco_custo', 'valor_frete', 'preco_venda_sugerido', 'preco_promocional'], 'number', 'min' => 0],
            [['margem_lucro_percentual'], 'number', 'min' => 0, 'max' => 99.99], // Margem: 0-99.99%
            [['markup_percentual'], 'number', 'min' => 0], // ✅ Markup: sem limite máximo (pode ser qualquer valor positivo)
            [['estoque_atual', 'estoque_minimo', 'ponto_corte'], 'integer', 'min' => 0, 'skipOnEmpty' => false],
            [['estoque_atual'], 'default', 'value' => 0],
            [['estoque_minimo'], 'default', 'value' => 10],
            [['ponto_corte'], 'default', 'value' => 5],
            [['estoque_atual', 'estoque_minimo', 'ponto_corte'], 'filter', 'filter' => function($value) {
                // ✅ Converte string vazia para 0, mantém números inteiros
                if ($value === '' || $value === null) {
                    return 0;
                }
                return (int) $value;
            }],
            // Validação: ponto_corte deve ser menor ou igual a estoque_minimo
            [['ponto_corte'], 'compare', 'compareAttribute' => 'estoque_minimo', 'operator' => '<=', 'skipOnEmpty' => false, 'message' => 'O ponto de corte deve ser menor ou igual ao estoque mínimo.'],
            [['valor_frete'], 'default', 'value' => 0],
            [['ativo', 'permite_parcelamento'], 'boolean'],
            [['ativo'], 'default', 'value' => true],
            [['permite_parcelamento'], 'default', 'value' => false],
            [['data_inicio_promocao', 'data_fim_promocao'], 'safe'],
            [['estoque_atual', 'estoque_minimo', 'ponto_corte'], 'safe'], // ✅ Garante que os campos podem ser carregados via load()
            [['nome'], 'string', 'max' => 150],
            [['codigo_referencia'], 'string', 'max' => 50],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
            [['categoria_id'], 'exist', 'skipOnError' => true, 'targetClass' => Categoria::class, 'targetAttribute' => ['categoria_id' => 'id']],
            // Código de referência único por usuário
            [['codigo_referencia'], 'unique', 'targetAttribute' => ['usuario_id', 'codigo_referencia']],
            // Validação de promoção: se tem preço promocional, deve ter datas
            ['preco_promocional', 'validatePromocao'],
        ];
    }

    /**
     * Validação customizada para campos de promoção
     */
    public function validatePromocao($attribute, $params)
    {
        if (!empty($this->preco_promocional)) {
            if (empty($this->data_inicio_promocao) || empty($this->data_fim_promocao)) {
                $this->addError($attribute, 'Quando há preço promocional, as datas de início e fim são obrigatórias.');
            }
            
            if ($this->preco_promocional >= $this->preco_venda_sugerido) {
                $this->addError($attribute, 'O preço promocional deve ser menor que o preço de venda sugerido.');
            }
        }
    }

    /**
     * Calcula e atualiza margem de lucro e markup automaticamente
     */
    public function calculateMargemMarkup($attribute, $params)
    {
        $custoTotal = PricingHelper::calcularCustoTotal($this->preco_custo, $this->valor_frete);
        
        if ($custoTotal > 0 && $this->preco_venda_sugerido > 0) {
            $this->margem_lucro_percentual = PricingHelper::calcularMargemLucro($custoTotal, $this->preco_venda_sugerido);
            $this->markup_percentual = PricingHelper::calcularMarkup($custoTotal, $this->preco_venda_sugerido);
        } else {
            $this->margem_lucro_percentual = null;
            $this->markup_percentual = null;
        }
    }

    /**
     * Hook antes de salvar para calcular margem e markup
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // 🔍 DEBUG: Log do estoque antes de salvar
            Yii::info('Estoque antes de salvar: ' . $this->estoque_atual, __METHOD__);
            
            // ✅ Calcula margem e markup, mas limita margem a 99.99% para não falhar validação
            // Markup pode ser qualquer valor positivo (sem limite)
            $custoTotal = PricingHelper::calcularCustoTotal($this->preco_custo, $this->valor_frete);
            
            if ($custoTotal > 0 && $this->preco_venda_sugerido > 0) {
                $this->margem_lucro_percentual = PricingHelper::calcularMargemLucro($custoTotal, $this->preco_venda_sugerido);
                $this->markup_percentual = PricingHelper::calcularMarkup($custoTotal, $this->preco_venda_sugerido);
                
                // ✅ Limita margem a 99.99% para não falhar validação
                if ($this->margem_lucro_percentual > 99.99) {
                    $this->margem_lucro_percentual = 99.99;
                }
            } else {
                $this->margem_lucro_percentual = null;
                $this->markup_percentual = null;
            }
            
            return true;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Usuário',
            'categoria_id' => 'Categoria',
            'nome' => 'Nome',
            'descricao' => 'Descrição',
            'codigo_referencia' => 'Código de Referência',
            'preco_custo' => 'Preço de Custo',
            'valor_frete' => 'Valor do Frete',
            'preco_venda_sugerido' => 'Preço de Venda',
            'margem_lucro_percentual' => 'Margem de Lucro (%)',
            'markup_percentual' => 'Markup (%)',
            'estoque_atual' => 'Estoque Atual',
            'estoque_minimo' => 'Estoque Mínimo',
            'ponto_corte' => 'Ponto de Corte',
            'ativo' => 'Ativo',
            'data_criacao' => 'Data de Cadastro',
            'data_atualizacao' => 'Última Atualização',
            'permite_parcelamento' => 'Permite Parcelamento',
            'preco_promocional' => 'Preço Promocional',
            'data_inicio_promocao' => 'Início da Promoção',
            'data_fim_promocao' => 'Fim da Promoção',
        ];
    }

    /**
     * ✅ MÉTODO fields() MODIFICADO/ADICIONADO
     * Controla quais campos são retornados por padrão na API.
     */
    public function fields()
    {
        $fields = parent::fields(); // Pega os campos padrão (colunas da tabela)

        // Adiciona a relação 'fotos' aos campos padrão
        // Isso garante que a relação seja incluída no JSON se carregada com ->with('fotos')
        $fields['fotos'] = 'fotos';

        // Adiciona campos calculados
        $fields['em_promocao'] = 'emPromocao';
        $fields['preco_final'] = 'precoFinal';

        // Descomente a linha abaixo se quiser incluir a categoria por padrão também
        // $fields['categoria'] = 'categoria';

        return $fields;
    }


    /**
     * Define quais campos e relações extras podem ser incluídos na resposta da API
     * usando o parâmetro ?expand=... na URL.
     * Como 'fotos' agora está em fields(), só precisamos de 'categoria' aqui se quisermos
     * que ela seja opcional (carregada apenas com ?expand=categoria).
     * Se 'categoria' também foi movida para fields(), este método pode ser removido
     * ou retornar um array vazio.
     */
    public function extraFields()
    {
        // 'fotos' foi movido para fields(), então só deixamos 'categoria' aqui
        return ['categoria'];
    }


    /**
     * Retorna margem de lucro em porcentagem (CORRIGIDO)
     * Margem = (Preço de Venda - Custo) / Preço de Venda * 100
     * 
     * @return float Margem de lucro em percentual
     */
    public function getMargemLucro()
    {
        $custoTotal = PricingHelper::calcularCustoTotal($this->preco_custo, $this->valor_frete);
        return PricingHelper::calcularMargemLucro($custoTotal, $this->preco_venda_sugerido);
    }

    /**
     * Retorna o markup em porcentagem
     * Markup = (Preço de Venda - Custo) / Custo * 100
     * 
     * @return float Markup em percentual
     */
    public function getMarkup()
    {
        $custoTotal = PricingHelper::calcularCustoTotal($this->preco_custo, $this->valor_frete);
        return PricingHelper::calcularMarkup($custoTotal, $this->preco_venda_sugerido);
    }

    /**
     * Retorna o custo total (custo + frete)
     * 
     * @return float Custo total
     */
    public function getCustoTotal()
    {
        return PricingHelper::calcularCustoTotal($this->preco_custo, $this->valor_frete);
    }

    /**
     * ✅ NOVO: Verifica se o produto está em promoção ativa
     */
    public function getEmPromocao()
    {
        if (empty($this->preco_promocional)) {
            return false;
        }
        
        $agora = new \DateTime();
        $inicio = $this->data_inicio_promocao ? new \DateTime($this->data_inicio_promocao) : null;
        $fim = $this->data_fim_promocao ? new \DateTime($this->data_fim_promocao) : null;
        
        if ($inicio && $fim) {
            return $agora >= $inicio && $agora <= $fim;
        }
        
        return false;
    }

    /**
     * ✅ NOVO: Retorna o preço final (promocional se estiver em promoção, ou normal)
     */
    public function getPrecoFinal()
    {
        return $this->emPromocao ? $this->preco_promocional : $this->preco_venda_sugerido;
    }

    /**
     * ✅ NOVO: Retorna desconto em porcentagem
     */
    public function getDescontoPromocional()
    {
        if (!$this->emPromocao || $this->preco_venda_sugerido == 0) {
            return 0;
        }
        
        return (($this->preco_venda_sugerido - $this->preco_promocional) / $this->preco_venda_sugerido) * 100;
    }

    /**
     * Retorna foto principal do produto
     */
    public function getFotoPrincipal()
    {
        return $this->getFotos()
            ->where(['eh_principal' => true])
            ->one();
    }

    /**
     * Verifica se produto tem estoque disponível
     */
    public function temEstoque($quantidade = 1)
    {
        return $this->estoque_atual >= $quantidade;
    }

    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    public function getCategoria()
    {
        return $this->hasOne(Categoria::class, ['id' => 'categoria_id']);
    }

    public function getFotos()
    {
        // Certifique-se de que a relação está correta e ordenada
        return $this->hasMany(ProdutoFoto::class, ['produto_id' => 'id'])
            ->orderBy(['eh_principal' => SORT_DESC, 'ordem' => SORT_ASC]);
    }

    public function getVendaItens()
    {
        return $this->hasMany(VendaItem::class, ['produto_id' => 'id']);
    }

    /**
     * Retorna produtos ativos para dropdown
     */
    public static function getListaDropdown($usuarioId = null, $apenasComEstoque = false)
    {
        $usuarioId = $usuarioId ?: Yii::$app->user->id;

        $query = self::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true]);

        if ($apenasComEstoque) {
            $query->andWhere(['>', 'estoque_atual', 0]);
        }

        return $query->select(['nome', 'id'])
            ->indexBy('id')
            ->orderBy(['nome' => SORT_ASC])
            ->column();
    }
}