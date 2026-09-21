<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;
use app\models\Usuario;

/**
 * Model CanalSetor — Setores/Grupos do Canal de Comunicação Interno (Multi-Tenant)
 *
 * @property string $id
 * @property string $usuario_id
 * @property string $nome
 * @property string|null $descricao
 * @property string|null $icone
 * @property string|null $cor
 * @property bool $ativo
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Usuario $loja
 * @property Colaborador[] $colaboradores
 * @property CanalSetorColaborador[] $vinculos
 */
class CanalSetor extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'prest_canal_setores';
    }

    public function rules(): array
    {
        return [
            [['usuario_id', 'nome'], 'required'],
            [['usuario_id'], 'string'],
            [['nome'], 'string', 'max' => 100],
            [['descricao'], 'string', 'max' => 255],
            [['icone', 'cor'], 'string', 'max' => 50],
            [['icone'], 'default', 'value' => '💬'],
            [['cor'], 'default', 'value' => 'emerald'],
            [['ativo'], 'boolean'],
            [['ativo'], 'default', 'value' => true],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Loja / Tenant',
            'nome' => 'Nome do Setor',
            'descricao' => 'Descrição',
            'icone' => 'Ícone / Emoji',
            'cor' => 'Cor do Setor',
            'ativo' => 'Ativo',
            'created_at' => 'Criado em',
            'updated_at' => 'Atualizado em',
        ];
    }

    public function getLoja()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    public function getVinculos()
    {
        return $this->hasMany(CanalSetorColaborador::class, ['setor_id' => 'id']);
    }

    public function getColaboradores()
    {
        return $this->hasMany(Colaborador::class, ['id' => 'colaborador_id'])
            ->viaTable('prest_canal_setor_colaboradores', ['setor_id' => 'id']);
    }

    public function getMensagens()
    {
        return $this->hasMany(ClienteInbox::class, ['setor_id' => 'id']);
    }

    /**
     * Retorna a quantidade de mensagens não lidas deste setor
     */
    public function getNaoLidasCount(): int
    {
        return (int) ClienteInbox::find()
            ->where([
                'usuario_id' => $this->usuario_id,
                'setor_id' => $this->id,
                'lido' => false,
            ])
            ->count();
    }

    /**
     * Cria setores padrão para a loja caso ela ainda não possua nenhum
     */
    public static function criarSetoresPadrao(string $lojaId): void
    {
        $existe = static::find()->where(['usuario_id' => $lojaId])->exists();
        if ($existe) {
            return;
        }

        $padroes = [
            [
                'nome' => 'Vendas',
                'descricao' => 'Atendimento a novos pedidos, cotações e orçamentos',
                'icone' => '💼',
                'cor' => 'emerald',
            ],
            [
                'nome' => 'Cobrança & Financeiro',
                'descricao' => 'Dúvidas sobre pagamentos, boletos, crediário e notas',
                'icone' => '💰',
                'cor' => 'amber',
            ],
            [
                'nome' => 'Atendimento Geral',
                'descricao' => 'Suporte, dúvidas gerais e informações da loja',
                'icone' => '🎧',
                'cor' => 'blue',
            ],
        ];

        foreach ($padroes as $p) {
            $s = new static();
            $s->usuario_id = $lojaId;
            $s->nome = $p['nome'];
            $s->descricao = $p['descricao'];
            $s->icone = $p['icone'];
            $s->cor = $p['cor'];
            $s->ativo = true;
            $s->save(false);
        }
    }

    /**
     * Retorna a lista de setores ativos aos quais o usuário logado tem acesso.
     * Dono da loja e administradores gerais visualizam TODOS os setores.
     * Colaboradores visualizam apenas os setores onde foram vinculados.
     */
    public static function getSetoresPermitidosParaUsuario(string $lojaId, $usuarioLogado): array
    {
        static::criarSetoresPadrao($lojaId);

        $query = static::find()
            ->where(['usuario_id' => $lojaId, 'ativo' => true])
            ->orderBy(['nome' => SORT_ASC]);

        if (!$usuarioLogado) {
            return $query->all();
        }

        // Se for dono da loja ou SaaS admin, vê todos
        $isDono = ($usuarioLogado->eh_dono_loja || $usuarioLogado->id === $lojaId || \app\components\TenantHelper::isAdmin());
        if ($isDono) {
            return $query->all();
        }

        // Busca vínculo de colaborador
        $colaborador = Colaborador::getColaboradorLogado();
        if ($colaborador && $colaborador->eh_administrador) {
            return $query->all();
        }

        if ($colaborador) {
            // Busca os IDs dos setores onde o colaborador foi expressamente vinculado
            $setorIds = CanalSetorColaborador::find()
                ->select('setor_id')
                ->where(['colaborador_id' => $colaborador->id, 'usuario_id' => $lojaId])
                ->column();

            if (!empty($setorIds)) {
                return $query->andWhere(['id' => $setorIds])->all();
            }

            // Fallback por papel se não foi vinculado manualmente a nenhum setor
            $condicoesFallback = ['or'];
            if ($colaborador->eh_vendedor) {
                $condicoesFallback[] = ['ilike', 'nome', 'venda%'];
            }
            if ($colaborador->eh_cobrador) {
                $condicoesFallback[] = ['ilike', 'nome', '%cobra%'];
            }

            if (count($condicoesFallback) > 1) {
                $setoresFallback = (clone $query)->andWhere($condicoesFallback)->all();
                if (!empty($setoresFallback)) {
                    return $setoresFallback;
                }
            }
        }

        return $query->all();
    }
}
