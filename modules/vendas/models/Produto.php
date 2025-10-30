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
 * @property float $preco_venda_sugerido
 * @property integer $estoque_atual
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
            [['preco_custo', 'preco_venda_sugerido', 'preco_promocional'], 'number', 'min' => 0],
            [['estoque_atual'], 'integer', 'min' => 0],
            [['estoque_atual'], 'default', 'value' => 0],
            [['ativo', 'permite_parcelamento'], 'boolean'],
            [['ativo'], 'default', 'value' => true],
            [['permite_parcelamento'], 'default', 'value' => false],
            [['data_inicio_promocao', 'data_fim_promocao'], 'safe'],
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
            'preco_venda_sugerido' => 'Preço de Venda',
            'estoque_atual' => 'Estoque Atual',
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
     * Retorna margem de lucro em porcentagem
     */
    public function getMargemLucro()
    {
        if ($this->preco_custo == 0) return 0;
        return (($this->preco_venda_sugerido - $this->preco_custo) / $this->preco_custo) * 100;
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