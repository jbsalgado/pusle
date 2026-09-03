<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\modules\vendas\models\Produto;


/**
 * ============================================================================================================
 * Model: ProdutoFoto
 * ============================================================================================================
 * Tabela: prest_produto_fotos
 * 
 * @property string $id
 * @property string $produto_id
 * @property string $arquivo_nome
 * @property string $arquivo_path
 * @property boolean $eh_principal
 * @property integer $ordem
 * @property string $data_upload
 * @property string|null $cor
 * @property string|null $variante_id
 * 
 * @property Produto $produto
 * @property ProdutoVariante|null $variante
 */
class ProdutoFoto extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_produto_fotos';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['produto_id', 'arquivo_nome', 'arquivo_path'], 'required'],
            [['produto_id', 'variante_id'], 'string'],
            [['cor'], 'string', 'max' => 50],
            [['eh_principal'], 'boolean'],
            [['ordem'], 'integer'],
            [['ordem'], 'default', 'value' => 0],
            [['arquivo_nome'], 'string', 'max' => 255],
            [['arquivo_path'], 'string', 'max' => 500],
            [['produto_id'], 'exist', 'skipOnError' => true, 'targetClass' => Produto::class, 'targetAttribute' => ['produto_id' => 'id']],
            [['variante_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProdutoVariante::class, 'targetAttribute' => ['variante_id' => 'id']],
            [['cor'], 'filter', 'filter' => function ($val) {
                return $val ? mb_strtoupper(trim($val), 'UTF-8') : null;
            }],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'produto_id' => 'Produto',
            'arquivo_nome' => 'Nome do Arquivo',
            'arquivo_path' => 'Caminho',
            'eh_principal' => 'Foto Principal',
            'ordem' => 'Ordem',
            'data_upload' => 'Data de Upload',
            'cor' => 'Modelo / Cor Associado',
            'variante_id' => 'Variação Específica',
        ];
    }

    /**
     * Antes de salvar, se for principal, desmarcar outras fotos principais
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Gera UUID se for um novo registro e não tiver ID definido
            if ($insert && empty($this->id)) {
                try {
                    // Tenta usar gen_random_uuid() do PostgreSQL (nativo, não precisa de extensão)
                    $uuid = Yii::$app->db->createCommand("SELECT gen_random_uuid()")->queryScalar();
                    if (empty($uuid)) {
                        throw new \Exception('UUID vazio retornado do banco');
                    }
                    $this->id = $uuid;
                    Yii::info('UUID gerado pelo banco: ' . $this->id, __METHOD__);
                } catch (\Exception $e) {
                    Yii::warning('Erro ao gerar UUID pelo banco: ' . $e->getMessage() . '. Usando fallback PHP.', __METHOD__);
                    // Fallback: gera UUID no PHP usando ramsey/uuid ou função nativa
                    if (function_exists('uuid_create')) {
                        $uuid = uuid_create(UUID_TYPE_RANDOM);
                        $this->id = $uuid;
                    } else {
                        // Gera UUID v4 manualmente
                        $this->id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                            mt_rand(0, 0xffff),
                            mt_rand(0, 0x0fff) | 0x4000,
                            mt_rand(0, 0x3fff) | 0x8000,
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                        );
                    }
                    Yii::info('UUID gerado pelo PHP: ' . $this->id, __METHOD__);
                }
            }
            
            if ($this->eh_principal) {
                // Desmarcar outras fotos principais do mesmo produto
                self::updateAll(
                    ['eh_principal' => false],
                    ['produto_id' => $this->produto_id]
                );
            }
            return true;
        }
        return false;
    }

    /**
     * Retorna URL completa da foto (robusta para localhost e VPS)
     */
    public function getUrl()
    {
        $caminhoFoto = ltrim($this->arquivo_path, '/');
        try {
            $webAlias = \Yii::getAlias('@web');
            if ($webAlias !== '@web' && $webAlias !== null && $webAlias !== '') {
                return rtrim($webAlias, '/') . '/' . $caminhoFoto;
            }
        } catch (\Throwable $e) {}

        if (\Yii::$app->has('request') && method_exists(\Yii::$app->request, 'getBaseUrl')) {
            try {
                $baseUrl = \Yii::$app->request->getBaseUrl();
                if ($baseUrl !== null && $baseUrl !== false && $baseUrl !== '') {
                    return rtrim($baseUrl, '/') . '/' . $caminhoFoto;
                }
            } catch (\Throwable $e) {}
        }

        return '/' . $caminhoFoto;
    }

    /**
     * Retorna a URL absoluta completa (com protocolo https:// e domínio) da foto
     */
    public function getUrlCompleta()
    {
        $caminho = ltrim($this->arquivo_path, '/');
        if (\Yii::$app->has('request') && method_exists(\Yii::$app->request, 'getHostInfo')) {
            try {
                $host = \Yii::$app->request->getHostInfo();
                $base = \Yii::$app->request->getBaseUrl();
                if ($host) {
                    $basePart = ($base && $base !== '/') ? '/' . trim($base, '/') : '';
                    return rtrim($host, '/') . $basePart . '/' . $caminho;
                }
            } catch (\Throwable $e) {}
        }
        return 'https://catalogos.oncode.app.br/' . $caminho;
    }

    public function getProduto()
    {
        return $this->hasOne(Produto::class, ['id' => 'produto_id']);
    }

    public function getVariante()
    {
        return $this->hasOne(ProdutoVariante::class, ['id' => 'variante_id']);
    }
}