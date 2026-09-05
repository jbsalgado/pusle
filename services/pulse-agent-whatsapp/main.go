package main

import (
	"context"
	"encoding/base64"
	"encoding/json"
	"flag"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"os"
	"os/signal"
	"path/filepath"
	"strings"
	"syscall"
	"time"

	_ "modernc.org/sqlite"

	"github.com/skip2/go-qrcode"
	"go.mau.fi/whatsmeow"
	"go.mau.fi/whatsmeow/proto/waE2E"
	"go.mau.fi/whatsmeow/store/sqlstore"
	"go.mau.fi/whatsmeow/types"
	"go.mau.fi/whatsmeow/types/events"
	waLog "go.mau.fi/whatsmeow/util/log"
	"google.golang.org/protobuf/proto"
)

var (
	flagServer = flag.String("server", "https://catalogos.oncode.app.br", "URL base do Pulse SaaS")
	flagToken  = flag.String("token", "", "Token único do agente da loja")
	flagDataDir = flag.String("data", "./data", "Diretório de armazenamento local da sessão SQLite")

	client   *whatsmeow.Client
	httpClient = &http.Client{Timeout: 30 * time.Second}
)

func main() {
	flag.Parse()

	if *flagToken == "" {
		fmt.Println("❌ ERRO: O parâmetro --token é obrigatório.")
		fmt.Println("Exemplo: pulse-agent --server=https://catalogos.oncode.app.br --token=pba_sua_chave_aqui")
		os.Exit(1)
	}

	*flagServer = strings.TrimRight(*flagServer, "/")

	fmt.Println("==================================================================")
	fmt.Println("  PULSE AGENT WHATSAPP (Edge Go Engine - Whatsmeow)")
	fmt.Println("  Conexão Direta e Residencial por Loja - Zero Custo Meta API")
	fmt.Println("==================================================================")
	fmt.Printf("Servidor VPS : %s\n", *flagServer)
	fmt.Printf("Token Loja   : %s\n\n", *flagToken)

	// Garante que o diretório de dados existe
	if err := os.MkdirAll(*flagDataDir, 0755); err != nil {
		fmt.Printf("❌ Falha ao criar diretório de dados: %v\n", err)
		os.Exit(1)
	}

	// 1. Handshake inicial com a VPS
	fmt.Println("📡 Conectando e autenticando na VPS...")
	if err := fazerHandshake(); err != nil {
		fmt.Printf("❌ Falha no Handshake inicial com a VPS: %v\n", err)
		fmt.Println("Verifique se o token está correto e se o servidor está acessível.")
		os.Exit(1)
	}
	fmt.Println("✅ Handshake concluído com sucesso!")

	// 2. Inicializa o cliente Whatsmeow com banco SQLite local
	dbPath := filepath.Join(*flagDataDir, "whatsapp_session.db")
	dbLog := waLog.Stdout("Database", "ERROR", true)
	container, err := sqlstore.New(context.Background(), "sqlite", "file:"+dbPath+"?_pragma=foreign_keys(1)", dbLog)
	if err != nil {
		fmt.Printf("❌ Falha ao inicializar banco local SQLite: %v\n", err)
		os.Exit(1)
	}

	deviceStore, err := container.GetFirstDevice(context.Background())
	if err != nil {
		fmt.Printf("❌ Falha ao obter dispositivo local: %v\n", err)
		os.Exit(1)
	}

	clientLog := waLog.Stdout("WhatsApp", "INFO", true)
	client = whatsmeow.NewClient(deviceStore, clientLog)
	client.AddEventHandler(eventHandler)

	// Conecta o socket do WhatsApp se já houver sessão gravada
	if client.Store.ID != nil {
		fmt.Println("📲 Sessão prévia encontrada. Conectando ao WhatsApp...")
		if err := client.Connect(); err != nil {
			fmt.Printf("⚠️ Erro ao conectar: %v\n", err)
		}
	} else {
		fmt.Println("ℹ️ Nenhuma sessão pareada. Aguardando comando de QR Code pelo painel web...")
	}

	// 3. Inicia goroutine de Long-Polling para receber tarefas da VPS
	ctx, cancel := context.WithCancel(context.Background())
	go loopLongPolling(ctx)

	// Captura interrupções para encerrar limpo
	sigChan := make(chan os.Signal, 1)
	signal.Notify(sigChan, os.Interrupt, syscall.SIGTERM)
	<-sigChan

	fmt.Println("\n🛑 Encerrando Pulse Agent de forma segura...")
	cancel()
	if client.IsConnected() {
		client.Disconnect()
	}
	fmt.Println("👋 Finalizado.")
}

