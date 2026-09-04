<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\helpers\Url;

/**
 * Model para prest_trilhas_sonoras
 *
 * @property string $id
 * @property string $usuario_id
 * @property string $titulo
 * @property string $descricao
 * @property string $arquivo_nome
 * @property string $arquivo_path
 * @property string $formato
 * @property int $tamanho_bytes
 * @property string $tipo
 * @property bool $ativo
 * @property string $created_at
 * @property string $updated_at
 */
class TrilhaSonora extends ActiveRecord
{
    const TIPO_MUSICA = 'musica';
    const TIPO_EFEITO = 'efeito_especial';
    const TIPO_YOUTUBE = 'youtube';
    const TIPO_VOZ_IA = 'voz_ia';

    /**
     * @var \yii\web\UploadedFile File upload helper
     */
    public $audioFile;

    private $_tipo;

    public function getTipo()
    {
        if ($this->hasAttribute('tipo') && parent::__get('tipo') !== null) {
            return parent::__get('tipo');
        }
        return $this->_tipo ?? self::TIPO_MUSICA;
    }

    public function setTipo($value)
    {
        $this->_tipo = $value;
        if ($this->hasAttribute('tipo')) {
            $this->setAttribute('tipo', $value);
        }
    }

    public static function tableName()
    {
        return 'prest_trilhas_sonoras';
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
            [['usuario_id', 'titulo', 'arquivo_nome', 'arquivo_path'], 'required'],
            [['usuario_id'], 'string', 'max' => 36],
            [['titulo', 'arquivo_nome'], 'string', 'max' => 255],
            [['arquivo_path'], 'string', 'max' => 500],
            [['formato'], 'string', 'max' => 10],
            [['tipo'], 'string', 'max' => 30],
            [['tipo'], 'default', 'value' => self::TIPO_MUSICA],
            [['tipo'], 'in', 'range' => [self::TIPO_MUSICA, self::TIPO_EFEITO]],
            [['descricao'], 'string'],
            [['tamanho_bytes'], 'integer'],
            [['ativo'], 'boolean'],
            [['ativo'], 'default', 'value' => true],
            [['audioFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'mp3, wav, aac, m4a, ogg', 'maxSize' => 15 * 1024 * 1024],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Usuário',
            'titulo' => 'Título da Música / Efeito',
            'descricao' => 'Descrição / Gênero',
            'arquivo_nome' => 'Nome do Arquivo',
            'arquivo_path' => 'Caminho',
            'formato' => 'Formato',
            'tamanho_bytes' => 'Tamanho',
            'tipo' => 'Tipo de Áudio',
            'ativo' => 'Ativo',
            'audioFile' => 'Arquivo de Áudio (MP3, WAV, AAC, M4A, OGG)',
            'created_at' => 'Data de Cadastro',
        ];
    }

    public static function getTiposDisponiveis()
    {
        return [
            self::TIPO_MUSICA => '🎵 Música de Fundo',
            self::TIPO_EFEITO => '🔊 Efeito Especial / Vinheta',
            self::TIPO_YOUTUBE => '▶️ Áudio do YouTube',
            self::TIPO_VOZ_IA => '🎙️ Locução IA (Texto)',
        ];
    }

    public function getTipoLabel()
    {
        $tipos = self::getTiposDisponiveis();
        return $tipos[$this->tipo] ?? '🎵 Música de Fundo';
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert && empty($this->id)) {
                $this->id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
            }
            if (empty($this->tipo)) {
                $this->tipo = self::TIPO_MUSICA;
            }
            return true;
        }
        return false;
    }

    public function getUrl()
    {
        $caminho = '/' . ltrim($this->arquivo_path, '/');
        if (Yii::$app->has('request') && method_exists(Yii::$app->request, 'getHostInfo') && Yii::$app->request->getHostInfo()) {
            return Yii::$app->request->getHostInfo() . $caminho;
        }
        return $caminho;
    }

    public function getTamanhoFormatado()
    {
        $bytes = (int)$this->tamanho_bytes;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
