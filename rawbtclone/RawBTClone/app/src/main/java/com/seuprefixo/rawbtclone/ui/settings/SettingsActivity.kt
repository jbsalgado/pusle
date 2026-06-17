package com.seuprefixo.rawbtclone.ui.settings

import android.os.Bundle
import android.widget.ArrayAdapter
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.seuprefixo.rawbtclone.databinding.ActivitySettingsBinding
import com.seuprefixo.rawbtclone.utils.PrinterSettings
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.launch

class SettingsActivity : AppCompatActivity() {

    private lateinit var binding: ActivitySettingsBinding
    private lateinit var settings: PrinterSettings

    private val encodingOptions = listOf("CP860", "CP850", "CP437", "UTF-8", "ISO-8859-1")
    private val paperWidthOptions = listOf(58, 80)
    private val escModelOptions = listOf("GS_V_0", "ESC_*_33", "ESC_X", "ESC_X_4")

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivitySettingsBinding.inflate(layoutInflater)
        setContentView(binding.root)
        setSupportActionBar(binding.toolbar)
        supportActionBar?.setDisplayHomeAsUpEnabled(true)
        title = "Configurações da Impressora"

        settings = PrinterSettings(applicationContext)

        setupSpinners()
        loadCurrentSettings()
        setupSaveButton()
    }

    private fun setupSpinners() {
        binding.spinnerEncoding.adapter = ArrayAdapter(
            this, android.R.layout.simple_spinner_dropdown_item, encodingOptions
        )
        binding.spinnerPaperWidth.adapter = ArrayAdapter(
            this,
            android.R.layout.simple_spinner_dropdown_item,
            paperWidthOptions.map { "${it} mm" }
        )
        binding.spinnerEscModel.adapter = ArrayAdapter(
            this, android.R.layout.simple_spinner_dropdown_item, escModelOptions
        )
    }

    private fun loadCurrentSettings() {
        lifecycleScope.launch {
            val paperMm  = settings.paperWidthMm.first()
            val encoding = settings.encoding.first()
            val escModel = settings.escModel.first()
            val columns  = settings.columns.first()
            val autoCut  = settings.autoCut.first()

            binding.spinnerPaperWidth.setSelection(
                paperWidthOptions.indexOf(paperMm).coerceAtLeast(0)
            )
            binding.spinnerEncoding.setSelection(
                encodingOptions.indexOf(encoding).coerceAtLeast(0)
            )
            binding.spinnerEscModel.setSelection(
                escModelOptions.indexOf(escModel).coerceAtLeast(0)
            )
            binding.etColumns.setText(columns.toString())
            binding.switchAutoCut.isChecked = autoCut
        }
    }

    private fun setupSaveButton() {
        binding.btnSave.setOnClickListener {
            val paperMm  = paperWidthOptions[binding.spinnerPaperWidth.selectedItemPosition]
            val encoding = encodingOptions[binding.spinnerEncoding.selectedItemPosition]
            val escModel = escModelOptions[binding.spinnerEscModel.selectedItemPosition]
            val columns  = binding.etColumns.text.toString().toIntOrNull() ?: 48
            val autoCut  = binding.switchAutoCut.isChecked

            lifecycleScope.launch {
                settings.saveConfig(paperMm, encoding, escModel, columns, autoCut)
                Toast.makeText(
                    this@SettingsActivity,
                    "Configurações salvas!",
                    Toast.LENGTH_SHORT
                ).show()
                finish()
            }
        }
    }

    override fun onSupportNavigateUp(): Boolean {
        onBackPressedDispatcher.onBackPressed()
        return true
    }
}
