<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\models\Usuario;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\models\Categoria;

/**
 * ============================================================================================================
 * Model: ComissaoConfig
 * ============================================================================================================
 * Tabela: prest_comissao_config
 * 
 * Modelo para configurações flexíveis de comissões por colaborador.
 * Permite múltiplas configurações por colaborador, podendo ser aplicadas por categoria específica ou para todas.
 * 
 * @property string $id
 * @property string $usuario_id
 * @property string $colaborador_id
 * @property string $tipo_comissao (VENDA ou COBRANCA)
 * @property string|null $categoria_id (NULL = todas as categorias, ou ID específico)
 * @property float $percentual (0-100)
 * @property boolean $ativo
 * @property string|null $data_inicio
 * @property string|null $data_fim
 * @property string|null $observacoes
 * @property string $data_criacao
 * @property string|null $data_atualizacao
 * 
 * @property Usuario $usuario
 * @property Colaborador $colaborador
 * @property Categoria|null $categoria
 */
class ComissaoConfig extends ActiveRecord
{
    const TIPO_VENDA = 'VENDA';
    const TIPO_COBRANCA = 'COBRANCA';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_comissao_config';
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
            [['usuario_id', 'colaborador_id', 'tipo_comissao', 'percentual'], 'required'],
            [['usuario_id', 'colaborador_id', 'categoria_id'], 'string'],
            [['percentual'], 'number', 'min' => 0, 'max' => 100],
            [['ativo'], 'boolean'],
            [['ativo'], 'default', 'value' => true],
            [['tipo_comissao'], 'string', 'max' => 20],
            [['tipo_comissao'], 'in', 'range' => [self::TIPO_VENDA, self::TIPO_COBRANCA]],
            [['data_inicio', 'data_fim'], 'date', 'format' => 'php:Y-m-d'],
            [['observacoes'], 'string'],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
            [['colaborador_id'], 'exist', 'skipOnError' => true, 'targetClass' => Colaborador::class, 'targetAttribute' => ['colaborador_id' => 'id']],
            [['categoria_id'], 'exist', 'skipOnError' => true, 'targetClass' => Categoria::class, 'targetAttribute' => ['categoria_id' => 'id']],
            // Validar que data_fim deve ser maior que data_inicio
            ['data_fim', 'compare', 'compareAttribute' => 'data_inicio', 'operator' => '>', 'skipOnEmpty' => true],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert && empty($this->id)) {
                $this->id = new Expression('gen_random_uuid()');
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
            'colaborador_id' => 'Colaborador',
            'tipo_comissao' => 'Tipo de Comissão',
            'categoria_id' => 'Categoria',
            'percentual' => 'Percentual (%)',
            'ativo' => 'Ativo',
            'data_inicio' => 'Data de Início',
            'data_fim' => 'Data de Fim',
            'observacoes' => 'Observações',
            'data_criacao' => 'Data de Criação',
            'data_atualizacao' => 'Última Atualização',
        ];
    }

    /**
     * Retorna relação com Usuario
     */
    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    /**
     * Retorna relação com Colaborador
     */
    public function getColaborador()
    {
        return $this->hasOne(Colaborador::class, ['id' => 'colaborador_id']);
    }

    /**
     * Retorna relação com Categoria
     */
    public function getCategoria()
    {
        return $this->hasOne(Categoria::class, ['id' => 'categoria_id']);
    }

    /**
     * Retorna relação com Comissões calculadas usando esta configuração
     */
    public function getComissoes()
    {
        return $this->hasMany(Comissao::class, ['comissao_config_id' => 'id']);
    }

    /**
     * Verifica se a configuração está vigente (considerando datas de início e fim)
     */
    public function isVigente()
    {
        if (!$this->ativo) {
            return false;
        }

        $hoje = date('Y-m-d');

        if ($this->data_inicio && $this->data_inicio > $hoje) {
            return false; // Ainda não iniciou
        }

        if ($this->data_fim && $this->data_fim < $hoje) {
            return false; // Já expirou
        }

        return true;
    }

    /**
     * Retorna descrição da configuração
     */
    public function getDescricao()
    {
        $tipo = $this->tipo_comissao == self::TIPO_VENDA ? 'Venda' : 'Cobrança';
        $categoria = $this->categoria_id ? $this->categoria->nome : 'Todas as Categorias';
        return "{$tipo} - {$categoria} - {$this->percentual}%";
    }

    /**
     * Busca configuração de comissão aplicável para um colaborador, tipo e categoria
     * 
     * @param string $colaboradorId
     * @param string $tipoComissao (VENDA ou COBRANCA)
     * @param string|null $categoriaId (NULL para todas)
     * @param string|null $usuarioId
     * @return ComissaoConfig|null
     */
    public static function buscarConfiguracao($colaboradorId, $tipoComissao, $categoriaId = null, $usuarioId = null)
    {
        $usuarioId = $usuarioId ?: Yii::$app->user->id;
        $hoje = date('Y-m-d');

        // Primeiro, busca configuração específica para a categoria (se houver)
        if ($categoriaId) {
            $configEspecifica = self::find()
                ->where([
                    'usuario_id' => $usuarioId,
                    'colaborador_id' => $colaboradorId,
                    'tipo_comissao' => $tipoComissao,
                    'categoria_id' => $categoriaId,
                    'ativo' => true,
                ])
                ->andWhere(['or',
                    ['data_inicio' => null],
                    ['<=', 'data_inicio', $hoje]
                ])
                ->andWhere(['or',
                    ['data_fim' => null],
                    ['>=', 'data_fim', $hoje]
                ])
                ->one();

            if ($configEspecifica) {
                return $configEspecifica;
            }
        }

        // Se não encontrou específica, busca configuração geral (categoria_id = NULL)
        return self::find()
            ->where([
                'usuario_id' => $usuarioId,
                'colaborador_id' => $colaboradorId,
                'tipo_comissao' => $tipoComissao,
                'categoria_id' => null,
                'ativo' => true,
            ])
            ->andWhere(['or',
                ['data_inicio' => null],
                ['<=', 'data_inicio', $hoje]
            ])
            ->andWhere(['or',
                ['data_fim' => null],
                ['>=', 'data_fim', $hoje]
            ])
            ->one();
    }

    /**
     * Retorna lista de configurações para dropdown
     */
    public static function getListaDropdown($usuarioId = null, $colaboradorId = null)
    {
        $usuarioId = $usuarioId ?: Yii::$app->user->id;
        
        $query = self::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true])
            ->with(['colaborador', 'categoria']);

        if ($colaboradorId) {
            $query->andWhere(['colaborador_id' => $colaboradorId]);
        }

        $configs = $query->orderBy(['colaborador_id' => SORT_ASC, 'tipo_comissao' => SORT_ASC])->all();

        $lista = [];
        foreach ($configs as $config) {
            $lista[$config->id] = $config->getDescricao();
        }

        return $lista;
    }
}

