@extends('adminlte::page')

@section('title', 'Instalación Automatizada de Docker')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Instalación Automatizada de Docker</h1>
        </div>
        <div class="col-sm-6">
            <div class="float-sm-right">
                <a href="{{ route('proyectos.docker-install') }}" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Volver a la guía de instalación
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Script de Instalación Automatizada para Windows</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info-circle"></i> Acerca de esta herramienta</h5>
                        <p>Esta herramienta proporciona un script PowerShell para instalar Docker Desktop automáticamente en sistemas Windows.</p>
                    </div>
                    
                    <div class="alert alert-warning">
                        <h5><i class="icon fas fa-exclamation-triangle"></i> Requisitos previos</h5>
                        <ul>
                            <li>Windows 10/11 (64 bits: Pro, Enterprise o Education) o Windows 10/11 Home con WSL 2</li>
                            <li>Virtualización habilitada en BIOS</li>
                            <li>Al menos 4 GB de RAM</li>
                            <li>Permisos de administrador en su equipo</li>
                        </ul>
                    </div>
                    
                    <div class="card bg-light">
                        <div class="card-header">
                            <h4>Instrucciones</h4>
                        </div>
                        <div class="card-body">
                            <ol>
                                <li>Copie todo el contenido del script de abajo</li>
                                <li>Abra PowerShell como administrador</li>
                                <li>Pegue el script y presione Enter para ejecutarlo</li>
                                <li>Siga las instrucciones que aparecerán en pantalla</li>
                            </ol>
                            
                            <div class="form-group mt-4">
                                <label for="installScript">Script de instalación automática:</label>
                                <div class="input-group">
                                    <textarea id="installScript" class="form-control code-area" style="height: 300px; font-family: monospace;" readonly># Script de instalación automática de Docker para Windows
# Este script instala Docker Desktop en Windows, configura WSL 2 si es necesario,
# y verifica que todo esté funcionando correctamente.

