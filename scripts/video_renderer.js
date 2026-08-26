const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');
const { spawn } = require('child_process');

function writeFrame(stream, buffer) {
    return new Promise((resolve, reject) => {
        if (!stream || stream.destroyed || stream.writableEnded) {
            return reject(new Error('Stream do FFmpeg não está gravável.'));
        }
        const written = stream.write(buffer);
        if (!written) {
            let cleaned = false;
            const cleanup = () => {
                if (cleaned) return;
                cleaned = true;
                stream.removeListener('drain', onDrain);
                stream.removeListener('error', onError);
                stream.removeListener('close', onClose);
            };
            const onDrain = () => { cleanup(); resolve(); };
            const onError = (err) => { cleanup(); reject(err); };
            const onClose = () => { cleanup(); reject(new Error('Stream do FFmpeg fechada durante a gravação.')); };

            stream.once('drain', onDrain);
            stream.once('error', onError);
            stream.once('close', onClose);
        } else {
            process.nextTick(resolve);
        }
    });
}

async function main() {
    const args = process.argv.slice(2);
    if (args.length < 1) {
        console.error('Uso: node video_renderer.js <caminho_json_payload> [caminho_saida_mp4]');
        process.exit(1);
    }

    const payloadPath = args[0];
    if (!fs.existsSync(payloadPath)) {
        console.error(`Arquivo de payload não encontrado: ${payloadPath}`);
        process.exit(1);
    }

    const payloadRaw = fs.readFileSync(payloadPath, 'utf8');
    const data = JSON.parse(payloadRaw);

    const duracao = parseInt(data.duracao) || 15; // 5, 10 ou 15 segundos
    const formato = (data.formato || 'stories').toLowerCase();
    const width = 1080;
    const height = (formato === 'feed' || formato === '1:1') ? 1080 : 1920;
    const fps = 30;
    const totalFrames = duracao * fps;
    const outputPath = args[1] || data.outputPath || path.join(__dirname, `video_${Date.now()}.mp4`);

    const htmlContent = generateVideoHtmlTemplate(data, duracao);

    const userDataDir = path.join('/tmp', `puppeteer_video_${Date.now()}_${Math.random().toString(36).substring(7)}`);

    let browser;
    try {
        const launchOptions = {
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-crash-reporter',
                '--disable-gpu',
                `--user-data-dir=${userDataDir}`,
                '--font-render-hinting=medium'
            ],
            headless: 'new'
        };

        let executablePath = process.env.PUPPETEER_EXECUTABLE_PATH;

        if (!executablePath) {
            const possiblePaths = [
                '/srv/http/.cache/puppeteer',
                '/root/.cache/puppeteer',
                path.join(process.env.HOME || '', '.cache/puppeteer')
            ];

            for (const cacheDir of possiblePaths) {
                if (fs.existsSync(cacheDir)) {
                    try {
                        const findExecutable = (dir, targetName) => {
                            const files = fs.readdirSync(dir);
                            for (const file of files) {
                                const fullPath = path.join(dir, file);
                                const stat = fs.statSync(fullPath);
                                if (stat.isDirectory()) {
                                    const found = findExecutable(fullPath, targetName);
                                    if (found) return found;
                                } else if (file === targetName && (stat.mode & 0o111)) {
                                    return fullPath;
                                }
                            }
                            return null;
                        };

                        const foundHeadless = findExecutable(cacheDir, 'chrome-headless-shell');
                        if (foundHeadless) {
                            executablePath = foundHeadless;
                            break;
                        }

                        const foundChrome = findExecutable(cacheDir, 'chrome');
                        if (foundChrome) {
                            executablePath = foundChrome;
                            break;
                        }
                    } catch (e) {}
                }
            }
        }

        if (!executablePath) {
            const systemExecutables = [
                '/usr/bin/google-chrome-stable',
                '/usr/bin/google-chrome',
                '/usr/bin/chromium',
                '/usr/bin/chromium-browser',
                '/snap/bin/chromium'
            ];

            for (const sysPath of systemExecutables) {
                if (fs.existsSync(sysPath)) {
                    executablePath = sysPath;
                    break;
                }
            }
        }

        if (executablePath) {
            launchOptions.executablePath = executablePath;
        }

        browser = await puppeteer.launch(launchOptions);
        const page = await browser.newPage();

        await page.setViewport({
            width: width,
            height: height,
            deviceScaleFactor: 1
        });

        await page.setContent(htmlContent, { waitUntil: ['domcontentloaded', 'networkidle0'] });

        await page.evaluate(async () => {
            if (document.fonts) {
                await document.fonts.ready;
            }
        });

        const outputDir = path.dirname(outputPath);
        if (!fs.existsSync(outputDir)) {
            fs.mkdirSync(outputDir, { recursive: true });
        }

        const requestedAudio = data.trilhaSonora || 'promo_bg.mp3';
        const fileName = path.basename(requestedAudio);

        let audioPath = path.isAbsolute(requestedAudio) 
            ? requestedAudio 
            : path.resolve(__dirname, '../web', requestedAudio.replace(/^\//, ''));

        if (!fs.existsSync(audioPath)) {
            audioPath = path.resolve(__dirname, '../web/uploads/audio', fileName);
        }
        if (!fs.existsSync(audioPath)) {
            audioPath = path.resolve(__dirname, '../assets/audio', fileName);
        }
        if (!fs.existsSync(audioPath)) {
            audioPath = path.resolve(__dirname, '../assets/audio/promo_bg.mp3');
        }
        const hasAudio = fs.existsSync(audioPath);
        console.log(`[FFmpeg Audio] Using track: ${audioPath} (exists: ${hasAudio})`);

        // Iniciar Processo FFmpeg via Spawn com MJPEG ultra-rápido
        const ffmpegArgs = [
            '-y',
            '-f', 'image2pipe',
            '-vcodec', 'mjpeg',
            '-r', `${fps}`,
            '-i', '-'
        ];

        if (hasAudio) {
            const fadeStart = Math.max(0, duracao - 1.5);
            ffmpegArgs.push(
                '-stream_loop', '-1',
                '-i', audioPath,
                '-t', `${duracao}`,
                '-filter_complex', `[1:a]afade=t=out:st=${fadeStart}:d=1.5[a]`,
                '-map', '0:v',
                '-map', '[a]',
                '-shortest'
            );
        }

        ffmpegArgs.push(
            '-c:v', 'libx264',
            '-pix_fmt', 'yuv420p',
            '-preset', 'fast',
            '-crf', '22',
            '-movflags', '+faststart',
            outputPath
        );

        const ffmpegProcess = spawn('ffmpeg', ffmpegArgs);
        let ffmpegLogs = '';

        ffmpegProcess.stderr.on('data', (data) => {
            ffmpegLogs += data.toString();
        });

        // Loop de Renderização de Frames com JPEG alta performance e Controle de Drain
        for (let frame = 0; frame < totalFrames; frame++) {
            await page.evaluate((currentFrame, total, fpsVal) => {
                if (typeof window.seekFrame === 'function') {
                    window.seekFrame(currentFrame, total, fpsVal);
                }
            }, frame, totalFrames, fps);

            const buffer = await page.screenshot({
                type: 'jpeg',
                quality: 90,
                fullPage: false,
                omitBackground: false
            });

            // Aguarda o consumo do buffer pelo FFmpeg antes de capturar o próximo frame
            await writeFrame(ffmpegProcess.stdin, buffer);
        }

        ffmpegProcess.stdin.end();

        await new Promise((resolve, reject) => {
            ffmpegProcess.on('close', (code) => {
                if (code === 0 && fs.existsSync(outputPath) && fs.statSync(outputPath).size > 1000) {
                    resolve();
                } else {
                    reject(new Error(`FFmpeg finalizou com código ${code}. Tamanho arquivo: ${fs.existsSync(outputPath) ? fs.statSync(outputPath).size : 0} bytes. Logs: ${ffmpegLogs.slice(-800)}`));
                }
            });
            ffmpegProcess.on('error', (err) => reject(err));
        });

        console.log(JSON.stringify({
            success: true,
            outputPath: outputPath,
            duracao: duracao,
            totalFrames: totalFrames,
            width: width,
            height: height
        }));

    } catch (err) {
        console.error(JSON.stringify({
            success: false,
            error: err.message,
            stack: err.stack
        }));
        process.exit(1);
    } finally {
        if (browser) {
            await browser.close();
        }
        if (fs.existsSync(userDataDir)) {
            try {
                fs.rmSync(userDataDir, { recursive: true, force: true });
            } catch (e) {}
        }
    }
}

/**
 * Paletas de Cores Pré-definidas
 */
const COLOR_PALETTES = {
    dark: {
        primary: '#0E8CE9',
        secondary: '#026EC7',
        bgGradient: 'linear-gradient(135deg, #0F172A 0%, #1E293B 50%, #0F172A 100%)',
        cardBg: 'rgba(255, 255, 255, 0.07)',
        infoBg: 'rgba(15, 23, 42, 0.92)',
        textColor: '#FFFFFF',
        mutedText: '#94A3B8',
        accent: '#38BDF8',
        badgeBg: 'linear-gradient(135deg, #FF3B30 0%, #E11D48 100%)',
        border: 'rgba(255, 255, 255, 0.15)'
    },
    ocean: {
        primary: '#0284C7',
        secondary: '#0369A1',
        bgGradient: 'linear-gradient(135deg, #0C4A6E 0%, #0369A1 50%, #075985 100%)',
        cardBg: 'rgba(255, 255, 255, 0.08)',
        infoBg: 'rgba(12, 74, 110, 0.92)',
        textColor: '#FFFFFF',
        mutedText: '#BAE6FD',
        accent: '#38BDF8',
        badgeBg: 'linear-gradient(135deg, #0284C7 0%, #2563EB 100%)',
        border: 'rgba(186, 230, 253, 0.2)'
    },
    emerald: {
        primary: '#059669',
        secondary: '#047857',
        bgGradient: 'linear-gradient(135deg, #064E3B 0%, #047857 50%, #065F46 100%)',
        cardBg: 'rgba(255, 255, 255, 0.08)',
        infoBg: 'rgba(6, 78, 59, 0.92)',
        textColor: '#FFFFFF',
        mutedText: '#A7F3D0',
        accent: '#34D399',
        badgeBg: 'linear-gradient(135deg, #059669 0%, #10B981 100%)',
        border: 'rgba(167, 243, 208, 0.2)'
    },
    purple: {
        primary: '#8B5CF6',
        secondary: '#7C3AED',
        bgGradient: 'linear-gradient(135deg, #2E1065 0%, #4C1D95 50%, #1E1B4B 100%)',
        cardBg: 'rgba(255, 255, 255, 0.08)',
        infoBg: 'rgba(30, 27, 75, 0.92)',
        textColor: '#FFFFFF',
        mutedText: '#DDD6FE',
        accent: '#A78BFA',
        badgeBg: 'linear-gradient(135deg, #EC4899 0%, #D946EF 100%)',
        border: 'rgba(221, 214, 254, 0.2)'
    },
    sunset: {
        primary: '#EA580C',
        secondary: '#C2410C',
        bgGradient: 'linear-gradient(135deg, #431407 0%, #7C2D12 50%, #9A3412 100%)',
        cardBg: 'rgba(255, 255, 255, 0.08)',
        infoBg: 'rgba(67, 20, 7, 0.92)',
        textColor: '#FFFFFF',
        mutedText: '#FFEDD5',
        accent: '#FB923C',
        badgeBg: 'linear-gradient(135deg, #EA580C 0%, #E11D48 100%)',
        border: 'rgba(255, 237, 213, 0.2)'
    },
    rose: {
        primary: '#E11D48',
        secondary: '#BE123C',
        bgGradient: 'linear-gradient(135deg, #4C0519 0%, #881337 50%, #9F1239 100%)',
        cardBg: 'rgba(255, 255, 255, 0.08)',
        infoBg: 'rgba(76, 5, 25, 0.92)',
        textColor: '#FFFFFF',
        mutedText: '#FECDD3',
        accent: '#F43F5E',
        badgeBg: 'linear-gradient(135deg, #E11D48 0%, #DB2777 100%)',
        border: 'rgba(254, 205, 211, 0.2)'
    },
    gold: {
        primary: '#F59E0B',
        secondary: '#D97706',
        bgGradient: 'linear-gradient(135deg, #451A03 0%, #78350F 50%, #1C0A00 100%)',
        cardBg: 'rgba(255, 255, 255, 0.08)',
        infoBg: 'rgba(28, 10, 0, 0.92)',
        textColor: '#FFFFFF',
        mutedText: '#FEF3C7',
        accent: '#FBBF24',
        badgeBg: 'linear-gradient(135deg, #EF4444 0%, #DC2626 100%)',
        border: 'rgba(254, 243, 199, 0.2)'
    }
};

function generateVideoHtmlTemplate(data, duracao) {
    const templateStyle = (data.template || 'modern_dark').toLowerCase();
    if (templateStyle === 'full_bleed_banner' || templateStyle === 'full_bleed') {
        return generateFullBleedVideoHtmlTemplate(data, duracao);
    }
    const corTema = (data.corTema || 'dark').toLowerCase();
    const p = data.produto || {};
    const l = data.loja || {};
    const palette = COLOR_PALETTES[corTema] || COLOR_PALETTES.dark;

    const nomeProduto = escapeHtml(p.nome || 'PRODUTO EM OFERTA');
    const descricao = escapeHtml(p.descricao || '');
    const marca = escapeHtml(p.marca || '');
    const precoOriginal = p.precoOriginal || '';
    const precoPromocional = p.precoPromocional || 'R$ 0,00';
    const emPromocao = !!p.emPromocao;
    const badgeTexto = p.badgeTexto || (emPromocao ? 'OFERTA' : 'DESTAQUE');
    const parcelamento = escapeHtml(p.parcelamento || '');

    const fotos = Array.isArray(p.fotosBase64) && p.fotosBase64.length > 0 ? p.fotosBase64 : [
        'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400" viewBox="0 0 24 24" fill="none" stroke="%23cccccc" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>'
    ];

    const nomeLoja = escapeHtml(l.nome || 'PULSE STORE');
    const logoLoja = l.logoBase64 || '';
    const telefone = escapeHtml(l.telefone || '');
    const site = escapeHtml(l.site || '');

    const fundoEstilo = (data.fundoEstilo || 'gradient').toLowerCase();
    let bgStyle = palette.bgGradient;
    if (fundoEstilo === 'mesh') {
        bgStyle = `radial-gradient(at 0% 0%, ${palette.primary}99 0px, transparent 50%), radial-gradient(at 100% 0%, ${palette.secondary}99 0px, transparent 50%), radial-gradient(at 100% 100%, ${palette.accent}55 0px, transparent 50%), ${palette.bgGradient}`;
    } else if (fundoEstilo === 'geometric') {
        bgStyle = `repeating-linear-gradient(45deg, ${palette.primary}22 0, ${palette.primary}22 20px, transparent 20px, transparent 40px), ${palette.bgGradient}`;
    } else if (fundoEstilo === 'grid') {
        bgStyle = `radial-gradient(rgba(255, 255, 255, 0.18) 2px, transparent 2px) 0 0 / 30px 30px, ${palette.bgGradient}`;
    }

    return `<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700;800;900&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            width: 1080px; height: 1920px;
            font-family: 'Inter', sans-serif;
            background: ${bgStyle};
            color: ${palette.textColor};
            display: flex; flex-direction: column; justify-content: space-between;
            overflow: hidden; position: relative;
        }

        /* Ambient Glows */
        .glow-1 { position: absolute; top: -150px; right: -150px; width: 700px; height: 700px; background: radial-gradient(circle, ${palette.primary}77 0%, rgba(0,0,0,0) 70%); border-radius: 50%; filter: blur(70px); }
        .glow-2 { position: absolute; bottom: -150px; left: -150px; width: 800px; height: 800px; background: radial-gradient(circle, ${palette.secondary}66 0%, rgba(0,0,0,0) 70%); border-radius: 50%; filter: blur(90px); }

        .container { position: relative; z-index: 2; width: 100%; height: 100%; padding: 80px 60px 70px 60px; display: flex; flex-direction: column; justify-content: space-between; }

        /* Header */
        .header { display: flex; align-items: center; justify-content: space-between; width: 100%; height: 110px; padding-bottom: 20px; border-bottom: 1px solid ${palette.border}; opacity: 0; transform: translateY(-30px); transition: all 0.5s ease; }
        .store-brand { display: flex; align-items: center; gap: 20px; }
        .store-logo { max-height: 75px; max-width: 220px; object-fit: contain; }
        .store-name { font-family: 'Outfit', sans-serif; font-size: 34px; font-weight: 800; text-transform: uppercase; color: #FFFFFF; }
        .promo-badge { background: ${palette.badgeBg}; color: #FFFFFF; font-family: 'Outfit', sans-serif; font-weight: 900; font-size: 28px; padding: 10px 24px; border-radius: 50px; box-shadow: 0 8px 25px rgba(255, 59, 48, 0.5); text-transform: uppercase; }

        /* Stage Photo Area */
        .stage { position: relative; flex: 1; display: flex; align-items: center; justify-content: center; margin: 40px 0; }
        .image-card { position: relative; width: 100%; height: 920px; background: ${palette.cardBg}; backdrop-filter: blur(20px); border: 1px solid ${palette.border}; border-radius: 40px; box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6); display: flex; align-items: center; justify-content: center; padding: 40px; overflow: hidden; opacity: 0; transform: scale(0.85); transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
        .product-image { max-width: 90%; max-height: 90%; object-fit: contain; filter: drop-shadow(0 20px 35px rgba(0,0,0,0.6)); transition: transform 0.3s ease, opacity 0.3s ease; }
        .brand-tag { position: absolute; top: 30px; left: 30px; background: rgba(15, 23, 42, 0.85); border: 1px solid ${palette.border}; color: ${palette.accent}; font-size: 22px; font-weight: 800; padding: 10px 24px; border-radius: 14px; text-transform: uppercase; letter-spacing: 1px; }
        .photo-badge { position: absolute; bottom: 30px; right: 30px; background: rgba(15, 23, 42, 0.85); border: 1px solid ${palette.border}; color: #FFFFFF; font-size: 20px; font-weight: 700; padding: 8px 18px; border-radius: 12px; font-family: 'Outfit', sans-serif; }

        /* Info Card */
        .info-card { background: ${palette.infoBg}; backdrop-filter: blur(30px); border: 1px solid ${palette.border}; border-radius: 36px; padding: 36px 40px; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5); display: flex; flex-direction: column; gap: 16px; opacity: 0; transform: translateY(40px); transition: all 0.5s ease; }
        .product-title { font-family: 'Outfit', sans-serif; font-size: 44px; font-weight: 800; line-height: 1.2; color: #FFFFFF; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .price-section { display: flex; align-items: flex-end; justify-content: space-between; margin-top: 6px; }
        .original-price { font-size: 26px; color: ${palette.mutedText}; text-decoration: line-through; font-weight: 600; }
        .current-price-label { font-size: 20px; font-weight: 700; color: ${palette.accent}; text-transform: uppercase; letter-spacing: 1px; }
        .current-price { font-family: 'Outfit', sans-serif; font-size: 68px; font-weight: 900; color: ${palette.accent}; line-height: 1; letter-spacing: -1px; text-shadow: 0 0 25px ${palette.accent}66; }
        .installment-badge { background: rgba(255,255,255,0.08); border: 1px solid ${palette.border}; color: ${palette.accent}; font-size: 22px; font-weight: 700; padding: 12px 24px; border-radius: 16px; align-self: flex-end; }

        /* Footer CTA */
        .footer { display: flex; align-items: center; justify-content: space-between; margin-top: 25px; padding-top: 15px; border-top: 1px solid ${palette.border}; font-size: 22px; color: ${palette.mutedText}; font-weight: 600; opacity: 0; transform: translateY(20px); transition: all 0.5s ease; }
        .cta-pill { background: ${palette.primary}; color: #FFFFFF; font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 900; padding: 12px 28px; border-radius: 14px; text-transform: uppercase; box-shadow: 0 8px 25px ${palette.primary}88; animation: pulseCta 1.5s infinite; }

        .visible { opacity: 1 !important; transform: none !important; }
        .pulse-scale { transform: scale(1.05) !important; }
    </style>
</head>
<body>
    <div class="glow-1"></div>
    <div class="glow-2"></div>
    <div class="container">
        <div class="header" id="elemHeader">
            <div class="store-brand">
                ${logoLoja ? `<img class="store-logo" src="${logoLoja}">` : ''}
                <div class="store-name">${nomeLoja}</div>
            </div>
            ${badgeTexto ? `<div class="promo-badge">${badgeTexto}</div>` : ''}
        </div>
        <div class="stage">
            <div class="image-card" id="elemImgCard">
                ${marca ? `<div class="brand-tag">${marca}</div>` : ''}
                ${fotos.length > 1 ? `<div class="photo-badge" id="elemPhotoBadge">📷 1 / ${fotos.length}</div>` : ''}
                <img class="product-image" id="elemProductImg" src="${fotos[0]}">
            </div>
        </div>
        <div class="info-card" id="elemInfoCard">
            <div class="product-title">${nomeProduto}</div>
            <div class="price-section">
                <div>
                    ${emPromocao && precoOriginal ? `<div class="original-price">De: ${precoOriginal}</div>` : ''}
                    <div class="current-price-label">${emPromocao ? 'Por apenas' : 'Preço Especial'}</div>
                    <div class="current-price" id="elemPrice">${precoPromocional}</div>
                </div>
                ${parcelamento ? `<div class="installment-badge">${parcelamento}</div>` : ''}
            </div>
        </div>
        <div class="footer" id="elemFooter">
            <div>📞 ${telefone || 'Consulte no WhatsApp'}</div>
            <div class="cta-pill">PEÇA JÁ NO WHATSAPP</div>
        </div>
    </div>

    <script>
        const fotosList = ${JSON.stringify(fotos)};
        const duracaoTotal = ${duracao};

        const elemHeader = document.getElementById('elemHeader');
        const elemImgCard = document.getElementById('elemImgCard');
        const elemProductImg = document.getElementById('elemProductImg');
        const elemInfoCard = document.getElementById('elemInfoCard');
        const elemPrice = document.getElementById('elemPrice');
        const elemFooter = document.getElementById('elemFooter');

        window.seekFrame = function(frame, totalFrames, fps) {
            const progress = frame / totalFrames; // 0.0 a 1.0
            const currentTime = frame / fps; // em segundos

            // 1. Apresentação do Header (0.2s)
            if (currentTime >= 0.2) {
                elemHeader.classList.add('visible');
            }

            // 2. Apresentação do Card da Imagem (0.4s)
            if (currentTime >= 0.4) {
                elemImgCard.classList.add('visible');
            }

            // 3. Efeito Ken-Burns Zoom Suave na Foto
            const zoomFactor = 1 + (progress * 0.12);
            elemProductImg.style.transform = 'scale(' + zoomFactor + ')';

            // 4. Troca dinâmica de Fotos na Galeria se houver mais de 1 foto
            if (fotosList.length > 1) {
                const tempoPorFoto = duracaoTotal / fotosList.length;
                let fotoIndex = Math.min(Math.floor(currentTime / tempoPorFoto), fotosList.length - 1);

                if (elemProductImg.src !== fotosList[fotoIndex]) {
                    elemProductImg.src = fotosList[fotoIndex];
                    const elemPhotoBadge = document.getElementById('elemPhotoBadge');
                    if (elemPhotoBadge) {
                        elemPhotoBadge.innerText = '📷 ' + (fotoIndex + 1) + ' / ' + fotosList.length;
                    }
                }
            }

            // 5. Apresentação do Card de Preços (0.8s)
            if (currentTime >= 0.8) {
                elemInfoCard.classList.add('visible');
            }

            // 6. Explosão do Preço e Footer CTA (1.2s em diante)
            if (currentTime >= 1.2) {
                elemFooter.classList.add('visible');
            }

            // 7. Pulso do preço nos últimos segundos da promoção
            if (currentTime > (duracaoTotal - 3.0)) {
                const pulse = 1 + Math.sin((currentTime - (duracaoTotal - 3.0)) * 6) * 0.05;
                elemPrice.style.transform = 'scale(' + pulse + ')';
            }
        };
    </script>
</body>
</html>`;
}

function generateFullBleedVideoHtmlTemplate(data, duracao) {
    const corTema = (data.corTema || 'dark').toLowerCase();
    const p = data.produto || {};
    const l = data.loja || {};
    const palette = COLOR_PALETTES[corTema] || COLOR_PALETTES.dark;

    const nomeProduto = escapeHtml(p.nome || 'PRODUTO EM OFERTA');
    const marca = escapeHtml(p.marca || 'TUDO SOBRE');
    const precoPromocional = p.precoPromocional || 'R$ 0,00';
    const emPromocao = !!p.emPromocao;
    const fotos = Array.isArray(p.fotosBase64) && p.fotosBase64.length > 0 ? p.fotosBase64 : [
        'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400" viewBox="0 0 24 24" fill="none" stroke="%23cccccc" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>'
    ];

    const nomeLoja = escapeHtml(l.nome || 'PULSE STORE');
    const telefone = escapeHtml(l.telefone || '(81) 9386-1026');

    const topBgColor = palette.primary || '#6b8e23';
    const bottomBgColor = palette.badgeBg ? palette.badgeBg : '#ff5722';

    return `<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800;900&family=Inter:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            width: 1080px; height: 1920px;
            font-family: 'Outfit', sans-serif;
            background: #000000;
            overflow: hidden; position: relative;
        }

        /* Full Bleed Background Container */
        .full-bg-container {
            position: absolute; top: 0; left: 0;
            width: 100%; height: 100%;
            overflow: hidden; z-index: 1;
        }

        .full-bg-image {
            width: 100%; height: 100%;
            object-fit: cover; object-position: center;
            transform: scale(1.0);
            transition: transform 0.3s ease;
        }

        /* Photo Counter Badge */
        .photo-badge {
            position: absolute; top: 220px; right: 40px; z-index: 20;
            background: rgba(0, 0, 0, 0.75); border: 1px solid rgba(255, 255, 255, 0.3);
            color: #ffffff; font-size: 24px; font-weight: 700; padding: 10px 22px; border-radius: 14px;
        }

        /* Top Overlay Banner */
        .top-banner {
            position: absolute; top: 0; left: 0; right: 0; z-index: 10;
            background: ${topBgColor}; color: #ffffff;
            padding: 45px 40px 30px; text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
            transform: translateY(-100%); transition: transform 0.5s ease;
        }

        .top-subtitle {
            font-family: 'Inter', sans-serif; font-size: 28px; font-weight: 800;
            letter-spacing: 2px; text-transform: uppercase; color: rgba(255, 255, 255, 0.95); margin-bottom: 6px;
        }

        .top-title {
            font-size: 64px; font-weight: 900; text-transform: uppercase;
            letter-spacing: 1px; color: #ffffff; line-height: 1.1; text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        /* Bottom Overlay Banner */
        .bottom-banner {
            position: absolute; bottom: 0; left: 0; right: 0; z-index: 10;
            background: ${bottomBgColor}; padding: 40px 50px 45px;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 -10px 30px rgba(0,0,0,0.4);
            transform: translateY(100%); transition: transform 0.5s ease;
        }

        .bottom-info { display: flex; flex-direction: column; gap: 4px; }
        .store-brand-name { font-size: 40px; font-weight: 900; color: #000000; text-transform: uppercase; letter-spacing: -0.5px; line-height: 1; }
        .main-headline { font-size: 48px; font-weight: 900; color: #ffffff; text-transform: uppercase; line-height: 1.1; max-width: 650px; }
        .price-badge-text { font-size: 56px; font-weight: 900; color: #ffffff; line-height: 1; }

        .whatsapp-contact-box {
            display: flex; align-items: center; gap: 16px;
            background: rgba(0, 0, 0, 0.15); padding: 12px 24px; border-radius: 60px;
        }
        .whatsapp-icon { width: 64px; height: 64px; }
        .phone-number { font-size: 46px; font-weight: 900; color: #000000; font-family: 'Inter', sans-serif; letter-spacing: -1px; }

        .visible { transform: translateY(0) !important; }
    </style>
</head>
<body>
    <div class="full-bg-container">
        <img class="full-bg-image" id="elemFullImg" src="${fotos[0]}" alt="Background">
        ${fotos.length > 1 ? `<div class="photo-badge" id="elemPhotoBadge">📷 1 / ${fotos.length}</div>` : ''}
    </div>

    <div class="top-banner" id="elemTopBanner">
        <div class="top-subtitle">${marca ? marca : 'TUDO SOBRE'}</div>
        <div class="top-title">${nomeProduto}</div>
    </div>

    <div class="bottom-banner" id="elemBottomBanner">
        <div class="bottom-info">
            <div class="store-brand-name">${nomeLoja}</div>
            <div class="main-headline">${emPromocao ? 'POR APENAS' : 'OFERTA ESPECIAL'}</div>
            <div class="price-badge-text" id="elemPrice">${precoPromocional}</div>
        </div>

        <div class="whatsapp-contact-box">
            <svg class="whatsapp-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.982-1.385A9.956 9.956 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2z" fill="#25D366"/>
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414-.074-.124-.272-.198-.57-.346z" fill="#FFFFFF"/>
            </svg>
            <span class="phone-number">${telefone}</span>
        </div>
    </div>

    <script>
        const fotosList = ${JSON.stringify(fotos)};
        const duracaoTotal = ${duracao};

        const elemTopBanner = document.getElementById('elemTopBanner');
        const elemBottomBanner = document.getElementById('elemBottomBanner');
        const elemFullImg = document.getElementById('elemFullImg');

        window.seekFrame = function(frame, totalFrames, fps) {
            const progress = frame / totalFrames; // 0.0 a 1.0
            const currentTime = frame / fps; // em segundos

            // 1. Entrada dos Banners Topo e Rodapé
            if (currentTime >= 0.2) {
                elemTopBanner.classList.add('visible');
            }
            if (currentTime >= 0.4) {
                elemBottomBanner.classList.add('visible');
            }

            // 2. Zoom Ken-Burns na Foto de Fundo
            const zoomFactor = 1 + (progress * 0.15);
            elemFullImg.style.transform = 'scale(' + zoomFactor + ')';

            // 3. Troca de Fotos na Galeria
            if (fotosList.length > 1) {
                const tempoPorFoto = duracaoTotal / fotosList.length;
                let fotoIndex = Math.min(Math.floor(currentTime / tempoPorFoto), fotosList.length - 1);

                if (elemFullImg.src !== fotosList[fotoIndex]) {
                    elemFullImg.src = fotosList[fotoIndex];
                    const elemPhotoBadge = document.getElementById('elemPhotoBadge');
                    if (elemPhotoBadge) {
                        elemPhotoBadge.innerText = '📷 ' + (fotoIndex + 1) + ' / ' + fotosList.length;
                    }
                }
            }
        };
    </script>
</body>
</html>`;
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

main();