func eventHandler(rawEvt interface{}) {
	switch evt := rawEvt.(type) {
	case *events.Connected:
		fmt.Println("🟢 WhatsApp CONECTADO com sucesso!")
		var phone, name string
		if client.Store.ID != nil {
			phone = client.Store.ID.User
			name = client.Store.PushName
		}
		_ = notificarStatusVPS("connected", phone, name)

	case *events.LoggedOut:
		fmt.Println("🔴 WhatsApp DESCONECTADO (Sessão encerrada no celular).")
		_ = notificarStatusVPS("disconnected", "", "")

	case *events.Message:
		if evt.Info.IsFromMe {
			return
		}
		remetente := evt.Info.Sender.User
		texto := evt.Message.GetConversation()
		if texto == "" && evt.Message.ExtendedTextMessage != nil {
			texto = evt.Message.ExtendedTextMessage.GetText()
		}
		if texto != "" {
			fmt.Printf("📩 Mensagem recebida de %s: %s\n", remetente, texto)
			go repassarMensagemInbound(remetente, texto, evt.Info.ID)
		}
	}
}

func fazerHandshake() error {
	endpoint := fmt.Sprintf("%s/api/bridge-whatsapp/handshake", *flagServer)
	data := url.Values{}
	data.Set("token", *flagToken)
	data.Set("version", "1.0.0")
	data.Set("os", "go-linux")

	resp, err := httpClient.PostForm(endpoint, data)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		body, _ := io.ReadAll(resp.Body)
		return fmt.Errorf("status HTTP %d: %s", resp.StatusCode, string(body))
	}
	return nil
}

func notificarStatusVPS(status, phone, pushName string) error {
	endpoint := fmt.Sprintf("%s/api/bridge-whatsapp/status", *flagServer)
	data := url.Values{}
	data.Set("token", *flagToken)
	data.Set("status", status)
	data.Set("phone", phone)
	data.Set("push_name", pushName)

	resp, err := httpClient.PostForm(endpoint, data)
	if err != nil {
		return err
	}
	defer resp.Body.Close()
	return nil
}

func loopLongPolling(ctx context.Context) {
	fmt.Println("🔄 Iniciando escuta contínua de tarefas (Long-Polling)...")
	for {
		select {
		case <-ctx.Done():
			return
		default:
		}

		endpoint := fmt.Sprintf("%s/api/bridge-whatsapp/poll?token=%s", *flagServer, url.QueryEscape(*flagToken))
		req, err := http.NewRequestWithContext(ctx, "GET", endpoint, nil)
		if err != nil {
			time.Sleep(2 * time.Second)
			continue
		}

		resp, err := httpClient.Do(req)
		if err != nil {
			time.Sleep(3 * time.Second)
			continue
		}

		var result struct {
			Success bool            `json:"success"`
			Type    string          `json:"type"`
			Data    json.RawMessage `json:"data"`
		}

		body, _ := io.ReadAll(resp.Body)
		resp.Body.Close()

		if json.Unmarshal(body, &result) == nil && result.Success {
			switch result.Type {
			case "command":
				processarComando(result.Data)
			case "send_message":
				processarEnvio(result.Data)
			}
		}

		time.Sleep(1 * time.Second)
	}
}

func processarComando(data json.RawMessage) {
	var cmd struct {
		Action string `json:"action"`
	}
	if err := json.Unmarshal(data, &cmd); err != nil {
		return
	}

	switch cmd.Action {
	case "request_qr":
		fmt.Println("📲 Recebida solicitação de geração de QR Code...")
		gerarQrCode()

	case "disconnect":
		fmt.Println("🔌 Recebida solicitação de desconexão...")
		if client != nil && client.IsConnected() {
			_ = client.Logout(context.Background())
		}
		_ = notificarStatusVPS("disconnected", "", "")
	}
}

func gerarQrCode() {
	if client == nil {
		return
	}

	if client.IsConnected() {
		fmt.Println("ℹ️ Já conectado ao WhatsApp. Não é necessário QR Code.")
		return
	}

	qrChan, _ := client.GetQRChannel(context.Background())
	err := client.Connect()
	if err != nil {
		fmt.Printf("⚠️ Erro ao iniciar conexão do QR: %v\n", err)
		return
	}

	go func() {
		for evt := range qrChan {
			if evt.Event == "code" {
				fmt.Println("⚡ Novo QR Code gerado! Enviando para o painel web...")
				// Converte código em imagem PNG
				pngData, err := qrcode.Encode(evt.Code, qrcode.Medium, 256)
				if err != nil {
					continue
				}
				b64 := base64.StdEncoding.EncodeToString(pngData)
				enviarQrCodeVPS("data:image/png;base64," + b64)
			} else {
				fmt.Printf("Evento de pareamento: %s\n", evt.Event)
			}
		}
	}()
}

func enviarQrCodeVPS(b64 string) {
	endpoint := fmt.Sprintf("%s/api/bridge-whatsapp/qr-code", *flagServer)
	data := url.Values{}
	data.Set("token", *flagToken)
	data.Set("qr_code", b64)

	resp, err := httpClient.PostForm(endpoint, data)
	if err == nil {
		resp.Body.Close()
	}
}

