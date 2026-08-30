<?php

namespace app\modules\admin\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * SaasConfigGlobal Model - Configurações Globais do SaaS
 *
 * @property int $id
 * @property string $chave
 * @property string|null $valor
 * @property string|null $descricao
 * @property string $data_atualizacao
 */
class SaasConfigGlobal extends ActiveRecord
{
    public static function tableName()
    {
        return 'prest_saas_config_global';
    }

    public function rules()
    {
        return [
            [['chave'], 'required'],
            [['chave'], 'string', 'max' => 100],
            [['chave'], 'unique'],
            [['valor'], 'string'],
            [['descricao'], 'string', 'max' => 255],
        ];
    }

    public static function getValor(string $chave, $default = null)
    {
        $config = static::findOne(['chave' => $chave]);
        if ($config && $config->valor !== null && $config->valor !== '') {
            return $config->valor;
        }
        return $default;
    }

    public static function setValor(string $chave, $valor, ?string $descricao = null): bool
    {
        $config = static::findOne(['chave' => $chave]);
        if (!$config) {
            $config = new static();
            $config->chave = $chave;
            $config->descricao = $descricao;
        }
        $config->valor = (string) $valor;
        return $config->save();
    }
}
