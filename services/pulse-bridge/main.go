package main

import (
	"bytes"
	"encoding/json"
	"flag"
	"fmt"
	"io"
	"mime/multipart"
	"net/http"
	"os"
	"os/exec"
	"os/signal"
	"path/filepath"
	"strconv"
	"strings"
	"syscall"
	"time"
)

type Job struct {
	ID        string `json:"id"`
	YoutubeID string `json:"youtube_id"`
	URL       string `json:"url"`
	Status    string `json:"status"`
}

type PollResponse struct {
	Success bool   `json:"success"`
	Message string `json:"message,omitempty"`
	Job     *Job   `json:"job,omitempty"`
}

type PingResponse struct {
	Success    bool   `json:"success"`
	ServerTime int64  `json:"server_time"`
	Message    string `json:"message"`
}

func main() {
	serverFlag := flag.String("server", getEnv("PULSE_SERVER", "https://catalogos.oncode.app.br"), "URL do servidor Pulse SaaS")
	secretFlag := flag.String("secret", getEnv("PULSE_BRIDGE_SECRET", "pulse_bridge_sec_7a8f9c2d1e0b5"), "Secret token de autenticacao da Bridge")
	ytdlpFlag := flag.String("ytdlp", getEnv("YTDLP_PATH", "yt-dlp"), "Caminho do executavel yt-dlp")
	tempDirFlag := flag.String("temp", filepath.Join(os.TempDir(), "pulse-bridge"), "Diretorio para arquivos temporarios")
	flag.Parse()

	serverURL := strings.TrimRight(*serverFlag, "/")
	secret := *secretFlag
	ytdlpPath := *ytdlpFlag
	tempDir := *tempDirFlag

	fmt.Println("==========================================================")
	fmt.Println("   PULSE AUDIO BRIDGE - Go Residential Worker v1.0")
	fmt.Println("==========================================================")
	fmt.Printf("[Config] Servidor VPS: %s\n", serverURL)
	fmt.Printf("[Config] Diretorio Temporario: %s\n", tempDir)

	// 1. Verifica se yt-dlp está disponível
	ytdlpExec, err := exec.LookPath(ytdlpPath)
	if err != nil {
		fmt.Printf("\n[AVISO CRÍTICO] O executável 'yt-dlp' não foi encontrado no PATH!\n")
		fmt.Printf("Por favor, instale o yt-dlp ou passe o caminho via flag: -ytdlp=/caminho/yt-dlp\n\n")
	} else {
		fmt.Printf("[Config] yt-dlp detectado em: %s\n", ytdlpExec)
	}

	if err := os.MkdirAll(tempDir, 0755); err != nil {
		fmt.Printf("[Erro] Falha ao criar diretorio temporario: %v\n", err)
		os.Exit(1)
	}

	// 2. Testa conectividade e autenticação com o servidor
	client := &http.Client{Timeout: 35 * time.Second}
	testConnection(client, serverURL, secret)

	// 3. Captura sinal de encerramento gracioso (Ctrl+C)
	sigChan := make(chan os.Signal, 1)
	signal.Notify(sigChan, os.Interrupt, syscall.SIGTERM)
	go func() {
		<-sigChan
		fmt.Println("\n[Bridge] Encerrando worker graciosa e seguramente...")
		os.Exit(0)
	}()

	fmt.Println("[Bridge] 🟢 Conectado e aguardando tarefas do SaaS em tempo real...")

	// 4. Loop contínuo de polling
	falhasConsecutivas := 0
	for {
		job, err := pollJob(client, serverURL, secret)
		if err != nil {
			falhasConsecutivas++
			if falhasConsecutivas <= 3 || falhasConsecutivas%10 == 0 {
				fmt.Printf("[Bridge] ⚠️ Falha ao comunicar com o servidor (%d): %v\n", falhasConsecutivas, err)
			}
			time.Sleep(3 * time.Second)
			continue
		}

		falhasConsecutivas = 0

		if job != nil {
			fmt.Printf("\n[Bridge] 📥 Nova tarefa recebida: Job=%s | Vídeo=%s\n", job.ID, job.YoutubeID)
			fmt.Printf("[Bridge] 🌐 URL: %s\n", job.URL)

			iniciar := time.Now()
			if err := processarJob(client, serverURL, secret, ytdlpPath, tempDir, job); err != nil {
				fmt.Printf("[Bridge] ❌ Falha ao processar job %s: %v\n", job.ID, err)
				reportFail(client, serverURL, secret, job.ID, err.Error())
			} else {
				fmt.Printf("[Bridge] ✅ Concluído e enviado à VPS em %.1fs!\n", time.Since(iniciar).Seconds())
			}
		}

		// Pequeno intervalo entre polls
		time.Sleep(500 * time.Millisecond)
	}
}

