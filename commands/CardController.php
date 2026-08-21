<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\modules\vendas\services\CardGeneratorService;

/**
 * Comando de console para geração automatizada de cards de produtos.
 */
class CardController extends Controller
{
    public $template = 'modern_dark';
    public $cor = 'dark';
    public $fundo = 'gradient';
    public $imagemFundo = null;

    public function options($actionID)
    {
        return array_merge(parent::options($actionID), [
            'template',
            'cor',
            'fundo',
            'imagemFundo'
        ]);
    }

    /**
     * Gera um card profissional para o produto especificado.
     * Uso: php yii card/generate <produto_id> [feed|stories] --template=neon_promo --cor=purple --fundo=mesh
     *
     * @param string $id ID (UUID) do produto
     * @param string $formato Formato do card: 'feed' (padrão) ou 'stories'
     * @return int ExitCode
     */
    public function actionGenerate($id, $formato = 'feed')
    {
        $this->stdout("Iniciando geração de card para o produto ID: {$id} (Formato: {$formato}, Template: {$this->template}, Cor: {$this->cor})...\n");

        $options = [
            'template' => $this->template,
            'corTema' => $this->cor,
            'fundoEstilo' => $this->fundo,
            'imagemFundo' => $this->imagemFundo
        ];

        try {
            $service = new CardGeneratorService();
            $card = $service->gerarCard($id, $formato, $options);

            $this->stdout("✅ Card gerado com sucesso!\n", \yii\helpers\Console::FG_GREEN);
            $this->stdout("ID do Card: " . $card->id . "\n");
            $this->stdout("Template: " . ($card->metadata['template'] ?? 'N/A') . "\n");
            $this->stdout("Cor Tema: " . ($card->metadata['cor_tema'] ?? 'N/A') . "\n");
            $this->stdout("Caminho: " . $card->card_path . "\n");
            $this->stdout("URL: " . $card->getUrlCompleta() . "\n");

            return ExitCode::OK;
        } catch (\Exception $e) {
            $this->stderr("❌ Erro ao gerar card: " . $e->getMessage() . "\n", \yii\helpers\Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}
