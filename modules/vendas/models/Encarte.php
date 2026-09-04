<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\models\Usuario;

/**
 * Model: Encarte
 * Tabela: prest_encartes
 *
 * @property string $id
 * @property string $usuario_id
 * @property string $titulo
 * @property string $subtitulo
 * @property string $token_publico
 * @property string $estilo_layout
 * @property int $produtos_por_pagina
 * @property string $cor_tema
 * @property string $modo_foto
 * @property string $status
 * @property int $visualizacoes_count
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Usuario $usuario
 * @property EncarteProduto[] $encarteProdutos
 * @property Produto[] $produtos
 */
class Encarte extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%prest_encartes}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function rules()
    {
        return [
            [['usuario_id', 'titulo'], 'required'],
            [['usuario_id'], 'string', 'max' => 36],
            [['titulo', 'subtitulo'], 'string', 'max' => 255],
            [['token_publico'], 'string', 'max' => 64],
            [['estilo_layout', 'cor_tema'], 'string', 'max' => 50],
            [['modo_foto'], 'string', 'max' => 20],
            [['modo_foto'], 'in', 'range' => ['contain', 'cover']],
            [['status'], 'string', 'max' => 20],
            [['produtos_por_pagina', 'visualizacoes_count'], 'integer'],
            [['produtos_por_pagina'], 'default', 'value' => 6],
            [['estilo_layout'], 'default', 'value' => 'flipsnack_supermarket'],
            [['cor_tema'], 'default', 'value' => 'red_gold'],
            [['modo_foto'], 'default', 'value' => 'contain'],
            [['status'], 'default', 'value' => 'ativo'],
            [['token_publico'], 'unique'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Usuário (Loja)',
            'titulo' => 'Título do Encarte',
            'subtitulo' => 'Subtítulo / Período',
            'token_publico' => 'Token Público',
            'estilo_layout' => 'Estilo de Layout',
            'produtos_por_pagina' => 'Produtos por Lâmina',
            'cor_tema' => 'Tema Visual',
            'modo_foto' => 'Exibição das Fotos nos Cards',
            'status' => 'Status',
            'visualizacoes_count' => 'Visualizações',
            'created_at' => 'Data de Criação',
            'updated_at' => 'Data de Atualização',
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                if (empty($this->id)) {
                    try {
                        $uuid = Yii::$app->db->createCommand("SELECT gen_random_uuid()")->queryScalar();
                        if (!empty($uuid)) {
                            $this->id = $uuid;
                        }
                    } catch (\Exception $e) {
                        $this->id = sprintf(
                            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                        );
                    }
                }

                if (empty($this->token_publico)) {
                    $this->token_publico = Yii::$app->security->generateRandomString(32);
                }
            }
            return true;
        }
        return false;
    }

    public function getModoFoto()
    {
        return !empty($this->modo_foto) && in_array($this->modo_foto, ['contain', 'cover']) ? $this->modo_foto : 'contain';
    }

    public function getUrlPublica()
    {
        return \yii\helpers\Url::to(['/encarte/' . $this->token_publico], true);
    }

    public function getUrlPdf()
    {
        return \yii\helpers\Url::to(['/vendas/encarte-publico/pdf', 'token' => $this->token_publico], true);
    }

    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    public function getEncarteProdutos()
    {
        return $this->hasMany(EncarteProduto::class, ['encarte_id' => 'id'])->orderBy(['ordem' => SORT_ASC]);
    }

    public function getProdutos()
    {
        return $this->hasMany(Produto::class, ['id' => 'produto_id'])
            ->viaTable('prest_encarte_produtos', ['encarte_id' => 'id']);
    }
}