func resolverJidCanonico(ctx context.Context, numeroRaw string) (*types.JID, error) {
	// Remove caracteres não numéricos
	var digits []rune
	for _, r := range numeroRaw {
		if r >= '0' && r <= '9' {
			digits = append(digits, r)
		}
	}
	cleanNum := string(digits)
	if len(cleanNum) == 0 {
		return nil, fmt.Errorf("número vazio ou inválido")
	}

	// Se for número brasileiro de 10 ou 11 dígitos sem DDI, adiciona 55
	if len(cleanNum) == 10 || len(cleanNum) == 11 {
		cleanNum = "55" + cleanNum
	}

	// Gera variantes para o Brasil (DDI 55)
	// Trata a regra do 9º dígito (contas antigas registradas na Meta com 8 dígitos vs contas com 9 dígitos)
	var phonesToCheck []string
	phonesToCheck = append(phonesToCheck, "+"+cleanNum)

	if strings.HasPrefix(cleanNum, "55") {
		// 55 + DDD (2 digitos) + 9 + 8 digitos = 13 digitos
		if len(cleanNum) == 13 && cleanNum[4] == '9' {
			// Variante sem o 9º dígito: 55 + DDD + 8 dígitos
			semNove := cleanNum[:4] + cleanNum[5:]
			phonesToCheck = append(phonesToCheck, "+"+semNove)
		} else if len(cleanNum) == 12 {
			// Variante com o 9º dígito: 55 + DDD + 9 + 8 dígitos
			comNove := cleanNum[:4] + "9" + cleanNum[4:]
			phonesToCheck = append(phonesToCheck, "+"+comNove)
		}
	}

	fmt.Printf("🔍 Consultando registro e JID canônico na Meta para: %v...\n", phonesToCheck)
	respList, err := client.IsOnWhatsApp(ctx, phonesToCheck)
	if err != nil {
		fmt.Printf("⚠️ Erro ao consultar IsOnWhatsApp: %v. Tentando JID direto...\n", err)
		jid := types.NewJID(cleanNum, types.DefaultUserServer)
		return &jid, nil
	}

	for _, r := range respList {
		if r.IsIn {
			fmt.Printf("✅ JID Canônico encontrado com sucesso: %s (Consulta: %s)\n", r.JID.String(), r.Query)
			return &r.JID, nil
		}
	}

	return nil, fmt.Errorf("o número %s não está cadastrado no WhatsApp", cleanNum)
}

func processarEnvio(data json.RawMessage) {
	var msg struct {
		ID            string `json:"id"`
		NumeroDestino string `json:"numero_destino"`
		Tipo          string `json:"tipo"`
		Texto         string `json:"texto"`
		MidiaUrl      string `json:"midia_url"`
	}
	if err := json.Unmarshal(data, &msg); err != nil {
		return
	}

	fmt.Printf("🚀 Processando envio para %s...\n", msg.NumeroDestino)

	if client == nil || !client.IsConnected() {
		ackEnvio(msg.ID, "failed", "", "WhatsApp não está conectado no agente local")
		return
	}

	// 1. Resolve JID Canônico com suporte a LID e 9º dígito
	jid, err := resolverJidCanonico(context.Background(), msg.NumeroDestino)
	if err != nil {
		fmt.Printf("❌ Falha de validação para %s: %v\n", msg.NumeroDestino, err)
		ackEnvio(msg.ID, "failed", "", err.Error())
		return
	}

	// Pausa antiban humanizada
	time.Sleep(2 * time.Second)

	// 2. Simula presença (Digitando...)
	_ = client.SendChatPresence(context.Background(), *jid, types.ChatPresenceComposing, types.ChatPresenceMediaText)
	time.Sleep(1 * time.Second)

	// 3. Envia mensagem para o JID canônico
	waMsg := &waE2E.Message{
		Conversation: proto.String(msg.Texto),
	}

	resp, err := client.SendMessage(context.Background(), *jid, waMsg)
	if err != nil {
		fmt.Printf("❌ Erro ao enviar para %s: %v\n", jid.String(), err)
		ackEnvio(msg.ID, "failed", "", err.Error())
		return
	}

	fmt.Printf("✅ Mensagem entregue com sucesso para %s! ID WA: %s\n", jid.String(), resp.ID)
	ackEnvio(msg.ID, "delivered", resp.ID, "")
}

func ackEnvio(id, status, waID, errMsg string) {
	endpoint := fmt.Sprintf("%s/api/bridge-whatsapp/ack", *flagServer)
	data := url.Values{}
	data.Set("token", *flagToken)
	data.Set("id", id)
	data.Set("status", status)
	data.Set("whatsapp_id", waID)
	data.Set("error", errMsg)

	resp, err := httpClient.PostForm(endpoint, data)
	if err == nil {
		resp.Body.Close()
	}
}

func repassarMensagemInbound(remetente, texto, waID string) {
	endpoint := fmt.Sprintf("%s/api/bridge-whatsapp/inbound", *flagServer)
	data := url.Values{}
	data.Set("token", *flagToken)
	data.Set("from", remetente)
	data.Set("text", texto)
	data.Set("whatsapp_id", waID)

	resp, err := httpClient.PostForm(endpoint, data)
	if err == nil {
		resp.Body.Close()
	}
}
