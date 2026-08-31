<?php

namespace app\modules\evolution\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Modelo ActiveRecord para a tabela pulse_whatsapp_templates.
 *
 * @property string      $id
 * @property string      $empresa_id        UUID do tenant em prest_usuarios
 * @property string      $name              Nome do template (letras minúsculas e sublinhados)
 * @property string      $language          Código do idioma (ex: 'pt_BR')
 * @property string      $category          'UTILITY' | 'MARKETING' | 'AUTHENTICATION'
 * @property string      $header_type       'NONE' | 'TEXT' | 'IMAGE' | 'VIDEO' | 'DOCUMENT'
 * @property string|null $header_text
 * @property string      $body_text         Corpo do template com variáveis (ex: {{1}}, {{2}})
 * @property string|null $footer_text
 * @property array|null  $buttons_json
 * @property array|null  $components_json   Estrutura bruta JSON enviada à Meta Graph API
 * @property string|null $meta_template_id  ID retornado pela Meta após criação
 * @property string      $status            'APPROVED' | 'PENDING' | 'REJECTED' | 'PAUSED' | 'DISABLED'
 * @property string      $created_at
 * @property string      $updated_at
 */
class WhatsappTemplate extends ActiveRecord
{
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_PENDING  = 'PENDING';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_PAUSED   = 'PAUSED';
    public const STATUS_DISABLED = 'DISABLED';

    public const CATEGORY_UTILITY        = 'UTILITY';
    public const CATEGORY_MARKETING      = 'MARKETING';
    public const CATEGORY_AUTHENTICATION = 'AUTHENTICATION';

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return 'pulse_whatsapp_templates';
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['empresa_id', 'name', 'body_text'], 'required'],
            [['empresa_id'], 'string', 'max' => 36],
            [['name', 'meta_template_id'], 'string', 'max' => 100],
            [['name'], 'match', 'pattern' => '/^[a-z0-9_]+$/', 'message' => 'O nome do template deve conter apenas letras minúsculas, números e sublinhados.'],
            [['language'], 'string', 'max' => 10],
            [['language'], 'default', 'value' => 'pt_BR'],
            [['category'], 'string', 'max' => 50],
            [['category'], 'in', 'range' => [self::CATEGORY_UTILITY, self::CATEGORY_MARKETING, self::CATEGORY_AUTHENTICATION]],
            [['category'], 'default', 'value' => self::CATEGORY_UTILITY],
            [['header_type'], 'string', 'max' => 20],
            [['header_type'], 'in', 'range' => ['NONE', 'TEXT', 'IMAGE', 'VIDEO', 'DOCUMENT']],
            [['header_type'], 'default', 'value' => 'NONE'],
            [['header_text', 'body_text'], 'string'],
            [['footer_text'], 'string', 'max' => 255],
            [['status'], 'string', 'max' => 30],
            [['status'], 'in', 'range' => [self::STATUS_APPROVED, self::STATUS_PENDING, self::STATUS_REJECTED, self::STATUS_PAUSED, self::STATUS_DISABLED]],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['buttons_json', 'components_json'], 'safe'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'id'               => 'ID',
            'empresa_id'       => 'Empresa',
            'name'             => 'Nome do Template',
            'language'         => 'Idioma',
            'category'         => 'Categoria',
            'header_type'      => 'Tipo de Cabeçalho',
            'header_text'      => 'Texto do Cabeçalho',
            'body_text'        => 'Corpo da Mensagem',
            'footer_text'      => 'Rodapé',
            'buttons_json'     => 'Botões',
            'components_json'  => 'Componentes Meta',
            'meta_template_id' => 'ID Meta Template',
            'status'           => 'Status de Aprovação',
            'created_at'       => 'Criado em',
            'updated_at'       => 'Atualizado em',
        ];
    }

    /**
     * Retorna templates aprovados de uma empresa
     */
    public static function findApprovedByEmpresa(string $empresaId): array
    {
        return static::find()
            ->where(['empresa_id' => $empresaId, 'status' => self::STATUS_APPROVED])
            ->orderBy(['name' => SORT_ASC])
            ->all();
    }

    /**
     * Retorna status formatado com badge CSS
     */
    public function getStatusBadge(): string
    {
        switch ($this->status) {
            case self::STATUS_APPROVED:
                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Aprovado</span>';
            case self::STATUS_PENDING:
                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">Em Análise</span>';
            case self::STATUS_REJECTED:
                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">Rejeitado</span>';
            case self::STATUS_PAUSED:
                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-orange-100 text-orange-800">Pausado</span>';
            default:
                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">' . htmlspecialchars($this->status) . '</span>';
        }
    }
}
