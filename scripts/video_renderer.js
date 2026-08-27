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

    const isFeed = (data.formato || 'stories').toLowerCase() === 'feed' || (data.formato || '').toLowerCase() === '1:1';
    const totalHeight = isFeed ? 1080 : 1920;

    return `<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700;800;900&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            width: 1080px; height: ${totalHeight}px;
            font-family: 'Inter', sans-serif;
            background: ${bgStyle};
            color: ${palette.textColor};
            display: flex; flex-direction: column; justify-content: space-between;
            overflow: hidden; position: relative;
        }

        /* Ambient Glows */
        .glow-1 { position: absolute; top: -150px; right: -150px; width: 700px; height: 700px; background: radial-gradient(circle, ${palette.primary}77 0%, rgba(0,0,0,0) 70%); border-radius: 50%; filter: blur(70px); }
        .glow-2 { position: absolute; bottom: -150px; left: -150px; width: 800px; height: 800px; background: radial-gradient(circle, ${palette.secondary}66 0%, rgba(0,0,0,0) 70%); border-radius: 50%; filter: blur(90px); }

        .container { position: relative; z-index: 2; width: 100%; height: 100%; padding: ${isFeed ? '18px 24px' : '45px 40px 35px 40px'}; display: flex; flex-direction: column; justify-content: space-between; }

        /* Header */
        .header { display: flex; align-items: center; justify-content: space-between; width: 100%; height: ${isFeed ? '65px' : '95px'}; padding-bottom: ${isFeed ? '6px' : '14px'}; border-bottom: 1px solid ${palette.border}; opacity: 0; transform: translateY(-30px); transition: all 0.5s ease; }
        .store-brand { display: flex; align-items: center; gap: ${isFeed ? '12px' : '20px'}; }
        .store-logo { max-height: ${isFeed ? '45px' : '65px'}; max-width: ${isFeed ? '140px' : '200px'}; object-fit: contain; }
        .store-name { font-family: 'Outfit', sans-serif; font-size: ${isFeed ? '22px' : '32px'}; font-weight: 800; text-transform: uppercase; color: #FFFFFF; }
        .promo-badge { background: ${palette.badgeBg}; color: #FFFFFF; font-family: 'Outfit', sans-serif; font-weight: 900; font-size: ${isFeed ? '17px' : '25px'}; padding: ${isFeed ? '5px 12px' : '7px 20px'}; border-radius: 50px; box-shadow: 0 8px 25px rgba(255, 59, 48, 0.5); text-transform: uppercase; }

        /* Stage Photo Area */
        .stage { position: relative; flex: 1; display: flex; align-items: center; justify-content: center; margin: ${isFeed ? '8px 0' : '16px 0'}; }
        .image-card { position: relative; width: 100%; height: ${isFeed ? '630px' : '880px'}; background: ${palette.cardBg}; backdrop-filter: blur(20px); border: 1px solid ${palette.border}; border-radius: ${isFeed ? '24px' : '36px'}; box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6); display: flex; align-items: center; justify-content: center; padding: 10px; overflow: hidden; opacity: 0; transform: scale(0.85); transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
        .image-card-blur-bg { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; filter: blur(30px) opacity(0.4); transform: scale(1.2); pointer-events: none; }
        .product-image { position: relative; z-index: 2; width: 98%; height: 98%; max-width: 98%; max-height: 98%; object-fit: contain; filter: drop-shadow(0 20px 35px rgba(0,0,0,0.6)); transition: transform 0.3s ease, opacity 0.3s ease; }
        .brand-tag { position: absolute; top: ${isFeed ? '14px' : '24px'}; left: ${isFeed ? '14px' : '24px'}; z-index: 3; background: rgba(15, 23, 42, 0.85); border: 1px solid ${palette.border}; color: ${palette.accent}; font-size: ${isFeed ? '15px' : '20px'}; font-weight: 800; padding: ${isFeed ? '5px 12px' : '8px 20px'}; border-radius: 12px; text-transform: uppercase; letter-spacing: 1px; }
        .photo-badge { position: absolute; bottom: ${isFeed ? '14px' : '24px'}; right: ${isFeed ? '14px' : '24px'}; z-index: 3; background: rgba(15, 23, 42, 0.85); border: 1px solid ${palette.border}; color: #FFFFFF; font-size: ${isFeed ? '14px' : '18px'}; font-weight: 700; padding: ${isFeed ? '4px 10px' : '6px 16px'}; border-radius: 10px; font-family: 'Outfit', sans-serif; }

        /* Info Card */
        .info-card { background: ${palette.infoBg}; backdrop-filter: blur(30px); border: 1px solid ${palette.border}; border-radius: ${isFeed ? '20px' : '32px'}; padding: ${isFeed ? '14px 18px' : '24px 28px'}; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5); display: flex; flex-direction: column; gap: ${isFeed ? '4px' : '10px'}; opacity: 0; transform: translateY(40px); transition: all 0.5s ease; }
        .product-title { font-family: 'Outfit', sans-serif; font-size: ${isFeed ? '24px' : '36px'}; font-weight: 800; line-height: 1.2; color: #FFFFFF; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; word-break: break-word; overflow-wrap: break-word; }
        .price-section { display: flex; align-items: flex-end; justify-content: space-between; margin-top: ${isFeed ? '2px' : '4px'}; }
        .original-price { font-size: ${isFeed ? '15px' : '22px'}; color: ${palette.mutedText}; text-decoration: line-through; font-weight: 600; }
        .current-price-label { font-size: ${isFeed ? '13px' : '17px'}; font-weight: 700; color: ${palette.accent}; text-transform: uppercase; letter-spacing: 1px; }
        .current-price { font-family: 'Outfit', sans-serif; font-size: ${isFeed ? '40px' : '56px'}; font-weight: 900; color: ${palette.accent}; line-height: 1; letter-spacing: -1px; text-shadow: 0 0 25px ${palette.accent}66; }
        .installment-badge { background: rgba(255,255,255,0.08); border: 1px solid ${palette.border}; color: ${palette.accent}; font-size: ${isFeed ? '14px' : '19px'}; font-weight: 700; padding: ${isFeed ? '5px 12px' : '8px 18px'}; border-radius: 12px; align-self: flex-end; }

        /* Footer CTA */
        .footer { display: flex; align-items: center; justify-content: space-between; margin-top: ${isFeed ? '6px' : '12px'}; padding-top: ${isFeed ? '6px' : '10px'}; border-top: 1px solid ${palette.border}; font-size: ${isFeed ? '15px' : '19px'}; color: ${palette.mutedText}; font-weight: 600; opacity: 0; transform: translateY(20px); transition: all 0.5s ease; }
        .cta-pill { background: ${palette.primary}; color: #FFFFFF; font-family: 'Outfit', sans-serif; font-size: ${isFeed ? '15px' : '21px'}; font-weight: 900; padding: ${isFeed ? '6px 14px' : '9px 22px'}; border-radius: 12px; text-transform: uppercase; box-shadow: 0 8px 25px ${palette.primary}88; animation: pulseCta 1.5s infinite; }

        .visible { opacity: 1 !important; transform: none !important; }
        .pulse-scale { transform: scale(1.05) !important; }
    </style>
</head>
<body>
    <div class="glow-1"></div>
    <div class="glow-2"></div>
    <canvas id="canvasParticles" width="1080" height="${(data.formato || 'stories') === 'feed' ? 1080 : 1920}" style="position: absolute; top:0; left:0; width:100%; height:100%; z-index: 99; pointer-events: none;"></canvas>
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
                ${fotos[0] ? `<img class="image-card-blur-bg" id="elemBlurBg" src="${fotos[0]}">` : ''}
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
        const efeitoVisual = "${(data.efeitoVisual || data.efeito_visual || 'none').toLowerCase()}";

        const elemHeader = document.getElementById('elemHeader');
        const elemImgCard = document.getElementById('elemImgCard');
        const elemProductImg = document.getElementById('elemProductImg');
        const elemBlurBg = document.getElementById('elemBlurBg');
        const elemInfoCard = document.getElementById('elemInfoCard');
        const elemPrice = document.getElementById('elemPrice');
        const elemFooter = document.getElementById('elemFooter');

        // Engine de Partículas Canvas
        const canvas = document.getElementById('canvasParticles');
        const ctx = canvas ? canvas.getContext('2d') : null;
        let particles = [];
        let lastSpawnTime = 0;

        const corTemaVal = "${(data.corTema || data.cor_tema || 'dark').toLowerCase()}";

        function getContrastParticleColors(theme) {
            theme = (theme || 'dark').toLowerCase();
            if (theme === 'dark' || theme === 'ocean') {
                return ['#FDE047', '#38BDF8', '#FFB6C1', '#A7F3D0', '#FFFFFF', '#F472B6'];
            } else if (theme === 'emerald' || theme === 'purple') {
                return ['#FDE047', '#F472B6', '#38BDF8', '#FBBF24', '#FFFFFF', '#6EE7B7'];
            } else if (theme === 'sunset' || theme === 'rose') {
                return ['#38BDF8', '#6EE7B7', '#FDE047', '#FFFFFF', '#F472B6', '#A78BFA'];
            } else if (theme === 'gold') {
                return ['#581C87', '#0F172A', '#BE123C', '#FFFFFF', '#0369A1', '#4C1D95'];
            }
            return ['#38BDF8', '#FDE047', '#F472B6', '#6EE7B7', '#FFFFFF'];
        }

        function initParticles(type) {
            particles = [];
            if (!ctx || type === 'none') return;
            const contrastColors = getContrastParticleColors(corTemaVal);

            const allKawaiiTypes = [
                'baby_kids', 'flowers', 'paws', 'balloons', 'gifts',
                'christmas', 'birthday', 'fashion', 'valentines', 'shoes',
                'handbags', 'sweets', 'shirts', 'jeans', 'sneakers', 'woman', 'man'
            ];

            if (allKawaiiTypes.includes(type)) {
                for (let i = 0; i < 35; i++) {
                    particles.push({
                        x: Math.random() * canvas.width,
                        y: Math.random() * canvas.height,
                        size: Math.random() * 28 + 22,
                        speedY: Math.random() * 2.2 + 1.2,
                        speedX: Math.random() * 1.5 - 0.75,
                        alpha: Math.random() * 0.85 + 0.15,
                        color: contrastColors[Math.floor(Math.random() * contrastColors.length)]
                    });
                }
            } else if (type === 'stars') {
                for (let i = 0; i < 40; i++) {
                    particles.push({
                        x: Math.random() * canvas.width,
                        y: Math.random() * canvas.height,
                        size: Math.random() * 20 + 10,
                        alpha: Math.random(),
                        phase: Math.random() * Math.PI * 2,
                        color: contrastColors[Math.floor(Math.random() * contrastColors.length)]
                    });
                }
            } else if (type === 'hearts') {
                for (let i = 0; i < 30; i++) {
                    particles.push({
                        x: Math.random() * canvas.width,
                        y: Math.random() * canvas.height,
                        size: Math.random() * 22 + 14,
                        speedY: Math.random() * 2.5 + 1.5,
                        alpha: Math.random() * 0.85 + 0.15,
                        color: ['#EC4899', '#F43F5E', '#E11D48', '#FF69B4', '#FB7185'][Math.floor(Math.random() * 5)]
                    });
                }
            } else if (type === 'confetti') {
                for (let i = 0; i < 90; i++) {
                    particles.push({
                        x: Math.random() * canvas.width,
                        y: Math.random() * canvas.height,
                        w: Math.random() * 16 + 8,
                        h: Math.random() * 10 + 5,
                        speedY: Math.random() * 4.5 + 2.5,
                        speedX: Math.random() * 3 - 1.5,
                        rot: Math.random() * 360,
                        rotSpeed: Math.random() * 8 - 4,
                        color: ['#FF3B30', '#38BDF8', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#FFD700'][Math.floor(Math.random() * 7)]
                    });
                }
            }
        }

        function drawHeartPath(ctx, x, y, size, color, alpha) {
            ctx.save();
            ctx.globalAlpha = Math.max(0, Math.min(1, alpha));
            ctx.fillStyle = color;
            ctx.shadowColor = color;
            ctx.shadowBlur = 12;
            ctx.beginPath();
            const d = size;
            ctx.moveTo(x, y + d / 4);
            ctx.quadraticCurveTo(x, y, x - d / 2, y);
            ctx.quadraticCurveTo(x - d, y, x - d, y + d / 2);
            ctx.quadraticCurveTo(x - d, y + d, x, y + d * 1.3);
            ctx.quadraticCurveTo(x + d, y + d, x + d, y + d / 2);
            ctx.quadraticCurveTo(x + d, y, x + d / 2, y);
            ctx.quadraticCurveTo(x, y, x, y + d / 4);
            ctx.closePath();
            ctx.fill();
            ctx.restore();
        }

        function drawBabyHead(ctx, x, y, size, color, alpha) {
            ctx.save();
            ctx.globalAlpha = Math.max(0, Math.min(1, alpha));
            const r = size / 2;
            ctx.fillStyle = color;
            ctx.beginPath();
            ctx.arc(x, y, r, 0, Math.PI * 2);
            ctx.fill();

            ctx.fillStyle = 'rgba(255, 182, 193, 0.85)';
            ctx.beginPath();
            ctx.ellipse(x - r * 0.45, y + r * 0.15, r * 0.22, r * 0.14, 0, 0, Math.PI * 2);
            ctx.ellipse(x + r * 0.45, y + r * 0.15, r * 0.22, r * 0.14, 0, 0, Math.PI * 2);
            ctx.fill();

            ctx.fillStyle = '#0F172A';
            ctx.beginPath();
            ctx.arc(x - r * 0.3, y - r * 0.1, r * 0.12, 0, Math.PI * 2);
            ctx.arc(x + r * 0.3, y - r * 0.1, r * 0.12, 0, Math.PI * 2);
            ctx.fill();

            ctx.strokeStyle = '#0F172A';
            ctx.lineWidth = Math.max(2, r * 0.1);
            ctx.lineCap = 'round';
            ctx.beginPath();
            ctx.arc(x, y + r * 0.05, r * 0.28, 0.2 * Math.PI, 0.8 * Math.PI);
            ctx.stroke();

            ctx.beginPath();
            ctx.arc(x, y - r * 1.1, r * 0.25, 0.5 * Math.PI, 1.3 * Math.PI);
            ctx.stroke();
            ctx.restore();
        }

        function drawChildJumping(ctx, x, y, size, color, alpha) {
            ctx.save();
            ctx.globalAlpha = Math.max(0, Math.min(1, alpha));
            ctx.fillStyle = color;
            ctx.strokeStyle = color;
            ctx.lineWidth = size * 0.22;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';

            const rHead = size * 0.25;
            ctx.beginPath();
            ctx.arc(x, y - size * 0.4, rHead, 0, Math.PI * 2);
            ctx.fill();

            ctx.beginPath();
            ctx.arc(x, y, size * 0.2, 0, Math.PI * 2);
            ctx.fill();

            ctx.beginPath();
            ctx.moveTo(x - size * 0.45, y - size * 0.35);
            ctx.lineTo(x, y - size * 0.05);
            ctx.lineTo(x + size * 0.45, y - size * 0.35);
            ctx.stroke();

            ctx.beginPath();
            ctx.moveTo(x - size * 0.35, y + size * 0.45);
            ctx.lineTo(x, y + size * 0.1);
            ctx.lineTo(x + size * 0.35, y + size * 0.45);
            ctx.stroke();
            ctx.restore();
        }

        function drawDaisyFlower(ctx, x, y, size, color, alpha) {
            ctx.save();
            ctx.globalAlpha = Math.max(0, Math.min(1, alpha));
            const rCenter = size * 0.28;
            const rPetal = size * 0.32;

            ctx.fillStyle = color;
            for (let i = 0; i < 6; i++) {
                const angle = (i * Math.PI) / 3;
                const px = x + Math.cos(angle) * (rCenter + rPetal * 0.6);
                const py = y + Math.sin(angle) * (rCenter + rPetal * 0.6);
                ctx.beginPath();
                ctx.arc(px, py, rPetal, 0, Math.PI * 2);
                ctx.fill();
            }

            ctx.fillStyle = '#FDE047';
            ctx.beginPath();
            ctx.arc(x, y, rCenter, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        }

        function drawPetPaw(ctx, x, y, size, color, alpha) {
            ctx.save();
            ctx.globalAlpha = Math.max(0, Math.min(1, alpha));
            ctx.fillStyle = color;

            ctx.beginPath();
            ctx.ellipse(x, y + size * 0.15, size * 0.42, size * 0.32, 0, 0, Math.PI * 2);
            ctx.fill();

            const toes = [
                { dx: -size * 0.4, dy: -size * 0.25, r: size * 0.14 },
                { dx: -size * 0.15, dy: -size * 0.42, r: size * 0.16 },
                { dx: size * 0.15, dy: -size * 0.42, r: size * 0.16 },
                { dx: size * 0.4, dy: -size * 0.25, r: size * 0.14 }
            ];
            toes.forEach(t => {
                ctx.beginPath();
                ctx.arc(x + t.dx, y + t.dy, t.r, 0, Math.PI * 2);
                ctx.fill();
            });
            ctx.restore();
        }

        function drawStarChubby(ctx, x, y, size, color, alpha) {
            ctx.save();
            ctx.globalAlpha = Math.max(0, Math.min(1, alpha));
            ctx.fillStyle = color;

            ctx.beginPath();
            const points = 5;
            const outerR = size * 0.6;
            const innerR = size * 0.28;
            for (let i = 0; i < points * 2; i++) {
                const r = i % 2 === 0 ? outerR : innerR;
                const angle = (i * Math.PI) / points - Math.PI / 2;
                const px = x + Math.cos(angle) * r;
                const py = y + Math.sin(angle) * r;
                if (i === 0) ctx.moveTo(px, py);
                else ctx.lineTo(px, py);
            }
            ctx.closePath();
            ctx.fill();

            ctx.fillStyle = '#0F172A';
            ctx.beginPath();
            ctx.arc(x - size * 0.15, y - size * 0.05, size * 0.07, 0, Math.PI * 2);
            ctx.arc(x + size * 0.15, y - size * 0.05, size * 0.07, 0, Math.PI * 2);
            ctx.fill();

            ctx.strokeStyle = '#0F172A';
            ctx.lineWidth = Math.max(2, size * 0.06);
            ctx.lineCap = 'round';
            ctx.beginPath();
            ctx.arc(x, y + size * 0.05, size * 0.12, 0.2 * Math.PI, 0.8 * Math.PI);
            ctx.stroke();
            ctx.restore();
        }

        function drawPastelBalloon(ctx, x, y, size, color, alpha) {
            ctx.save();
            ctx.globalAlpha = Math.max(0, Math.min(1, alpha));
            ctx.fillStyle = color;

            ctx.beginPath();
            ctx.ellipse(x, y - size * 0.1, size * 0.38, size * 0.48, 0, 0, Math.PI * 2);
            ctx.fill();

            ctx.beginPath();
            ctx.moveTo(x - size * 0.1, y + size * 0.38);
            ctx.lineTo(x + size * 0.1, y + size * 0.38);
            ctx.lineTo(x, y + size * 0.48);
            ctx.closePath();
            ctx.fill();

            ctx.strokeStyle = 'rgba(255,255,255,0.7)';
            ctx.lineWidth = Math.max(2, size * 0.05);
            ctx.beginPath();
            ctx.moveTo(x, y + size * 0.48);
            ctx.quadraticCurveTo(x + size * 0.15, y + size * 0.7, x, y + size * 0.95);
            ctx.stroke();
            ctx.restore();
        }

        function drawGiftBox(ctx, x, y, size, color, alpha) {
            ctx.save();
            ctx.globalAlpha = Math.max(0, Math.min(1, alpha));
            ctx.fillStyle = color;

            const boxW = size * 0.7;
            const boxH = size * 0.6;
            const boxX = x - boxW / 2;
            const boxY = y - boxH / 2 + size * 0.1;
            const radius = size * 0.12;

            ctx.beginPath();
            ctx.moveTo(boxX + radius, boxY);
            ctx.lineTo(boxX + boxW - radius, boxY);
            ctx.quadraticCurveTo(boxX + boxW, boxY, boxX + boxW, boxY + radius);
            ctx.lineTo(boxX + boxW, boxY + boxH - radius);
            ctx.quadraticCurveTo(boxX + boxW, boxY + boxH, boxX + boxW - radius, boxY + boxH);
            ctx.lineTo(boxX + radius, boxY + boxH);
            ctx.quadraticCurveTo(boxX, boxY + boxH, boxX, boxY + boxH - radius);
            ctx.lineTo(boxX, boxY + radius);
            ctx.quadraticCurveTo(boxX, boxY, boxX + radius, boxY);
            ctx.closePath();
            ctx.fill();

            ctx.fillStyle = '#FFB6C1';
            const ribbonW = size * 0.14;
            ctx.fillRect(x - ribbonW / 2, boxY, ribbonW, boxH);
            ctx.fillRect(boxX, y + size * 0.1 - ribbonW / 2, boxW, ribbonW);

            ctx.beginPath();
            ctx.ellipse(x - size * 0.16, boxY - size * 0.08, size * 0.16, size * 0.1, -0.2, 0, Math.PI * 2);
            ctx.ellipse(x + size * 0.16, boxY - size * 0.08, size * 0.16, size * 0.1, 0.2, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        }

        function drawChristmasTree(ctx, x, y, size, color, alpha) {
            ctx.save();
            ctx.globalAlpha = Math.max(0, Math.min(1, alpha));
            ctx.fillStyle = '#10B981';
            const h = size * 0.9;
            const w = size * 0.7;

            for (let i = 0; i < 3; i++) {
                const layerY = y - h * 0.4 + i * (h * 0.25);
                const layerW = w * (0.5 + i * 0.25);
                ctx.beginPath();
                ctx.moveTo(x, layerY - h * 0.3);
                ctx.lineTo(x + layerW / 2, layerY + h * 0.15);
                ctx.lineTo(x - layerW / 2, layerY + h * 0.15);
                ctx.closePath();
                ctx.fill();
            }

            ctx.fillStyle = '#78350F';
            ctx.fillRect(x - w * 0.1, y + h * 0.35, w * 0.2, h * 0.15);

            ctx.fillStyle = '#FDE047';
            ctx.beginPath();
            ctx.arc(x, y - h * 0.45, size * 0.12, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        }

        function drawBirthdayCake(ctx, x, y, size, color, alpha) {
            ctx.save();
            ctx.globalAlpha = Math.max(0, Math.min(1, alpha));
            ctx.fillStyle = color;

            const w = size * 0.75;
            const h = size * 0.5;
            const bx = x - w / 2;
            const by = y - h / 2 + size * 0.1;

            ctx.beginPath();
            if (ctx.roundRect) ctx.roundRect(bx, by, w, h, size * 0.1);
            else ctx.fillRect(bx, by, w, h);
            ctx.fill();

            ctx.fillStyle = '#FFFFFF';
            ctx.beginPath();
            ctx.arc(x - w * 0.3, by, size * 0.1, 0, Math.PI);
            ctx.arc(x, by, size * 0.1, 0, Math.PI);
            ctx.arc(x + w * 0.3, by, size * 0.1, 0, Math.PI);
            ctx.fill();

            ctx.fillStyle = '#F472B6';
            ctx.fillRect(x - size * 0.04, by - size * 0.22, size * 0.08, size * 0.22);
            ctx.fillStyle = '#FDE047';
            ctx.beginPath();
            ctx.arc(x, by - size * 0.28, size * 0.06, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        }

        function drawFashionHanger(ctx, x, y, size, color, alpha) {
            ctx.save();
            ctx.globalAlpha = Math.max(0, Math.min(1, alpha));
            ctx.strokeStyle = color;
            ctx.lineWidth = Math.max(2, size * 0.1);
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';

            ctx.beginPath();
            ctx.arc(x, y - size * 0.3, size * 0.15, 0.2 * Math.PI, 1.8 * Math.PI, true);
            ctx.stroke();

            const w = size * 0.8;
            const h = size * 0.35;
            ctx.beginPath();
            ctx.moveTo(x, y - size * 0.15);
            ctx.lineTo(x + w / 2, y + h);
            ctx.lineTo(x - w / 2, y + h);
            ctx.closePath();
            ctx.stroke();

            ctx.fillStyle = '#FFB6C1';
            ctx.beginPath();
            ctx.arc(x, y - size * 0.15, size * 0.08, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        }

        function drawValentinesHeart(ctx, x, y, size, color, alpha) {
            ctx.save();
            ctx.globalAlpha = Math.max(0, Math.min(1, alpha));

            drawHeartPath(ctx, x - size * 0.1, y, size * 0.8, color, alpha);

            ctx.strokeStyle = '#FDE047';
            ctx.lineWidth = Math.max(2, size * 0.08);
            ctx.lineCap = 'round';
            ctx.beginPath();
            ctx.moveTo(x - size * 0.6, y + size * 0.4);
            ctx.lineTo(x + size * 0.6, y - size * 0.4);
            ctx.stroke();

            ctx.fillStyle = '#FDE047';
            ctx.beginPath();
            ctx.moveTo(x + size * 0.6, y - size * 0.4);
            ctx.lineTo(x + size * 0.45, y - size * 0.4);
            ctx.lineTo(x + size * 0.6, y - size * 0.25);
            ctx.closePath();
            ctx.fill();
            ctx.restore();
        }

        function drawHighHeelShoe(ctx, x, y, size, color, alpha) {
            ctx.save();
            ctx.globalAlpha = Math.max(0, Math.min(1, alpha));
            ctx.fillStyle = color;

            ctx.fillRect(x - size * 0.35, y, size * 0.08, size * 0.45);

            ctx.beginPath();
            ctx.moveTo(x - size * 0.4, y - size * 0.1);
            ctx.quadraticCurveTo(x - size * 0.1, y + size * 0.2, x + size * 0.4, y + size * 0.2);
            ctx.quadraticCurveTo(x + size * 0.2, y - size * 0.2, x - size * 0.3, y - size * 0.2);
            ctx.closePath();
            ctx.fill();

            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(x - size * 0.05, y - size * 0.05, size * 0.18, size * 0.06);
            ctx.restore();
        }

        function drawHandbag(ctx, x, y, size, color, alpha) {
            ctx.save();
            ctx.globalAlpha = Math.max(0, Math.min(1, alpha));
            ctx.fillStyle = color;

            const w = size * 0.7;
            const h = size * 0.5;
            const bx = x - w / 2;
            const by = y - h / 2 + size * 0.1;

            ctx.beginPath();
            ctx.moveTo(bx + size * 0.1, by);
            ctx.lineTo(bx + w - size * 0.1, by);
            ctx.quadraticCurveTo(bx + w, by, bx + w, by + size * 0.1);
            ctx.lineTo(bx + w * 0.9, by + h);
            ctx.lineTo(bx + w * 0.1, by + h);
            ctx.lineTo(bx, by + size * 0.1);
            ctx.quadraticCurveTo(bx, by, bx + size * 0.1, by);
            ctx.closePath();
            ctx.fill();

            ctx.strokeStyle = color;
            ctx.lineWidth = Math.max(3, size * 0.09);
            ctx.beginPath();
            ctx.arc(x, by, size * 0.22, Math.PI, 0);
            ctx.stroke();

            ctx.fillStyle = '#FDE047';
            ctx.beginPath();
            ctx.arc(x, by + h * 0.4, size * 0.08, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        }

        function drawSweets(ctx, x, y, size, color, alpha) {
            ctx.save();
            ctx.globalAlpha = Math.max(0, Math.min(1, alpha));
            ctx.fillStyle = color;

            ctx.beginPath();
            ctx.ellipse(x, y, size * 0.32, size * 0.22, 0, 0, Math.PI * 2);
            ctx.fill();

            ctx.beginPath();
            ctx.moveTo(x - size * 0.3, y);
            ctx.lineTo(x - size * 0.5, y - size * 0.15);
            ctx.lineTo(x - size * 0.5, y + size * 0.15);
            ctx.closePath();
            ctx.fill();

            ctx.beginPath();
            ctx.moveTo(x + size * 0.3, y);
            ctx.lineTo(x + size * 0.5, y - size * 0.15);
            ctx.lineTo(x + size * 0.5, y + size * 0.15);
            ctx.closePath();
            ctx.fill();
            ctx.restore();
        }

        function drawShirt(ctx, x, y, size, color, alpha) {
            ctx.save();
            ctx.globalAlpha = Math.max(0, Math.min(1, alpha));
            ctx.fillStyle = color;

            const w = size * 0.7;
            const h = size * 0.65;

            ctx.beginPath();
            ctx.moveTo(x - w * 0.25, y - h * 0.5);
            ctx.lineTo(x - w * 0.5, y - h * 0.25);
            ctx.lineTo(x - w * 0.35, y - h * 0.1);
            ctx.lineTo(x - w * 0.35, y + h * 0.5);
            ctx.lineTo(x + w * 0.35, y + h * 0.5);
            ctx.lineTo(x + w * 0.35, y - h * 0.1);
            ctx.lineTo(x + w * 0.5, y - h * 0.25);
            ctx.lineTo(x + w * 0.25, y - h * 0.5);
            ctx.closePath();
            ctx.fill();

            ctx.fillStyle = '#FFFFFF';
            ctx.beginPath();
            ctx.arc(x, y - h * 0.1, size * 0.04, 0, Math.PI * 2);
            ctx.arc(x, y + h * 0.15, size * 0.04, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        }

        function drawJeans(ctx, x, y, size, color, alpha) {
            ctx.save();
            ctx.globalAlpha = Math.max(0, Math.min(1, alpha));
            ctx.fillStyle = '#3B82F6';

            const w = size * 0.55;
            const h = size * 0.75;

            ctx.beginPath();
            ctx.moveTo(x - w * 0.5, y - h * 0.5);
            ctx.lineTo(x + w * 0.5, y - h * 0.5);
            ctx.lineTo(x + w * 0.45, y + h * 0.5);
            ctx.lineTo(x + w * 0.08, y + h * 0.5);
            ctx.lineTo(x, y - h * 0.1);
            ctx.lineTo(x - w * 0.08, y + h * 0.5);
            ctx.lineTo(x - w * 0.45, y + h * 0.5);
            ctx.closePath();
            ctx.fill();

            ctx.fillStyle = '#F59E0B';
            ctx.fillRect(x - w * 0.5, y - h * 0.5, w, h * 0.08);
            ctx.restore();
        }

        function drawSneakers(ctx, x, y, size, color, alpha) {
            ctx.save();
            ctx.globalAlpha = Math.max(0, Math.min(1, alpha));
            ctx.fillStyle = color;

            ctx.beginPath();
            ctx.ellipse(x, y, size * 0.45, size * 0.25, -0.1, 0, Math.PI * 2);
            ctx.fill();

            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(x - size * 0.42, y + size * 0.12, size * 0.84, size * 0.1);

            ctx.strokeStyle = '#FFFFFF';
            ctx.lineWidth = Math.max(2, size * 0.06);
            ctx.beginPath();
            ctx.moveTo(x - size * 0.15, y - size * 0.1);
            ctx.lineTo(x + size * 0.1, y + size * 0.05);
            ctx.stroke();
            ctx.restore();
        }

        function drawWomanHead(ctx, x, y, size, color, alpha) {
            ctx.save();
            ctx.globalAlpha = Math.max(0, Math.min(1, alpha));

            drawBabyHead(ctx, x, y, size, color, alpha);

            ctx.fillStyle = '#EC4899';
            ctx.beginPath();
            ctx.ellipse(x - size * 0.2, y - size * 0.4, size * 0.15, size * 0.09, -0.3, 0, Math.PI * 2);
            ctx.ellipse(x + size * 0.2, y - size * 0.4, size * 0.15, size * 0.09, 0.3, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        }

        function drawManHead(ctx, x, y, size, color, alpha) {
            ctx.save();
            ctx.globalAlpha = Math.max(0, Math.min(1, alpha));

            drawBabyHead(ctx, x, y, size, color, alpha);

            ctx.fillStyle = '#1E293B';
            ctx.beginPath();
            ctx.arc(x, y - size * 0.4, size * 0.22, 0, Math.PI);
            ctx.fill();
            ctx.restore();
        }

        function renderParticles(currentTime, type) {
            if (!ctx || type === 'none') return;
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const allKawaiiTypes = [
                'baby_kids', 'flowers', 'paws', 'balloons', 'gifts',
                'christmas', 'birthday', 'fashion', 'valentines', 'shoes',
                'handbags', 'sweets', 'shirts', 'jeans', 'sneakers', 'woman', 'man'
            ];

            if (allKawaiiTypes.includes(type)) {
                for (let i = 0; i < particles.length; i++) {
                    const p = particles[i];
                    p.y -= p.speedY;
                    p.x += Math.sin(p.y * 0.02) * p.speedX;
                    if (p.y < -60) { p.y = canvas.height + 60; p.x = Math.random() * canvas.width; }

                    if (type === 'baby_kids') {
                        if (i % 2 === 0) drawBabyHead(ctx, p.x, p.y, p.size, p.color, p.alpha);
                        else drawChildJumping(ctx, p.x, p.y, p.size, p.color, p.alpha);
                    } else if (type === 'flowers') {
                        drawDaisyFlower(ctx, p.x, p.y, p.size, p.color, p.alpha);
                    } else if (type === 'paws') {
                        drawPetPaw(ctx, p.x, p.y, p.size, p.color, p.alpha);
                    } else if (type === 'balloons') {
                        drawPastelBalloon(ctx, p.x, p.y, p.size, p.color, p.alpha);
                    } else if (type === 'gifts') {
                        drawGiftBox(ctx, p.x, p.y, p.size, p.color, p.alpha);
                    } else if (type === 'christmas') {
                        drawChristmasTree(ctx, p.x, p.y, p.size, p.color, p.alpha);
                    } else if (type === 'birthday') {
                        drawBirthdayCake(ctx, p.x, p.y, p.size, p.color, p.alpha);
                    } else if (type === 'fashion') {
                        drawFashionHanger(ctx, p.x, p.y, p.size, p.color, p.alpha);
                    } else if (type === 'valentines') {
                        drawValentinesHeart(ctx, p.x, p.y, p.size, p.color, p.alpha);
                    } else if (type === 'shoes') {
                        drawHighHeelShoe(ctx, p.x, p.y, p.size, p.color, p.alpha);
                    } else if (type === 'handbags') {
                        drawHandbag(ctx, p.x, p.y, p.size, p.color, p.alpha);
                    } else if (type === 'sweets') {
                        drawSweets(ctx, p.x, p.y, p.size, p.color, p.alpha);
                    } else if (type === 'shirts') {
                        drawShirt(ctx, p.x, p.y, p.size, p.color, p.alpha);
                    } else if (type === 'jeans') {
                        drawJeans(ctx, p.x, p.y, p.size, p.color, p.alpha);
                    } else if (type === 'sneakers') {
                        drawSneakers(ctx, p.x, p.y, p.size, p.color, p.alpha);
                    } else if (type === 'woman') {
                        drawWomanHead(ctx, p.x, p.y, p.size, p.color, p.alpha);
                    } else if (type === 'man') {
                        drawManHead(ctx, p.x, p.y, p.size, p.color, p.alpha);
                    }
                }
            } else if (type === 'fireworks') {
                if (currentTime - lastSpawnTime > 1.2 || particles.length === 0) {
                    lastSpawnTime = currentTime;
                    const cx = Math.random() * (canvas.width - 300) + 150;
                    const cy = Math.random() * (canvas.height * 0.5) + 200;
                    const colors = ['#FFD700', '#FF3B30', '#38BDF8', '#10B981', '#F43F5E', '#A78BFA'];
                    const burstColor = colors[Math.floor(Math.random() * colors.length)];
                    for (let i = 0; i < 45; i++) {
                        const angle = Math.random() * Math.PI * 2;
                        const speed = Math.random() * 9 + 3;
                        particles.push({
                            x: cx, y: cy,
                            vx: Math.cos(angle) * speed,
                            vy: Math.sin(angle) * speed,
                            alpha: 1.0,
                            decay: Math.random() * 0.03 + 0.015,
                            size: Math.random() * 6 + 3,
                            color: burstColor
                        });
                    }
                }
                for (let i = particles.length - 1; i >= 0; i--) {
                    const p = particles[i];
                    p.x += p.vx;
                    p.y += p.vy;
                    p.vy += 0.15;
                    p.alpha -= p.decay;
                    if (p.alpha <= 0) { particles.splice(i, 1); continue; }
                    ctx.save();
                    ctx.globalAlpha = Math.max(0, p.alpha);
                    ctx.fillStyle = p.color;
                    ctx.shadowColor = p.color;
                    ctx.shadowBlur = 10;
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.restore();
                }
            } else if (type === 'confetti') {
                for (let i = 0; i < particles.length; i++) {
                    const p = particles[i];
                    p.y += p.speedY;
                    p.x += Math.sin(p.y * 0.02) * p.speedX;
                    p.rot += p.rotSpeed;
                    if (p.y > canvas.height + 20) { p.y = -20; p.x = Math.random() * canvas.width; }
                    ctx.save();
                    ctx.translate(p.x, p.y);
                    ctx.rotate((p.rot * Math.PI) / 180);
                    ctx.fillStyle = p.color;
                    ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
                    ctx.restore();
                }
            } else if (type === 'sparks') {
                if (currentTime - lastSpawnTime > 0.6 || particles.length < 20) {
                    lastSpawnTime = currentTime;
                    const cx = Math.random() * canvas.width;
                    const cy = Math.random() * canvas.height;
                    for (let i = 0; i < 15; i++) {
                        const angle = Math.random() * Math.PI * 2;
                        const speed = Math.random() * 12 + 4;
                        particles.push({
                            x: cx, y: cy,
                            vx: Math.cos(angle) * speed,
                            vy: Math.sin(angle) * speed,
                            alpha: 1.0, decay: 0.04,
                            size: Math.random() * 5 + 2,
                            color: Math.random() > 0.5 ? '#F59E0B' : '#38BDF8'
                        });
                    }
                }
                for (let i = particles.length - 1; i >= 0; i--) {
                    const p = particles[i];
                    p.x += p.vx; p.y += p.vy; p.alpha -= p.decay;
                    if (p.alpha <= 0) { particles.splice(i, 1); continue; }
                    ctx.save();
                    ctx.globalAlpha = Math.max(0, p.alpha);
                    ctx.fillStyle = p.color;
                    ctx.shadowColor = p.color;
                    ctx.shadowBlur = 12;
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.restore();
                }
            } else if (type === 'stars') {
                for (let i = 0; i < particles.length; i++) {
                    const p = particles[i];
                    p.alpha = 0.3 + 0.7 * Math.abs(Math.sin(currentTime * 3 + p.phase));
                    ctx.save();
                    ctx.globalAlpha = p.alpha;
                    ctx.fillStyle = p.color;
                    ctx.shadowColor = p.color;
                    ctx.shadowBlur = 15;
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.restore();
                }
            } else if (type === 'hearts') {
                for (let i = 0; i < particles.length; i++) {
                    const p = particles[i];
                    p.y -= p.speedY;
                    p.x += Math.sin(p.y * 0.03) * 0.8;
                    if (p.y < -50) { p.y = canvas.height + 50; p.x = Math.random() * canvas.width; }
                    drawHeartPath(ctx, p.x, p.y, p.size, p.color, p.alpha);
                }
            }
        }

        initParticles(efeitoVisual);

        window.seekFrame = function(frame, totalFrames, fps) {
            const progress = frame / totalFrames;
            const currentTime = frame / fps;

            if (currentTime >= 0.2) elemHeader.classList.add('visible');
            if (currentTime >= 0.4) elemImgCard.classList.add('visible');

            const zoomFactor = 1 + (progress * 0.12);
            elemProductImg.style.transform = 'scale(' + zoomFactor + ')';

            if (fotosList.length > 1) {
                const tempoPorFoto = duracaoTotal / fotosList.length;
                let fotoIndex = Math.min(Math.floor(currentTime / tempoPorFoto), fotosList.length - 1);

                if (elemProductImg.src !== fotosList[fotoIndex]) {
                    elemProductImg.src = fotosList[fotoIndex];
                    if (elemBlurBg) elemBlurBg.src = fotosList[fotoIndex];
                    const elemPhotoBadge = document.getElementById('elemPhotoBadge');
                    if (elemPhotoBadge) {
                        elemPhotoBadge.innerText = '📷 ' + (fotoIndex + 1) + ' / ' + fotosList.length;
                    }
                }
            }

            if (currentTime >= 0.8) elemInfoCard.classList.add('visible');
            if (currentTime >= 1.2) elemFooter.classList.add('visible');

            if (currentTime > (duracaoTotal - 3.0)) {
                const pulse = 1 + Math.sin((currentTime - (duracaoTotal - 3.0)) * 6) * 0.05;
                elemPrice.style.transform = 'scale(' + pulse + ')';
            }

            renderParticles(currentTime, efeitoVisual);
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

    const isFeed = (data.formato || 'stories').toLowerCase() === 'feed' || (data.formato || '').toLowerCase() === '1:1';
    const totalHeight = isFeed ? 1080 : 1920;

    return `<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800;900&family=Inter:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            width: 1080px; height: ${totalHeight}px;
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
            position: absolute; top: ${isFeed ? '130px' : '220px'}; right: ${isFeed ? '24px' : '40px'}; z-index: 20;
            background: rgba(0, 0, 0, 0.75); border: 1px solid rgba(255, 255, 255, 0.3);
            color: #ffffff; font-size: ${isFeed ? '18px' : '24px'}; font-weight: 700; padding: ${isFeed ? '6px 16px' : '10px 22px'}; border-radius: 14px;
        }

        /* Top Overlay Banner */
        .top-banner {
            position: absolute; top: 0; left: 0; right: 0; z-index: 10;
            background: ${topBgColor}; color: #ffffff;
            padding: ${isFeed ? '25px 30px 18px' : '45px 40px 30px'}; text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
            transform: translateY(-100%); transition: transform 0.5s ease;
        }

        .top-subtitle {
            font-family: 'Inter', sans-serif; font-size: ${isFeed ? '20px' : '28px'}; font-weight: 800;
            letter-spacing: 2px; text-transform: uppercase; color: rgba(255, 255, 255, 0.95); margin-bottom: 4px;
        }

        .top-title {
            font-size: ${isFeed ? '44px' : '64px'}; font-weight: 900; text-transform: uppercase;
            letter-spacing: 1px; color: #ffffff; line-height: 1.1; text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        /* Bottom Overlay Banner */
        .bottom-banner {
            position: absolute; bottom: 0; left: 0; right: 0; z-index: 10;
            background: ${bottomBgColor}; padding: ${isFeed ? '22px 30px 25px' : '40px 50px 45px'};
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 -10px 30px rgba(0,0,0,0.4);
            transform: translateY(100%); transition: transform 0.5s ease;
        }

        .bottom-info { display: flex; flex-direction: column; gap: 4px; }
        .store-brand-name { font-size: ${isFeed ? '28px' : '40px'}; font-weight: 900; color: #000000; text-transform: uppercase; letter-spacing: -0.5px; line-height: 1; }
        .main-headline { font-size: ${isFeed ? '34px' : '48px'}; font-weight: 900; color: #ffffff; text-transform: uppercase; line-height: 1.1; max-width: ${isFeed ? '500px' : '650px'}; }
        .price-badge-text { font-size: ${isFeed ? '42px' : '56px'}; font-weight: 900; color: #ffffff; line-height: 1; }

        .whatsapp-contact-box {
            display: flex; align-items: center; gap: ${isFeed ? '10px' : '16px'};
            background: rgba(0, 0, 0, 0.15); padding: ${isFeed ? '8px 16px' : '12px 24px'}; border-radius: 60px;
        }
        .whatsapp-icon { width: ${isFeed ? '44px' : '64px'}; height: ${isFeed ? '44px' : '64px'}; }
        .phone-number { font-size: ${isFeed ? '32px' : '46px'}; font-weight: 900; color: #000000; font-family: 'Inter', sans-serif; letter-spacing: -1px; }

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