func testConnection(client *http.Client, serverURL, secret string) {
	pingURL := fmt.Sprintf("%s/index.php/api/bridge/ping", serverURL)
	req, err := http.NewRequest("GET", pingURL, nil)
	if err != nil {
		fmt.Printf("[Erro] URL invalida: %v\n", err)
		return
	}
	req.Header.Set("X-Bridge-Secret", secret)

	resp, err := client.Do(req)
	if err != nil {
		fmt.Printf("[Alerta] Não foi possível conectar ao servidor (%s): %v\n", pingURL, err)
		fmt.Println("[Alerta] O worker continuará tentando se reconectar automaticamente...")
		return
	}
	defer resp.Body.Close()

	if resp.StatusCode == http.StatusUnauthorized {
		fmt.Println("[ERRO FATAL] O Secret Token informado é inválido para o servidor!")
		os.Exit(1)
	}

	var pingResp PingResponse
	if err := json.NewDecoder(resp.Body).Decode(&pingResp); err == nil && pingResp.Success {
		fmt.Printf("[Bridge] Autenticação confirmada com sucesso! Horário do servidor: %s\n", time.Unix(pingResp.ServerTime, 0).Format("15:04:05"))
	}
}

func pollJob(client *http.Client, serverURL, secret string) (*Job, error) {
	pollURL := fmt.Sprintf("%s/index.php/api/bridge/poll", serverURL)
	req, err := http.NewRequest("GET", pollURL, nil)
	if err != nil {
		return nil, err
	}
	req.Header.Set("X-Bridge-Secret", secret)

	resp, err := client.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return nil, fmt.Errorf("status HTTP %d", resp.StatusCode)
	}

	var pResp PollResponse
	if err := json.NewDecoder(resp.Body).Decode(&pResp); err != nil {
		return nil, err
	}

	if pResp.Success && pResp.Job != nil {
		return pResp.Job, nil
	}

	return nil, nil
}

func processarJob(client *http.Client, serverURL, secret, ytdlpPath, tempDir string, job *Job) error {
	// 1. Extrai metadados locais (título e duração)
	fmt.Println("[Bridge] 🔍 Obtendo informações do vídeo com IP residencial...")
	cmdInfo := exec.Command(ytdlpPath, "--print", "%(title)s||%(duration)s", "--no-playlist", job.URL)
	outInfo, err := cmdInfo.Output()
	titulo := fmt.Sprintf("YouTube: %s", job.YoutubeID)
	duracao := 0.0

	if err == nil {
		parts := strings.Split(strings.TrimSpace(string(outInfo)), "||")
		if len(parts) >= 1 && strings.TrimSpace(parts[0]) != "" {
			titulo = strings.TrimSpace(parts[0])
		}
		if len(parts) >= 2 {
			if d, err := strconv.ParseFloat(strings.TrimSpace(parts[1]), 64); err == nil {
				duracao = d
			}
		}
	}

	fmt.Printf("[Bridge] 🎵 Título: %s (Duração: %.1fs)\n", titulo, duracao)

	// 2. Prepara argumentos de download do áudio usando yt-dlp local
	outputTemplate := filepath.Join(tempDir, fmt.Sprintf("%s.%%(ext)s", job.ID))
	finalMp3 := filepath.Join(tempDir, fmt.Sprintf("%s.mp3", job.ID))

	// Remove eventual arquivo antigo
	os.Remove(finalMp3)

	var dlArgs []string
	if duracao > 1800 {
		fmt.Printf("[Bridge] ⏱️ Vídeo longo detectado (%.0fs / %.1fh). Recortando automaticamente os primeiros 30 minutos com velocidade máxima (ratebypass)...\n", duracao, duracao/3600.0)
		dlArgs = []string{
			"--extractor-args", "youtube:player_client=android",
			"-f", "18/ba[ext=m4a]/ba/b",
			"--download-sections", "*00:00:00-00:30:00",
			"-x", "--audio-format", "mp3",
			"-o", outputTemplate,
			"--no-playlist",
			job.URL,
		}
		duracao = 1800.0
	} else {
		fmt.Println("[Bridge] ⬇️ Baixando áudio completo e convertendo para MP3...")
		dlArgs = []string{
			"--extractor-args", "youtube:player_client=android,web",
			"-f", "ba/b[ext=mp4]/18",
			"-x", "--audio-format", "mp3",
			"-o", outputTemplate,
			"--no-playlist",
			job.URL,
		}
	}

	cmdDl := exec.Command(ytdlpPath, dlArgs...)
	dlOutput, err := cmdDl.CombinedOutput()
	if err != nil {
		return fmt.Errorf("falha no yt-dlp: %v | Saída: %s", err, string(dlOutput))
	}

	if _, err := os.Stat(finalMp3); os.IsNotExist(err) {
		return fmt.Errorf("arquivo MP3 não gerado em %s", finalMp3)
	}

	defer os.Remove(finalMp3)

	// 3. Envia o arquivo MP3 e metadados para a VPS via POST Multipart
	fmt.Println("[Bridge] ⬆️ Enviando MP3 finalizado para a VPS...")
	return uploadAudio(client, serverURL, secret, job.ID, job.YoutubeID, titulo, duracao, finalMp3)
}

