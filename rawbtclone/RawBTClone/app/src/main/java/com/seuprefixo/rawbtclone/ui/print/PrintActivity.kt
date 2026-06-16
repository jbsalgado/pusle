package com.seuprefixo.rawbtclone.ui.print

import android.content.Intent
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.graphics.Canvas
import android.graphics.Color
import android.graphics.pdf.PdfRenderer
import android.net.Uri
import android.os.Bundle
import android.provider.OpenableColumns
import android.widget.Toast
import androidx.activity.result.contract.ActivityResultContracts
import androidx.activity.viewModels
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.seuprefixo.rawbtclone.bluetooth.BluetoothPrinterManager
import com.seuprefixo.rawbtclone.databinding.ActivityPrintBinding
import com.seuprefixo.rawbtclone.escpos.EscPosBuilder
import com.seuprefixo.rawbtclone.utils.PrinterSettings
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

class PrintActivity : AppCompatActivity() {

    private lateinit var binding: ActivityPrintBinding
    private lateinit var btManager: BluetoothPrinterManager
    private lateinit var settings: PrinterSettings

    private val filePicker = registerForActivityResult(
        ActivityResultContracts.GetContent()
    ) { uri ->
        uri?.let { handleFileUri(it) }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityPrintBinding.inflate(layoutInflater)
        setContentView(binding.root)
        setSupportActionBar(binding.toolbar)
        supportActionBar?.setDisplayHomeAsUpEnabled(true)

        btManager = BluetoothPrinterManager(applicationContext)
        settings  = PrinterSettings(applicationContext)

        // Recebe intent de compartilhamento de outros apps
        handleIncomingIntent()

        setupListeners()
    }

    private fun handleIncomingIntent() {
        when {
            intent?.action == Intent.ACTION_SEND -> {
                val text = intent.getStringExtra(Intent.EXTRA_TEXT)
                if (text != null) {
                    binding.etPrintText.setText(text)
                }
                val uri = intent.getParcelableExtra<Uri>(Intent.EXTRA_STREAM)
                if (uri != null) {
                    handleFileUri(uri)
                }
            }
        }
    }

    private fun setupListeners() {
        binding.btnPickFile.setOnClickListener {
            filePicker.launch("*/*")
        }

        binding.btnPrintText.setOnClickListener {
            val text = binding.etPrintText.text.toString()
            if (text.isBlank()) {
                Toast.makeText(this, "Digite algo para imprimir", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }
            printText(text)
        }
    }

    private fun printText(text: String) {
        lifecycleScope.launch {
            setLoading(true)
            val result = connectAndSend {
                val encoding = settings.encoding.first()
                val columns  = settings.columns.first()
                val autoCut  = settings.autoCut.first()

                EscPosBuilder()
                    .initialize()
                    .align(EscPosBuilder.ALIGN_LEFT)
                    .text(text + "\n", encoding)
                    .apply { if (autoCut) feedAndCut() else feedLines(4) }
                    .build()
            }
            setLoading(false)
            showResult(result)
        }
    }

    private fun handleFileUri(uri: Uri) {
        val mimeType = contentResolver.getType(uri) ?: ""
        val fileName = getFileName(uri)
        binding.tvSelectedFile.text = "Arquivo: $fileName"

        lifecycleScope.launch {
            setLoading(true)
            val result = connectAndSend {
                when {
                    mimeType == "application/pdf" -> renderPdfBytes(uri)
                    mimeType.startsWith("image/") -> renderImageBytes(uri)
                    else -> renderTextFileBytes(uri)
                }
            }
            setLoading(false)
            showResult(result)
        }
    }

    private suspend fun connectAndSend(buildBytes: suspend () -> ByteArray): Result<Unit> {
        return withContext(Dispatchers.IO) {
            try {
                val address = settings.deviceAddress.first()
                    ?: return@withContext Result.failure(Exception("Configure uma impressora primeiro"))

                val device = btManager.getPairedPrinters()
                    .firstOrNull { it.address == address }
                    ?: return@withContext Result.failure(Exception("Impressora não encontrada"))

                val conn = btManager.connect(device)
                if (conn.isFailure) return@withContext conn

                val bytes = buildBytes()
                btManager.sendBytes(bytes).also { btManager.disconnect() }
            } catch (e: Exception) {
                Result.failure(e)
            }
        }
    }

    private suspend fun renderPdfBytes(uri: Uri): ByteArray = withContext(Dispatchers.IO) {
        val fd = contentResolver.openFileDescriptor(uri, "r")!!
        val widthDots = settings.mmToDots(settings.paperWidthMm.first())
        val autoCut   = settings.autoCut.first()
        val builder   = EscPosBuilder().initialize()
        val renderer  = PdfRenderer(fd)

        for (i in 0 until renderer.pageCount) {
            val page = renderer.openPage(i)
            val scale = widthDots.toFloat() / page.width
            val h = (page.height * scale).toInt()
            val bmp = Bitmap.createBitmap(widthDots, h, Bitmap.Config.ARGB_8888)
            Canvas(bmp).drawColor(Color.WHITE)
            page.render(bmp, null, null, PdfRenderer.Page.RENDER_MODE_FOR_PRINT)
            page.close()
            builder.image(bmp, widthDots)
            bmp.recycle()
        }
        renderer.close(); fd.close()
        if (autoCut) builder.feedAndCut() else builder.feedLines(4)
        builder.build()
    }

    private suspend fun renderImageBytes(uri: Uri): ByteArray = withContext(Dispatchers.IO) {
        val stream    = contentResolver.openInputStream(uri)!!
        val bitmap    = BitmapFactory.decodeStream(stream)
        stream.close()
        val widthDots = settings.mmToDots(settings.paperWidthMm.first())
        val autoCut   = settings.autoCut.first()
        EscPosBuilder().initialize()
            .image(bitmap, widthDots)
            .apply { if (autoCut) feedAndCut() else feedLines(4) }
            .build()
            .also { bitmap.recycle() }
    }

    private suspend fun renderTextFileBytes(uri: Uri): ByteArray = withContext(Dispatchers.IO) {
        val text     = contentResolver.openInputStream(uri)!!.bufferedReader().readText()
        val encoding = settings.encoding.first()
        val autoCut  = settings.autoCut.first()
        EscPosBuilder().initialize()
            .text(text + "\n", encoding)
            .apply { if (autoCut) feedAndCut() else feedLines(4) }
            .build()
    }

    private fun getFileName(uri: Uri): String {
        var name = "arquivo"
        contentResolver.query(uri, null, null, null, null)?.use { cursor ->
            val idx = cursor.getColumnIndex(OpenableColumns.DISPLAY_NAME)
            if (cursor.moveToFirst() && idx >= 0) name = cursor.getString(idx)
        }
        return name
    }

    private fun PrinterSettings.mmToDots(mm: Int) = (mm * 8).coerceIn(200, 832)

    private fun setLoading(loading: Boolean) {
        binding.progressBar.visibility = if (loading) android.view.View.VISIBLE else android.view.View.GONE
        binding.btnPrintText.isEnabled  = !loading
        binding.btnPickFile.isEnabled   = !loading
    }

    private fun showResult(result: Result<Unit>) {
        val msg = if (result.isSuccess) "Enviado com sucesso!"
        else "Erro: ${result.exceptionOrNull()?.message}"
        Toast.makeText(this, msg, Toast.LENGTH_LONG).show()
    }

    override fun onSupportNavigateUp(): Boolean {
        onBackPressedDispatcher.onBackPressed()
        return true
    }
}
