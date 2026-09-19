<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Política de Privacidade — Pulse SaaS';
?>

<div class="min-h-screen bg-slate-900 text-slate-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto bg-slate-800/80 border border-slate-700/80 rounded-3xl p-6 sm:p-10 shadow-2xl backdrop-blur-xl">
        
        <!-- Header -->
        <div class="border-b border-slate-700/70 pb-8 mb-8 text-center sm:text-left flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 text-xs font-bold uppercase tracking-wider mb-2">
                    <span>🛡️ Conformidade LGPD & Meta Platform</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">Política de Privacidade</h1>
                <p class="text-sm text-slate-400 mt-1">Última atualização: 19 de Setembro de 2026</p>
            </div>
            <a href="<?= Url::to(['/site/index']) ?>" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-xl text-xs font-bold transition shadow">
                ← Voltar ao Início
            </a>
        </div>

        <!-- Conteúdo -->
        <div class="prose prose-invert max-w-none space-y-8 text-slate-300 leading-relaxed text-sm sm:text-base">
            
            <section>
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="text-purple-400">1.</span> Identificação do Controlador de Dados
                </h2>
                <p class="mt-2">
                    Esta Política de Privacidade aplica-se à plataforma <strong>Pulse SaaS</strong>, operada e controlada pela empresa <strong>Only-code</strong>, inscrita no CNPJ sob o nº <strong>47.037.952/0001-43</strong>, com sede em Recife/PE. Nosso compromisso é salvaguardar a privacidade e proteger os dados pessoais de lojistas, parceiros, afiliados e usuários finais em total conformidade com a <strong>Lei Geral de Proteção de Dados (Lei nº 13.709/2018 - LGPD)</strong> e com os <strong>Termos da Plataforma Meta</strong> e <strong>TikTok for Developers</strong>.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="text-purple-400">2.</span> Dados Coletados e Finalidades
                </h2>
                <p class="mt-2">
                    Coletamos e processamos exclusivamente os dados estritamente necessários para o fornecimento dos serviços de gestão comercial e marketing automatizado da plataforma:
                </p>
                <ul class="list-disc list-inside mt-3 space-y-2 text-slate-300 ml-2">
                    <li><strong>Dados de Cadastro da Loja e Usuário:</strong> Nome completo, e-mail, telefone/WhatsApp, CPF/CNPJ, dados de faturamento e preferências da loja.</li>
                    <li><strong>Dados de Integração com Redes Sociais (Meta & TikTok):</strong> Quando o lojista ou afiliado conecta voluntariamente suas contas via OAuth 2.0:
                        <ul class="list-circle list-inside ml-6 mt-1 space-y-1 text-slate-400">
                            <li>Identificador da Página do Facebook (`page_id`) e nome público da Página.</li>
                            <li>Identificador da Conta Profissional do Instagram (`instagram_business_id`) associada.</li>
                            <li>Tokens de acesso e renovação com permissões restritas a publicação de mídia (`pages_manage_posts`, `instagram_content_publish`).</li>
                            <li>No TikTok: Identificador aberto (`open_id`) e perfil básico de criador para despacho de conteúdo.</li>
                        </ul>
                    </li>
                    <li><strong>Mídias e Catálogo:</strong> Fotos, cards de produtos, encartes promocionais e vídeos gerados pelos próprios usuários para publicação.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="text-purple-400">3.</span> Como Utilizamos os Dados da Meta (Facebook & Instagram)
                </h2>
                <p class="mt-2">
                    Nossa integração com a <strong>Meta Graph API</strong> e <strong>Instagram Content Publishing API</strong> segue estritamente o princípio da minimização de dados:
                </p>
                <div class="bg-slate-900/80 border border-slate-700/70 p-4 rounded-2xl mt-3 space-y-2">
                    <p class="text-sm">🎯 <strong>Finalidade Exclusiva:</strong> Os dados de autenticação são utilizados unicamente para publicar na Página do Facebook e no perfil do Instagram os cards promocionais, fotos (Feed 1:1), carrosséis, Reels e Stories expressamente aprovados pelo usuário no Estúdio de Criação.</p>
                    <p class="text-sm">🚫 <strong>Não Comercialização:</strong> Em nenhuma hipótese vendemos, alugamos ou compartilhamos dados de contas sociais ou métricas de clientes com terceiros para fins de publicidade direcionada.</p>
                    <p class="text-sm">🔒 <strong>Segurança em Repouso:</strong> Todos os tokens de acesso e chaves de renovação são armazenados em banco de dados seguro criptografados com o algoritmo <strong>AES-256</strong>.</p>
                </div>
            </section>

            <section>
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="text-purple-400">4.</span> Compartilhamento de Dados
                </h2>
                <p class="mt-2">
                    O compartilhamento de dados restringe-se aos provedores de infraestrutura essenciais para a operação do SaaS (servidores de nuvem seguros, gateways de pagamento e provedores oficiais de API — Meta Platforms, Inc. e TikTok Inc.), sempre sob termos estritos de confidencialidade e segurança.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="text-purple-400">5.</span> Direitos do Titular de Dados (LGPD)
                </h2>
                <p class="mt-2">
                    Em cumprimento ao Art. 18 da LGPD, garantimos a você os seguintes direitos:
                </p>
                <ul class="list-disc list-inside mt-2 space-y-1 text-slate-300 ml-2">
                    <li>Confirmação da existência de tratamento e acesso aos dados.</li>
                    <li>Correção de dados incompletos ou desatualizados.</li>
                    <li>Revogação imediata de conexões com redes sociais a qualquer momento.</li>
                    <li>Exclusão definitiva de dados pessoais mediante requisição expressa.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="text-purple-400">6.</span> Exclusão de Dados e Revogação de Acesso
                </h2>
                <p class="mt-2">
                    O usuário pode desconectar suas contas sociais a qualquer momento com um único clique no painel do sistema (menu <em>Marketing Social → Desconectar</em>). Além disso, disponibilizamos uma página e mecanismo automatizado de requisição e confirmação de exclusão:
                </p>
                <div class="mt-3">
                    <a href="<?= Url::to(['/site/exclusao-dados']) ?>" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-bold text-xs rounded-xl transition shadow">
                        <span>🗑️</span> Acessar Página de Instruções e Exclusão de Dados
                    </a>
                </div>
            </section>

            <section>
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="text-purple-400">7.</span> Canal de Contato do Encarregado (DPO)
                </h2>
                <p class="mt-2">
                    Para esclarecimento de dúvidas, solicitações sobre privacidade ou exercício de direitos sob a LGPD, entre em contato com nosso time de privacidade:
                </p>
                <div class="mt-3 p-4 bg-slate-900/60 rounded-xl border border-slate-700/60 text-sm">
                    <p><strong>Encarregado de Proteção de Dados:</strong> Only-code / Pulse Privacy Team</p>
                    <p><strong>E-mail direto:</strong> <a href="mailto:only.code.cru@gmail.com" class="text-purple-400 hover:underline">only.code.cru@gmail.com</a></p>
                    <p><strong>CNPJ:</strong> 47.037.952/0001-43 — Recife, PE, Brasil</p>
                </div>
            </section>

        </div>

        <!-- Footer -->
        <div class="mt-12 pt-6 border-t border-slate-700/70 text-center text-xs text-slate-400">
            &copy; <?= date('Y') ?> Only-code. Todos os direitos reservados. Pulse SaaS &bull; 
            <a href="<?= Url::to(['/site/termos-de-uso']) ?>" class="text-purple-400 hover:underline">Termos de Uso</a> &bull; 
            <a href="<?= Url::to(['/site/exclusao-dados']) ?>" class="text-purple-400 hover:underline">Exclusão de Dados</a>
        </div>

    </div>
</div>