func uploadAudio(client *http.Client, serverURL, secret, jobID, youtubeID, titulo string, duracao float64, mp3Path string) error {
	file, err := os.Open(mp3Path)
	if err != nil {
		return fmt.Errorf("erro ao abrir MP3: %v", err)
	}
	defer file.Close()

	body := &bytes.Buffer{}
	writer := multipart.NewWriter(body)

	_ = writer.WriteField("job_id", jobID)
	_ = writer.WriteField("youtube_id", youtubeID)
	_ = writer.WriteField("titulo", titulo)
	_ = writer.WriteField("duracao", fmt.Sprintf("%.2f", duracao))

	part, err := writer.CreateFormFile("audio", filepath.Base(mp3Path))
	if err != nil {
		return fmt.Errorf("erro ao criar campo de upload: %v", err)
	}

	if _, err := io.Copy(part, file); err != nil {
		return fmt.Errorf("erro ao copiar bytes do arquivo: %v", err)
	}

	if err := writer.Close(); err != nil {
		return fmt.Errorf("erro ao fechar multipart: %v", err)
	}

	submitURL := fmt.Sprintf("%s/index.php/api/bridge/submit", serverURL)
	req, err := http.NewRequest("POST", submitURL, body)
	if err != nil {
		return fmt.Errorf("erro ao criar requisição: %v", err)
	}

	req.Header.Set("Content-Type", writer.FormDataContentType())
	req.Header.Set("X-Bridge-Secret", secret)

	// Timeout maior para upload de arquivo
	uploadClient := &http.Client{Timeout: 90 * time.Second}
	resp, err := uploadClient.Do(req)
	if err != nil {
		return fmt.Errorf("erro no upload para a VPS: %v", err)
	}
	defer resp.Body.Close()

	respBody, _ := io.ReadAll(resp.Body)
	if resp.StatusCode != http.StatusOK {
		return fmt.Errorf("erro na resposta do servidor (HTTP %d): %s", resp.StatusCode, string(respBody))
	}

	return nil
}

func getEnv(key, fallback string) string {
	if val := os.Getenv(key); val != "" {
		return val
	}
	return fallback
}

func reportFail(client *http.Client, serverURL, secret, jobID, errorMsg string) {
	failURL := fmt.Sprintf("%s/index.php/api/bridge/fail", serverURL)
	data := fmt.Sprintf("job_id=%s&error=%s", jobID, errorMsg)
	req, err := http.NewRequest("POST", failURL, strings.NewReader(data))
	if err != nil {
		return
	}
	req.Header.Set("Content-Type", "application/x-www-form-urlencoded")
	req.Header.Set("X-Bridge-Secret", secret)
	resp, err := client.Do(req)
	if err == nil {
		resp.Body.Close()
	}
}

