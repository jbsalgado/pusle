package com.seuprefixo.rawbtclone.utils

import android.content.Context
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.*
import androidx.datastore.preferences.preferencesDataStore
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map

private val Context.dataStore: DataStore<Preferences> by preferencesDataStore(name = "printer_settings")

/**
 * Persiste e lê as preferências da impressora usando Jetpack DataStore.
 */
class PrinterSettings(private val context: Context) {

    companion object {
        val KEY_DEVICE_ADDRESS  = stringPreferencesKey("device_address")
        val KEY_DEVICE_NAME     = stringPreferencesKey("device_name")
        val KEY_PAPER_WIDTH_MM  = intPreferencesKey("paper_width_mm")
        val KEY_ENCODING        = stringPreferencesKey("encoding")
        val KEY_ESC_MODEL       = stringPreferencesKey("esc_model")
        val KEY_COLUMNS         = intPreferencesKey("columns")
        val KEY_AUTO_CUT        = booleanPreferencesKey("auto_cut")

        // Valores padrão
        const val DEFAULT_PAPER_WIDTH = 80   // mm
        const val DEFAULT_ENCODING    = "CP860"
        const val DEFAULT_ESC_MODEL   = "GS_V_0"
        const val DEFAULT_COLUMNS     = 48
    }

    val deviceAddress: Flow<String?> = context.dataStore.data.map { it[KEY_DEVICE_ADDRESS] }
    val deviceName: Flow<String?>    = context.dataStore.data.map { it[KEY_DEVICE_NAME] }

    val paperWidthMm: Flow<Int> = context.dataStore.data.map {
        it[KEY_PAPER_WIDTH_MM] ?: DEFAULT_PAPER_WIDTH
    }

    val encoding: Flow<String> = context.dataStore.data.map {
        it[KEY_ENCODING] ?: DEFAULT_ENCODING
    }

    val escModel: Flow<String> = context.dataStore.data.map {
        it[KEY_ESC_MODEL] ?: DEFAULT_ESC_MODEL
    }

    val columns: Flow<Int> = context.dataStore.data.map {
        it[KEY_COLUMNS] ?: DEFAULT_COLUMNS
    }

    val autoCut: Flow<Boolean> = context.dataStore.data.map {
        it[KEY_AUTO_CUT] ?: true
    }

    suspend fun savePrinter(address: String, name: String) {
        context.dataStore.edit { prefs ->
            prefs[KEY_DEVICE_ADDRESS] = address
            prefs[KEY_DEVICE_NAME]    = name
        }
    }

    suspend fun saveConfig(
        paperWidthMm: Int,
        encoding: String,
        escModel: String,
        columns: Int,
        autoCut: Boolean
    ) {
        context.dataStore.edit { prefs ->
            prefs[KEY_PAPER_WIDTH_MM] = paperWidthMm
            prefs[KEY_ENCODING]       = encoding
            prefs[KEY_ESC_MODEL]      = escModel
            prefs[KEY_COLUMNS]        = columns
            prefs[KEY_AUTO_CUT]       = autoCut
        }
    }

    /** Converte mm para dots (203 dpi padrão para impressoras térmicas) */
    fun mmToDots(mm: Int): Int = (mm * 8).coerceIn(200, 832)
}
