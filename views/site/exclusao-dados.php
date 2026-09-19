<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Instruções e Solicitação de Exclusão de Dados — Pulse SaaS';
$confirmationId = Yii::$app->request->get('id');
?>

<div class="min-h-screen bg-slate-900 text-slate-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto bg-slate-800/80 border border-slate-700/80 rounded-3xl p-6 sm:p-10 shadow-2xl backdrop-blur-xl">
        
        <!-- Header -->
        <div class="border-b border-slate-700/70 pb-8 mb-8 text-center sm:text-left flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-500/10 text-red-400 text-xs font-bold uppercase tracking-wider mb-2">
                    <span>🗑️ Meta Platform Data Deletion & LGPD</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">Exclusão de Dados do Usuário</h1>
                <p class="text-sm text-slate-400 mt-1">Orientações de remoção de dados da Meta (Facebook / Instagram) e do Pulse</p>
            </div>
            <a href="<?= Url::to(['/site/index']) ?>" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-xl text-xs font-bold transition shadow">
                ← Voltar ao Início
            </a>
        </div>

        <?php if (!empty($confirmationId)): ?>
            <!-- Card de Confirmação quando redirecionado pelo Facebook -->
            <div class="bg-emerald-950/50 border border-emerald-500/40 rounded-2xl p-6 mb-8 text-slate-200">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-2xl flex-shrink-0">
                        ✅
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Solicitação de Exclusão Processada com Sucesso</h3>
                        <p class="text-sm text-slate-300 mt-1">
                            Sua solicitação de remoção de dados de integração com a Meta (Facebook e Instagram) foi recebida e concluída pelo servidor do Pulse.
                        </p>
                        <div class="mt-4 p-3 bg-slate-900/80 rounded-xl border border-slate-700/80 inline-block">
                            <span class="text-xs text-slate-400 block font-mono">Código de Confirmação:</span>
                            <span class="text-sm font-bold font-mono text-emerald-400"><?= Html::encode($confirmationId) ?></span>
                        </div>
                        <p class="text-xs text-slate-400 mt-3">
                            Status atual: <strong>Concluído</strong>. Todos os tokens de acesso e vínculos sociais foram revogados e eliminados dos nossos bancos de dados.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Conteúdo Principal com Instruções Claras -->
        <div class="space-y-8 text-slate-300 text-sm sm:text-base leading-relaxed">
            
            <section>
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="text-red-400">1.</span> Como Funciona a Retenção de Dados no Pulse
                </h2>
                <p class="mt-2">
                    O <strong>Pulse SaaS</strong> (Only-code, CNPJ 47.037.952/0001-43) armazena apenas os tokens de autenticação estritamente necessários para permitir a publicação de mídias que você autoriza no Facebook e Instagram.
                    Não retemos dados pessoais de seus seguidores ou clientes finais das redes sociais.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="text-red-400">2.</span> Métodos para Excluir Seus Dados
                </h2>
                <p class="mt-2">
                    Em conformidade com os <strong>Termos da Plataforma Meta (Seção 4.b)</strong> e o <strong>Art. 18 da LGPD</strong>, você pode excluir seus dados a qualquer momento por qualquer um dos 3 métodos abaixo:
                </p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    
                    <!-- Opção A -->
                    <div class="bg-slate-900/70 border border-slate-700/60 p-5 rounded-2xl flex flex-col justify-between">
                        <div>
                            <div class="text-2xl mb-2">⚡ Opção 1</div>
                            <h3 class="text-base font-bold text-white">Desconectar pelo Pulse</h3>
                            <p class="text-xs text-slate-400 mt-2">
                                Acesse seu painel do Pulse no menu <strong>Marketing Social</strong> e clique no botão <strong>Desconectar</strong> ao lado da sua conta.
                            </p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-slate-800">
                            <span class="text-[11px] font-bold text-emerald-400">Imediato &bull; 1 clique</span>
                        </div>
                    </div>

                    <!-- Opção B -->
                    <div class="bg-slate-900/70 border border-slate-700/60 p-5 rounded-2xl flex flex-col justify-between">
                        <div>
                            <div class="text-2xl mb-2">🌐 Opção 2</div>
                            <h3 class="text-base font-bold text-white">Pelo seu Facebook</h3>
                            <p class="text-xs text-slate-400 mt-2">
                                Vá em <em>Configurações e Privacidade &gt; Configurações &gt; Aplicativos e Sites</em> no seu Facebook, localize o aplicativo <strong>Pulse</strong> e clique em <strong>Remover</strong>.
                            </p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-slate-800">
                            <span class="text-[11px] font-bold text-blue-400">Automático via Meta Callback</span>
                        </div>
                    </div>

                    <!-- Opção C -->
                    <div class="bg-slate-900/70 border border-slate-700/60 p-5 rounded-2xl flex flex-col justify-between">
                        <div>
                            <div class="text-2xl mb-2">✉️ Opção 3</div>
                            <h3 class="text-base font-bold text-white">Solicitação por E-mail</h3>
                            <p class="text-xs text-slate-400 mt-2">
                                Envie um e-mail para <a href="mailto:only.code.cru@gmail.com" class="text-purple-400 hover:underline">only.code.cru@gmail.com</a> solicitando a exclusão de todos os dados do seu tenant.
                            </p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-slate-800">
                            <span class="text-[11px] font-bold text-amber-400">Prazo: até 48 horas</span>
                        </div>
                    </div>

                </div>
            </section>

            <section>
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="text-red-400">3.</span> O que Acontece Após a Exclusão?
                </h2>
                <ul class="list-disc list-inside mt-2 space-y-1.5 text-slate-300 ml-2 text-sm">
                    <li>Todos os tokens de acesso (Access Tokens e Refresh Tokens) são imediatamente apagados do banco de dados.</li>
                    <li>Nenhuma publicação ou comunicação futura poderá ser enviada pelo Pulse para suas redes.</li>
                    <li>As postagens que já foram publicadas em sua Página ou perfil do Instagram permanecem intactas nas plataformas da Meta, ficando sob seu controle direto na respectiva rede.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="text-red-400">4.</span> Informações Técnicas para o Callback da Meta
                </h2>
                <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-4 font-mono text-xs text-slate-400 space-y-1">
                    <p><strong class="text-slate-200">Endpoint Callback URL:</strong> https://catalogos.oncode.app.br/exclusao-dados</p>
                    <p><strong class="text-slate-200">Protocolo:</strong> POST com parâmetro signed_request (SHA-256)</p>
                    <p><strong class="text-slate-200">Resposta:</strong> JSON com confirmation_code e status tracking URL</p>
                </div>
            </section>

        </div>

        <!-- Footer -->
        <div class="mt-12 pt-6 border-t border-slate-700/70 text-center text-xs text-slate-400">
            &copy; <?= date('Y') ?> Only-code. Todos os direitos reservados. Pulse SaaS &bull; 
            <a href="<?= Url::to(['/site/politica-privacidade']) ?>" class="text-purple-400 hover:underline">Política de Privacidade</a> &bull; 
            <a href="<?= Url::to(['/site/termos-de-uso']) ?>" class="text-purple-400 hover:underline">Termos de Uso</a>
        </div>

    </div>
</div>
