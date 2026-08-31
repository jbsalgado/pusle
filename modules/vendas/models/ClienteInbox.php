<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * ClienteInbox ActiveRecord Model
 *
 * Tabela "prest_cliente_inbox"
 *
 * @property string $id
 * @property string $usuario_id
 * @property string|null $cliente_id
 * @property string|null $mesa_id
 * @property string|null $comanda_id
 * @property string $tipo
 * @property string|null $titulo
 * @property string|null $conteudo_texto
 * @property string|null $midia_url
 * @property array|null $acoes_json
 * @property bool $lido
 * @property string $created_at
 *
 * @property Clientes|null $cliente
 * @property Mesa|null $mesa
 * @property Comanda|null $comanda
 */
class ClienteInbox extends ActiveRecord
{
    const TIPO_TEXTO         = 'texto';
    const TIPO_VIDEO         = 'video';
    const TIPO_CARD          = 'card';
    const TIPO_STATUS_PEDIDO = 'status_pedido';
    const TIPO_CONTA         = 'conta';
    const TIPO_CHAMADO       = 'chamado';

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return 'prest_cliente_inbox';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['usuario_id'], 'required'],
            [['usuario_id', 'cliente_id', 'mesa_id', 'comanda_id'], 'string'],
            [['tipo'], 'string', 'max' => 30],
            [['tipo'], 'default', 'value' => self::TIPO_TEXTO],
            [['titulo'], 'string', 'max' => 255],
            [['conteudo_texto', 'midia_url'], 'string'],
            [['acoes_json'], 'safe'],
            [['lido'], 'boolean'],
            [['lido'], 'default', 'value' => false],
            [['created_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id'             => 'ID',
            'usuario_id'     => 'Empresa/Tenant',
            'cliente_id'     => 'Cliente',
            'mesa_id'        => 'Mesa',
            'comanda_id'     => 'Comanda',
            'tipo'           => 'Tipo de Mensagem',
            'titulo'         => 'Título',
            'conteudo_texto' => 'Conteúdo',
            'midia_url'      => 'URL da Mídia',
            'acoes_json'     => 'Ações / Botões',
            'lido'           => 'Lido',
            'created_at'     => 'Criado em',
        ];
    }

    /**
     * Relacionamento com o Cliente
     */
    public function getCliente()
    {
        return $this->hasOne(Clientes::class, ['id' => 'cliente_id']);
    }

    /**
     * Relacionamento com a Mesa
     */
    public function getMesa()
    {
        return $this->hasOne(Mesa::class, ['id' => 'mesa_id']);
    }

    /**
     * Helper para postar uma mensagem rápida ou notificação na timeline do cliente
     */
    public static function postar(
        string $usuarioId,
        ?string $clienteId,
        string $tipo,
        ?string $titulo,
        ?string $texto,
        ?string $midiaUrl = null,
        ?array $acoes = null,
        ?string $mesaId = null,
        ?string $comandaId = null
    ): ?self {
        $msg = new self();
        $msg->usuario_id     = $usuarioId;
        $msg->cliente_id     = $clienteId;
        $msg->tipo           = $tipo;
        $msg->titulo         = $titulo;
        $msg->conteudo_texto = $texto;
        $msg->midia_url      = $midiaUrl;
        $msg->acoes_json     = $acoes;
        $msg->mesa_id        = $mesaId;
        $msg->comanda_id     = $comandaId;
        $msg->created_at     = date('Y-m-d H:i:s');

        if ($msg->save()) {
            return $msg;
        }

        Yii::error("Falha ao salvar ClienteInbox: " . json_encode($msg->errors), __METHOD__);
        return null;
    }
}
