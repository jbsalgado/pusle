package com.seuprefixo.rawbtclone.ui.main

import android.Manifest
import android.bluetooth.BluetoothAdapter
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import android.os.Bundle
import android.view.Menu
import android.view.MenuItem
import android.widget.ArrayAdapter
import android.widget.Toast
import androidx.activity.result.contract.ActivityResultContracts
import androidx.activity.viewModels
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import androidx.lifecycle.lifecycleScope
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import com.seuprefixo.rawbtclone.R
import com.seuprefixo.rawbtclone.databinding.ActivityMainBinding
import com.seuprefixo.rawbtclone.ui.print.PrintActivity
import com.seuprefixo.rawbtclone.ui.settings.SettingsActivity
import kotlinx.coroutines.launch

class MainActivity : AppCompatActivity() {

    private lateinit var binding: ActivityMainBinding
    private val viewModel: MainViewModel by viewModels()

    // Launcher para solicitar permissões de Bluetooth
    private val permissionLauncher = registerForActivityResult(
        ActivityResultContracts.RequestMultiplePermissions()
    ) { granted ->
        if (granted.values.all { it }) {
            viewModel.refreshDeviceList()
        } else {
            Toast.makeText(this, "Permissão Bluetooth necessária", Toast.LENGTH_LONG).show()
        }
    }

    // Launcher para ativar Bluetooth
    private val enableBtLauncher = registerForActivityResult(
        ActivityResultContracts.StartActivityForResult()
    ) {
        viewModel.refreshDeviceList()
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)
        setSupportActionBar(binding.toolbar)

        checkPermissionsAndSetup()
        observeUiState()
        setupListeners()
    }

    private fun checkPermissionsAndSetup() {
        val permsNeeded = mutableListOf<String>()

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            if (!hasPermission(Manifest.permission.BLUETOOTH_CONNECT))
                permsNeeded.add(Manifest.permission.BLUETOOTH_CONNECT)
            if (!hasPermission(Manifest.permission.BLUETOOTH_SCAN))
                permsNeeded.add(Manifest.permission.BLUETOOTH_SCAN)
        }

        if (permsNeeded.isNotEmpty()) {
            permissionLauncher.launch(permsNeeded.toTypedArray())
        } else {
            if (!viewModel.btManager.isBluetoothEnabled()) {
                enableBtLauncher.launch(Intent(BluetoothAdapter.ACTION_REQUEST_ENABLE))
            } else {
                viewModel.refreshDeviceList()
            }
        }
    }

    private fun observeUiState() {
        lifecycleScope.launch {
            viewModel.uiState.collect { state ->
                // Atualiza lista de dispositivos
                val names = state.pairedDevices.map { it.name ?: it.address }
                val adapter = ArrayAdapter(
                    this@MainActivity,
                    android.R.layout.simple_list_item_single_choice,
                    names
                )
                binding.listDevices.adapter = adapter

                // Status
                binding.tvStatus.text = state.statusMessage
                binding.tvSavedDevice.text = if (state.savedDeviceName != null)
                    "Última impressora: ${state.savedDeviceName}"
                else
                    "Nenhuma impressora configurada"

                // Botões
                binding.btnConnect.isEnabled    = !state.isLoading && !state.isConnected && state.selectedDevice != null
                binding.btnDisconnect.isEnabled  = state.isConnected
                binding.btnTestPage.isEnabled    = state.isConnected
                binding.btnPrintText.isEnabled   = state.isConnected
                binding.progressBar.visibility   =
                    if (state.isLoading) android.view.View.VISIBLE else android.view.View.GONE

                // Status chip color
                val colorRes = if (state.isConnected)
                    com.google.android.material.R.attr.colorTertiary
                else
                    com.google.android.material.R.attr.colorError
                // aplica apenas texto colorido simples
            }
        }
    }

    private fun setupListeners() {
        binding.listDevices.setOnItemClickListener { _, _, position, _ ->
            val device = viewModel.uiState.value.pairedDevices[position]
            viewModel.selectDevice(device)
            binding.btnConnect.isEnabled = true
        }

        binding.btnConnect.setOnClickListener {
            viewModel.connect()
        }

        binding.btnDisconnect.setOnClickListener {
            viewModel.disconnect()
        }

        binding.btnTestPage.setOnClickListener {
            viewModel.printTestPage()
        }

        binding.btnPrintText.setOnClickListener {
            startActivity(Intent(this, PrintActivity::class.java))
        }

        binding.btnRefresh.setOnClickListener {
            viewModel.refreshDeviceList()
        }
    }

    override fun onCreateOptionsMenu(menu: Menu): Boolean {
        menuInflater.inflate(R.menu.main_menu, menu)
        return true
    }

    override fun onOptionsItemSelected(item: MenuItem): Boolean {
        return when (item.itemId) {
            R.id.action_settings -> {
                startActivity(Intent(this, SettingsActivity::class.java))
                true
            }
            else -> super.onOptionsItemSelected(item)
        }
    }

    private fun hasPermission(permission: String) =
        ContextCompat.checkSelfPermission(this, permission) == PackageManager.PERMISSION_GRANTED
}
