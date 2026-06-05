<?php

namespace app\modules\contas_pagar\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use app\models\Usuario;

/**
 * ============================================================================================================
 * Model: TipoDespesa
 * ============================================================================================================
 * Tabela: prest_tipos_despesa
 *
 * Representa uma categoria genérica e reutilizável de despesa.
 * O detalhe específico de cada lançamento (número de NF, mês, etc.)
 * deve ser registrado no campo "descrição" da conta a pagar — não aqui.
 *
 * Exemplos de tipos corretos: "Aluguel", "Energia Elétrica", "Compra de Mercadoria"
 * Exemplos incorretos:        "Compra NF 001", "Aluguel Jan/2026" (específicos demais)
 *
 * Grupos (hard-coded, não editáveis):
 *   - FIXA       → Despesas Fixas
 *   - VARIAVEL   → Despesas Variáveis
 *   - MERCADORIA → Compras de Mercadorias
 *
 * @property string      $id
 * @property string      $usuario_id
 * @property string      $nome
 * @property string      $grupo         (FIXA | VARIAVEL | MERCADORIA)
 * @property string|null $descricao
 * @property bool        $ativo
 * @property string      $data_criacao
 * @property string      $data_atualizacao
 *
 * @property Usuario     $usuario
 * @property ContaPagar[] $contasPagar
 */
class TipoDespesa extends ActiveRecord
{
    // Constantes de grupo (hard-coded)
    const GRUPO_FIXA       = 'FIXA';
    const GRUPO_VARIAVEL   = 'VARIAVEL';
    const GRUPO_MERCADORIA = 'MERCADORIA';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_tipos_despesa';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class'            => TimestampBehavior::class,
                'createdAtAttribute'  => 'data_criacao',
                'updatedAtAttribute'  => 'data_atualizacao',
                'value'            => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['usuario_id', 'nome', 'grupo'], 'required'],
            [['nome'], 'string', 'max' => 100],
            [['descricao'], 'string'],
            [['usuario_id'], 'string'],
            [['ativo'], 'boolean'],
            [['ativo'], 'default', 'value' => true],
            [['grupo'], 'in', 'range' => [self::GRUPO_FIXA, self::GRUPO_VARIAVEL, self::GRUPO_MERCADORIA]],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'               => 'ID',
            'usuario_id'       => 'Usuário',
            'nome'             => 'Nome do Tipo',
            'grupo'            => 'Grupo de Despesa',
            'descricao'        => 'Descrição',
            'ativo'            => 'Ativo',
            'data_criacao'     => 'Data de Criação',
            'data_atualizacao' => 'Data de Atualização',
        ];
    }

    // -------------------------------------------------------------------------
    // Relações
    // -------------------------------------------------------------------------

    /**
     * Relação com Usuario
     */
    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    /**
     * Relação reversa com ContaPagar
     */
    public function getContasPagar()
    {
        return $this->hasMany(ContaPagar::class, ['tipo_despesa_id' => 'id']);
    }

    // -------------------------------------------------------------------------
    // Helpers estáticos
    // -------------------------------------------------------------------------

    /**
     * Retorna o mapa de grupos para uso em dropdowns.
     * @return array [valor => label]
     */
    public static function getGruposMap()
    {
        return [
            self::GRUPO_FIXA       => 'Despesas Fixas',
            self::GRUPO_VARIAVEL   => 'Despesas Variáveis',
            self::GRUPO_MERCADORIA => 'Compras de Mercadorias',
        ];
    }

    /**
     * Retorna o label legível de um grupo.
     * @param string $grupo
     * @return string
     */
    public static function getGrupoLabel($grupo)
    {
        $map = self::getGruposMap();
        return $map[$grupo] ?? $grupo;
    }

    /**
     * Retorna a classe CSS de badge para o grupo.
     * @param string $grupo
     * @return string classes Tailwind
     */
    public static function getGrupoBadgeClass($grupo)
    {
        $map = [
            self::GRUPO_FIXA       => 'bg-red-100 text-red-800',
            self::GRUPO_VARIAVEL   => 'bg-yellow-100 text-yellow-800',
            self::GRUPO_MERCADORIA => 'bg-blue-100 text-blue-800',
        ];
        return $map[$grupo] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Retorna o ícone emoji para o grupo.
     * @param string $grupo
     * @return string
     */
    public static function getGrupoIcon($grupo)
    {
        $map = [
            self::GRUPO_FIXA       => '🔴',
            self::GRUPO_VARIAVEL   => '🟡',
            self::GRUPO_MERCADORIA => '🔵',
        ];
        return $map[$grupo] ?? '⚪';
    }

    /**
     * Retorna tipos ativos de um grupo para uso em dropdown.
     * @param string $grupo
     * @param string $usuarioId
     * @return array [id => nome]
     */
    public static function getPorGrupo($grupo, $usuarioId)
    {
        $tipos = self::find()
            ->where(['grupo' => $grupo, 'ativo' => true, 'usuario_id' => $usuarioId])
            ->orderBy(['nome' => SORT_ASC])
            ->all();

        $result = [];
        foreach ($tipos as $tipo) {
            $result[$tipo->id] = $tipo->nome;
        }
        return $result;
    }

    /**
     * Retorna todos os tipos ativos do usuário agrupados por grupo.
     * Retorna array: ['FIXA' => ['id' => 'nome', ...], 'VARIAVEL' => [...], ...]
     * @param string $usuarioId
     * @return array
     */
    public static function getTodosAgrupados($usuarioId)
    {
        $tipos = self::find()
            ->where(['ativo' => true, 'usuario_id' => $usuarioId])
            ->orderBy(['grupo' => SORT_ASC, 'nome' => SORT_ASC])
            ->all();

        $result = [];
        foreach ($tipos as $tipo) {
            $result[$tipo->grupo][$tipo->id] = $tipo->nome;
        }
        return $result;
    }

    /**
     * Retorna todos os tipos ativos para dropdown simples (sem agrupamento).
     * @param string $usuarioId
     * @return array [id => 'Grupo — Nome']
     */
    public static function getTodosFlatMap($usuarioId)
    {
        $tipos = self::find()
            ->where(['ativo' => true, 'usuario_id' => $usuarioId])
            ->orderBy(['grupo' => SORT_ASC, 'nome' => SORT_ASC])
            ->all();

        $result = [];
        foreach ($tipos as $tipo) {
            $grupoLabel = self::getGrupoLabel($tipo->grupo);
            $result[$tipo->id] = $grupoLabel . ' — ' . $tipo->nome;
        }
        return $result;
    }

    /**
     * Verifica se o tipo possui contas a pagar vinculadas.
     * @return bool
     */
    public function temContasVinculadas()
    {
        return ContaPagar::find()->where(['tipo_despesa_id' => $this->id])->exists();
    }
}
