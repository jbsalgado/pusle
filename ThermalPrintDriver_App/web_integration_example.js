// Exemplo de código JavaScript para sistemas Web

/**
 * Função para imprimir texto na impressora térmica via App Android
 * @param {string} textToPrint - O texto que será impresso
 */
function sendToPrinter(textToPrint) {
  // Codifica os dados para URL para garantir segurança de caracteres especiais
  const encodedData = encodeURIComponent(textToPrint);

  // Constrói a URL do Deep Link
  // Esquema: printapp://print?data=...
  const deepLinkUrl = `printapp://print?data=${encodedData}`;

  console.log("Tentando abrir Deep Link:", deepLinkUrl);

  // Tenta abrir o App
  // Se o app não estiver instalado, nada acontecerá (ou o browser mostrará erro)
  // Em produção, pode-se usar um timeout para redirecionar para a Play Store se falhar
  window.location.href = deepLinkUrl;
}

// Demo de uso
// Quando o usuário clicar em um botão "Imprimir Cupom"
const btnPrint = document.getElementById("btnPrint");
if (btnPrint) {
  btnPrint.addEventListener("click", () => {
    const couponContent =
      "{CMD}RESET\n" +
      "{CMD}ALIGN_CENTER\n" +
      "LOJA EXEMPLO\n" +
      "--------------------------------\n" +
      "{CMD}ALIGN_LEFT\n" +
      "Item A .................. R$ 10,00\n" +
      "Item B .................. R$ 20,00\n" +
      "--------------------------------\n" +
      "{CMD}BOLD_ON\n" +
      "TOTAL ................... R$ 30,00\n" +
      "{CMD}BOLD_OFF\n" +
      "\n\n\n";

    sendToPrinter(couponContent);
  });
}
