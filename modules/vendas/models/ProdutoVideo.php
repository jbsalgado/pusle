<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\models\Usuario;
use app\modules\vendas\models\Produto;

/**
 * Model: ProdutoVideo
 * Tabela: prest_produto_videos
 *
 * @property string $id
 * @property string $produto_id
 * @property string $usuario_id
 * @property int $duracao
 * @property string $formato
 * @property string $status
 * @property string $video_path
 * @property string $video_url
 * @property string $erro_mensagem
 * @property array $metadata
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @property Produto $produto
 * @property Usuario $usuario
 */
class ProdutoVideo extends ActiveRecord
{
    const STATUS_PENDENTE = 'pendente';
    const STATUS_PROCESSANDO = 'processando';
    const STATUS_CONCLUIDO = 'concluido';
    const STATUS_ERRO = 'erro';

    const FORMATO_STORIES = 'stories';
    const FORMATO_FEED = 'feed';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_produto_videos';
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
            [['produto_id', 'usuario_id'], 'required'],
            [['produto_id', 'usuario_id'], 'string', 'max' => 36],
            [['duracao'], 'integer', 'min' => 0],
            [['duracao'], 'default', 'value' => 15],
            [['formato'], 'default', 'value' => self::FORMATO_STORIES],
            [['status'], 'default', 'value' => self::STATUS_PENDENTE],
            [['status'], 'in', 'range' => [self::STATUS_PENDENTE, self::STATUS_PROCESSANDO, self::STATUS_CONCLUIDO, self::STATUS_ERRO]],
            [['video_path', 'video_url'], 'string', 'max' => 500],
            [['erro_mensagem'], 'string'],
            [['metadata'], 'safe'],
            [['produto_id'], 'exist', 'skipOnError' => true, 'targetClass' => Produto::class, 'targetAttribute' => ['produto_id' => 'id']],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
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
            'usuario_id' => 'Usuário',
            'duracao' => 'Duração (segundos)',
            'formato' => 'Formato (Stories/Reels 9:16)',
            'status' => 'Status da Renderização',
            'video_path' => 'Caminho do Vídeo MP4',
            'video_url' => 'URL Pública do Vídeo',
            'erro_mensagem' => 'Mensagem de Erro',
            'metadata' => 'Metadados',
            'data_criacao' => 'Data de Criação',
            'data_atualizacao' => 'Data de Atualização',
        ];
    }

    /**
     * Retorna URL pública completa do vídeo
     */
    public function getUrl()
    {
        if (!empty($this->video_url) && (strpos($this->video_url, 'http://') === 0 || strpos($this->video_url, 'https://') === 0)) {
            return $this->video_url;
        }

        if (!empty($this->video_path)) {
            $caminho = ltrim($this->video_path, '/');
            return \yii\helpers\Url::to('@web/' . $caminho, true);
        }

        return '';
    }

    /**
     * Hook beforeSave para gerar UUID se necessário e serializar JSON
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert && empty($this->id)) {
                try {
                    $uuid = Yii::$app->db->createCommand("SELECT gen_random_uuid()")->queryScalar();
                    if (!empty($uuid)) {
                        $this->id = $uuid;
                    }
                } catch (\Exception $e) {
                    if (function_exists('uuid_create')) {
                        $this->id = uuid_create(UUID_TYPE_RANDOM);
                    } else {
                        $this->id = sprintf(
                            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                        );
                    }
                }
            }

            if (is_array($this->metadata)) {
                $this->metadata = json_encode($this->metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }

            return true;
        }
        return false;
    }

    /**
     * Hook afterFind para deserializar metadata em array
     */
    public function afterFind()
    {
        parent::afterFind();
        if (is_string($this->metadata)) {
            $this->metadata = json_decode($this->metadata, true);
        }
    }

    /**
     * Retorna a URL pública completa do vídeo .mp4
     */
    public function getUrlCompleta()
    {
        $pathOrUrl = !empty($this->video_url) ? $this->video_url : $this->video_path;
        if (empty($pathOrUrl)) {
            return null;
        }

        if (strpos($pathOrUrl, 'http://') === 0 || strpos($pathOrUrl, 'https://') === 0) {
            return $pathOrUrl;
        }

        $caminhoRelativo = ltrim($pathOrUrl, '/');
        if (Yii::$app->has('request') && Yii::$app->get('request') instanceof \yii\web\Request && !empty(Yii::$app->request->hostInfo)) {
            return \yii\helpers\Url::to('@web/' . $caminhoRelativo, true);
        }

        $baseUrl = Yii::$app->params['domain'] ?? 'https://alex-birds.oncode.app.br';
        return rtrim($baseUrl, '/') . '/' . $caminhoRelativo;
    }

    public function getProduto()
    {
        return $this->hasOne(Produto::class, ['id' => 'produto_id']);
    }

    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    /**
     * Retorna o tamanho em bytes do arquivo MP4 do vídeo
     */
    public function getTamanhoBytes(): int
    {
        if (is_array($this->metadata) && isset($this->metadata['tamanho_bytes'])) {
            return (int)$this->metadata['tamanho_bytes'];
        }

        if (!empty($this->video_path)) {
            $absPath = \Yii::getAlias('@app/web/') . ltrim($this->video_path, '/');
            if (file_exists($absPath)) {
                return (int)filesize($absPath);
            }
        }
        return 0;
    }

    /**
     * Retorna o tamanho formatado em KB ou MB (ex: 1.25 MB ou 850 KB)
     */
    public function getTamanhoFormatado(): string
    {
        $bytes = $this->getTamanhoBytes();
        if ($bytes <= 0) {
            return 'N/A';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
        }
        return number_format($bytes / 1024, 1, ',', '.') . ' KB';
    }

    /**
     * Gera a string descritiva formatada das opções/recursos utilizados.
     * Exemplo: FMTO-1080X1080,LAYOUT-Modern Dark,COR-Rose Pink,ESTILO-Geométrico,EFEITO-Fogos de Artifício
     *
     * @param array $meta
     * @return string
     */
    public static function gerarResumoRecursosTexto($meta = []): string
    {
        $formatoRaw = strtolower($meta['formato'] ?? 'stories');
        $fmtoStr = ($formatoRaw === 'feed' || $formatoRaw === '1:1') ? 'FMTO-1080X1080' : 'FMTO-1080X1920';

        $templateMap = [
            'modern_dark' => 'Modern Dark',
            'vibrant_gradient' => 'Vibrant Gradient',
            'minimalist_light' => 'Minimalist Light',
            'neon_promo' => 'Neon Promo',
            'full_bleed_banner' => 'Foto em Tela Cheia',
            'bold_banner' => 'Bold Banner',
        ];
        $templateRaw = $meta['template'] ?? 'modern_dark';
        $layoutVal = $templateMap[$templateRaw] ?? 'Modern Dark';

        $corMap = [
            'dark' => 'Dark Slate',
            'ocean' => 'Ocean Blue',
            'emerald' => 'Emerald Green',
            'purple' => 'Purple Sunset',
            'sunset' => 'Sunset Orange',
            'rose' => 'Rose Pink',
            'gold' => 'Premium Gold',
        ];
        $corRaw = $meta['cor_tema'] ?? $meta['corTema'] ?? 'dark';
        $corVal = $corMap[$corRaw] ?? 'Dark Slate';

        $fundoMap = [
            'gradient' => 'Gradiente',
            'mesh' => 'Mesh Fluid',
            'geometric' => 'Geométrico',
            'grid' => 'Grid Pontos',
        ];
        $fundoRaw = $meta['fundo_estilo'] ?? $meta['fundoEstilo'] ?? 'gradient';
        $estiloVal = $fundoMap[$fundoRaw] ?? 'Gradiente';

        $efeitoMap = [
            'none' => 'Sem Efeito',
            'fireworks' => 'Fogos de Artifício',
            'confetti' => 'Confetes Festa',
            'sparks' => 'Faíscas & Neons',
            'stars' => 'Estrelas & Glow',
            'hearts' => 'Corações',
            'baby_kids' => 'Bebê Risonho & Kids',
            'flowers' => 'Flores & Margaridas',
            'paws' => 'Patas de Pet',
            'balloons' => 'Balões Pastel',
            'gifts' => 'Caixas de Presente',
            'christmas' => 'Natal & Festas',
            'birthday' => 'Aniversário & Bolo',
            'fashion' => 'Moda & Fashion',
            'valentines' => 'Dia dos Namorados',
            'shoes' => 'Sapatos & Calçados',
            'handbags' => 'Bolsas & Acessórios',
            'sweets' => 'Doces & Confeitos',
            'shirts' => 'Blusas & Camisas',
            'jeans' => 'Calças Jeans',
            'sneakers' => 'Tênis Sneakers',
            'woman' => 'Mulher Kawaii',
            'man' => 'Homem Kawaii',
        ];
        $efeitoRaw = $meta['efeito_visual'] ?? $meta['efeitoVisual'] ?? 'none';
        $efeitoVal = $efeitoMap[$efeitoRaw] ?? 'Sem Efeito';

        return "{$fmtoStr},LAYOUT-{$layoutVal},COR-{$corVal},ESTILO-{$estiloVal},EFEITO-{$efeitoVal}";
    }

    /**
     * Retorna o resumo dos recursos gravado no banco reavaliando dinamicamente os parâmetros
     */
    public function getResumoRecursosFormatted(): string
    {
        $meta = is_array($this->metadata) ? $this->metadata : (json_decode($this->metadata ?? '{}', true) ?: []);
        $meta['formato'] = $meta['formato'] ?? $this->formato ?? 'stories';
        return self::gerarResumoRecursosTexto($meta);
    }
}