# Función para comprobar si se ejecuta como administrador
function Test-Administrator {
    $user = [Security.Principal.WindowsIdentity]::GetCurrent();
    $principal = New-Object Security.Principal.WindowsPrincipal($user);
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

# Verificar permisos de administrador
if (-not (Test-Administrator)) {
    Write-Host "Este script requiere permisos de administrador. Por favor, ejecute PowerShell como administrador." -ForegroundColor Red
    Write-Host "Presione cualquier tecla para salir..."
    $host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") | Out-Null
    exit
}

Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "  Instalación Automatizada de Docker para Windows" -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host ""

# Verificar versión de Windows
$osInfo = Get-CimInstance -ClassName Win32_OperatingSystem
$osVersion = [Version]::new($osInfo.Version)
$osName = $osInfo.Caption

Write-Host "Sistema operativo detectado: $osName" -ForegroundColor Yellow
Write-Host "Versión: $($osInfo.Version)" -ForegroundColor Yellow
Write-Host ""

if ($osVersion.Major -lt 10 -or ($osVersion.Major -eq 10 -and $osVersion.Build -lt 18362)) {
    Write-Host "ERROR: Se requiere Windows 10 versión 1903 (build 18362) o posterior para instalar Docker Desktop." -ForegroundColor Red
    Write-Host "Su sistema no cumple con los requisitos mínimos." -ForegroundColor Red
    Write-Host "Presione cualquier tecla para salir..."
    $host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") | Out-Null
    exit
}

# Verificar si la virtualización está habilitada
$virtualizationEnabled = Get-CimInstance -ClassName Win32_ComputerSystem | Select-Object -ExpandProperty HypervisorPresent

if (-not $virtualizationEnabled) {
    Write-Host "ADVERTENCIA: La virtualización parece no estar habilitada en su sistema." -ForegroundColor Red
    Write-Host "Es posible que necesite activarla en la BIOS/UEFI antes de continuar." -ForegroundColor Red
    Write-Host ""
    $continue = Read-Host "¿Desea continuar de todos modos? (S/N)"
    
    if ($continue -ne "S" -and $continue -ne "s") {
        Write-Host "Instalación cancelada. Por favor, habilite la virtualización en su BIOS/UEFI e intente nuevamente."
        exit
    }
}

# Comprobar si WSL ya está instalado
$wslInstalled = Get-WindowsOptionalFeature -Online -FeatureName Microsoft-Windows-Subsystem-Linux | Select-Object -ExpandProperty State

if ($wslInstalled -ne "Enabled") {
    Write-Host "Instalando Windows Subsystem for Linux (WSL)..." -ForegroundColor Green
    Enable-WindowsOptionalFeature -Online -FeatureName Microsoft-Windows-Subsystem-Linux -NoRestart
    
    Write-Host "Instalando Virtual Machine Platform..." -ForegroundColor Green
    Enable-WindowsOptionalFeature -Online -FeatureName VirtualMachinePlatform -NoRestart
    
    Write-Host ""
    Write-Host "IMPORTANTE: Se requiere reiniciar el sistema para continuar con la instalación." -ForegroundColor Yellow
    Write-Host "Después de reiniciar, por favor ejecute nuevamente este script." -ForegroundColor Yellow
    
    $restart = Read-Host "¿Desea reiniciar ahora? (S/N)"
    if ($restart -eq "S" -or $restart -eq "s") {
        Restart-Computer
    }
    else {
        Write-Host "Por favor reinicie su sistema y ejecute este script nuevamente."
        exit
    }
}
else {
    Write-Host "WSL ya está instalado. Continuando..." -ForegroundColor Green
}

# Comprobar si se está ejecutando WSL 2
$wslVersion = wsl --status | Select-String "Default Version"

if (-not $wslVersion -or $wslVersion -notlike "*2*") {
    Write-Host "Configurando WSL 2 como versión predeterminada..." -ForegroundColor Green
    
    # Descargar e instalar el kernel de WSL 2
    $wslUpdateUrl = "https://wslstorestorage.blob.core.windows.net/wslblob/wsl_update_x64.msi"
    $wslUpdateInstallerPath = "$env:TEMP\wsl_update_x64.msi"
    
    Write-Host "Descargando el kernel de WSL 2..." -ForegroundColor Green
    Invoke-WebRequest -Uri $wslUpdateUrl -OutFile $wslUpdateInstallerPath
    
    Write-Host "Instalando el kernel de WSL 2..." -ForegroundColor Green
    Start-Process -FilePath "msiexec.exe" -ArgumentList "/i", $wslUpdateInstallerPath, "/quiet", "/norestart" -Wait
    
    # Configurar WSL 2 como predeterminado
    wsl --set-default-version 2
    
    Write-Host "WSL 2 configurado correctamente." -ForegroundColor Green
}
else {
    Write-Host "WSL 2 ya está configurado. Continuando..." -ForegroundColor Green
}

# Verificar si Docker Desktop ya está instalado
$dockerInstalled = Get-WmiObject -Class Win32_Product | Where-Object { $_.Name -like "*Docker Desktop*" }

if ($dockerInstalled) {
    Write-Host "Docker Desktop ya está instalado en su sistema." -ForegroundColor Green
    Write-Host "Verificando que Docker esté en ejecución..."
    
    # Intentar ejecutar un comando Docker para verificar que funcione
    try {
        docker --version | Out-Null
        Write-Host "Docker está instalado y funcionando correctamente." -ForegroundColor Green
    }
    catch {
        Write-Host "Docker está instalado pero no está en ejecución." -ForegroundColor Yellow
        Write-Host "Por favor, inicie Docker Desktop desde el menú inicio y espere a que se inicie completamente." -ForegroundColor Yellow
    }
}
else {
    # Descargar Docker Desktop
    $dockerUrl = "https://desktop.docker.com/win/main/amd64/Docker%20Desktop%20Installer.exe"
    $dockerInstallerPath = "$env:TEMP\DockerDesktopInstaller.exe"
    
    Write-Host "Descargando Docker Desktop..." -ForegroundColor Green
    Invoke-WebRequest -Uri $dockerUrl -OutFile $dockerInstallerPath
    
    # Instalar Docker Desktop
    Write-Host "Instalando Docker Desktop..." -ForegroundColor Green
    Write-Host "Este proceso puede tardar varios minutos. Por favor, sea paciente." -ForegroundColor Yellow
    
    Start-Process -FilePath $dockerInstallerPath -ArgumentList "install", "--quiet", "--accept-license" -Wait
    
    Write-Host "Docker Desktop ha sido instalado." -ForegroundColor Green
    Write-Host "Por favor, espere unos momentos mientras Docker Desktop se inicia por primera vez..." -ForegroundColor Yellow
    
    # Esperar a que Docker Desktop se inicie
    Start-Sleep -Seconds 30
}

Write-Host ""
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "  Verificando la instalación de Docker" -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor Cyan

# Verificar que Docker funcione
try {
    $dockerVersion = docker --version
    Write-Host "Docker está instalado y funcionando correctamente!" -ForegroundColor Green
    Write-Host "Versión: $dockerVersion" -ForegroundColor Green
    
    # Verificar Docker Compose
    $composeVersion = docker compose version
    Write-Host "Docker Compose está disponible: $composeVersion" -ForegroundColor Green
    
    Write-Host ""
    Write-Host "¡Instalación completada con éxito!" -ForegroundColor Green
    Write-Host "Ahora puede utilizar Docker para desplegar sus proyectos." -ForegroundColor Green
}
catch {
    Write-Host "ERROR: No se pudo verificar la instalación de Docker." -ForegroundColor Red
    Write-Host "Por favor, asegúrese de que Docker Desktop esté en ejecución." -ForegroundColor Red
    Write-Host "Si acaba de instalar Docker, es posible que necesite reiniciar su sistema." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Presione cualquier tecla para salir..."
$host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") | Out-Null</textarea>
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="button" id="copyBtn">
                                            <i class="fas fa-copy"></i> Copiar Script
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Este script configurará automáticamente WSL 2, descargará e instalará Docker Desktop, y verificará que todo funcione correctamente.</small>
                            </div>
                            
                            <div class="alert alert-success mt-3">
                                <h5><i class="icon fas fa-check"></i> ¿Qué hace este script?</h5>
                                <ol>
                                    <li>Verifica si tiene Windows 10/11 compatible</li>
                                    <li>Comprueba si la virtualización está habilitada</li>
                                    <li>Instala y configura WSL 2 si es necesario</li>
                                    <li>Descarga e instala Docker Desktop automáticamente</li>
                                    <li>Verifica que la instalación se haya completado correctamente</li>
                                </ol>
                            </div>
                            
                            <div class="mt-4">
                                <h5>Después de la instalación</h5>
                                <p>Una vez que Docker esté instalado y en funcionamiento:</p>
                                <ol>
                                    <li>Reinicie su navegador web</li>
                                    <li>Vuelva a la página de despliegue de proyectos</li>
                                    <li>Ejecute el diagnóstico de Docker para verificar la instalación</li>
                                    <li>¡Ahora podrá desplegar sus proyectos!</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .code-area {
            background-color: #1e1e1e;
            color: #d4d4d4;
            font-family: Consolas, Monaco, 'Andale Mono', 'Ubuntu Mono', monospace;
            font-size: 14px;
            line-height: 1.5;
            padding: 15px;
            border-radius: 4px;
            white-space: pre;
            overflow-x: auto;
        }
    </style>
@stop

@section('js')
    <script>
        $(function() {
            // Botón para copiar el script
            $('#copyBtn').click(function() {
                var scriptText = $('#installScript');
                scriptText.select();
                document.execCommand('copy');
                
                // Cambiar el texto del botón temporalmente
                var $btn = $(this);
                var originalText = $btn.html();
                $btn.html('<i class="fas fa-check"></i> ¡Copiado!');
                
                setTimeout(function() {
                    $btn.html(originalText);
                }, 2000);
            });
        });
    </script>
@stop
