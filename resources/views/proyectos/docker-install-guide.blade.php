@extends('adminlte::page')

@section('title', 'Guía de Instalación de Docker')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Guía de Instalación de Docker</h1>
        </div>
        <div class="col-sm-6">
            <div class="float-sm-right">                <a href="{{ route('proyectos.docker-troubleshoot') }}" class="btn btn-primary">
                    <i class="fas fa-tools"></i> Volver al Diagnóstico
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
                    <h3 class="card-title">Requisitos del Sistema</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info"></i> Antes de instalar Docker</h5>
                        <p>Asegúrese de que su sistema cumpla con los siguientes requisitos:</p>
                    </div>
                    
                    <div class="card bg-light">
                        <div class="card-body">
                            <h4>Windows</h4>
                            <ul>
                                <li>Windows 10 64-bit: Pro, Enterprise, o Education (Build 16299 o posterior)</li>
                                <li>Windows 11 64-bit</li>
                                <li>Habilitado WSL 2 (Windows Subsystem for Linux)</li>
                                <li>Virtualización habilitada en BIOS/UEFI</li>
                            </ul>
                            
                            <h4>macOS</h4>
                            <ul>
                                <li>macOS Catalina (10.15) o posterior</li>
                                <li>Al menos 4GB de RAM</li>
                            </ul>
                            
                            <h4>Linux</h4>
                            <ul>
                                <li>Kernel 3.10 o superior</li>
                                <li>Ubuntu, Debian, Fedora o CentOS</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Guía de Instalación por Sistema Operativo</h3>
                </div>
                <div class="card-body">
                    <!-- Tabs de navegación -->
                    <ul class="nav nav-tabs mb-3" id="instalacion-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="windows-tab" data-toggle="tab" href="#windows" role="tab">
                                <i class="fab fa-windows mr-1"></i> Windows
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="macos-tab" data-toggle="tab" href="#macos" role="tab">
                                <i class="fab fa-apple mr-1"></i> macOS
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="linux-tab" data-toggle="tab" href="#linux" role="tab">
                                <i class="fab fa-linux mr-1"></i> Linux
                            </a>
                        </li>
                    </ul>
                    
                    <!-- Contenido de los tabs -->
                    <div class="tab-content" id="instalacion-content">
                        <!-- Windows -->
                        <div class="tab-pane fade show active" id="windows" role="tabpanel">
                            <h4>Instalación en Windows</h4>
                            
                            <div class="alert alert-warning">
                                <strong>Nota:</strong> Para Windows Home, necesitará habilitar WSL 2 antes de instalar Docker Desktop.
                            </div>
                              <div class="alert alert-info mb-4">
                                <h5><i class="icon fas fa-magic"></i> Instalación automática</h5>
                                <p>Hemos creado un script que automatiza todo el proceso de instalación de Docker en Windows. El script configurará WSL 2, descargará e instalará Docker Desktop y verificará que todo funcione correctamente.</p>
                                <a href="{{ route('proyectos.docker-auto-install') }}" class="btn btn-primary btn-lg">
                                    <i class="fas fa-rocket mr-1"></i> Usar Instalador Automático
                                </a>
                            </div>
                            
                            <h5>Instalación manual (paso a paso)</h5>
                            <ol>
                                <li>
                                    <strong>Habilitar WSL 2 (Windows Subsystem for Linux)</strong>
                                    <p>Abra PowerShell como administrador y ejecute:</p>
                                    <pre><code>dism.exe /online /enable-feature /featurename:Microsoft-Windows-Subsystem-Linux /all /norestart
dism.exe /online /enable-feature /featurename:VirtualMachinePlatform /all /norestart</code></pre>
                                    <p>Reinicie su ordenador después de ejecutar estos comandos.</p>
                                </li>
                                <li>
                                    <strong>Descargar e instalar el kernel de WSL 2</strong>
                                    <p>Descargue e instale el <a href="https://aka.ms/wsl2kernel" target="_blank">paquete de actualización del kernel de WSL 2 para Windows</a></p>
                                </li>
                                <li>
                                    <strong>Establecer WSL 2 como versión predeterminada</strong>
                                    <p>Abra PowerShell y ejecute:</p>
                                    <pre><code>wsl --set-default-version 2</code></pre>
                                </li>
                                <li>
                                    <strong>Descargar Docker Desktop</strong>
                                    <p>Visite <a href="https://www.docker.com/products/docker-desktop/" target="_blank">https://www.docker.com/products/docker-desktop/</a> y descargue Docker Desktop para Windows.</p>
                                </li>
                                <li>
                                    <strong>Instalar Docker Desktop</strong>
                                    <p>Ejecute el instalador y siga las instrucciones en pantalla. Asegúrese de seleccionar "Use WSL 2 instead of Hyper-V" cuando se le solicite.</p>
                                </li>
                                <li>
                                    <strong>Verificar la instalación</strong>
                                    <p>Abra PowerShell y ejecute:</p>
                                    <pre><code>docker --version
docker-compose --version</code></pre>
                                </li>
                            </ol>
                            
                            <div class="embed-responsive embed-responsive-16by9 mt-4">
                                <iframe class="embed-responsive-item" src="https://www.youtube.com/embed/5RQbdMn04Oc" title="Instalación de Docker en Windows" allowfullscreen></iframe>
                            </div>
                        </div>
                        
                        <!-- macOS -->
                        <div class="tab-pane fade" id="macos" role="tabpanel">
                            <h4>Instalación en macOS</h4>
                            
                            <ol>
                                <li>
                                    <strong>Descargar Docker Desktop</strong>
                                    <p>Visite <a href="https://www.docker.com/products/docker-desktop/" target="_blank">https://www.docker.com/products/docker-desktop/</a> y descargue Docker Desktop para Mac.</p>
                                    <p>Asegúrese de seleccionar la versión correcta según su chip (Intel o Apple Silicon).</p>
                                </li>
                                <li>
                                    <strong>Instalar Docker Desktop</strong>
                                    <p>Arrastre la aplicación Docker a su carpeta de Aplicaciones.</p>
                                </li>
                                <li>
                                    <strong>Iniciar Docker Desktop</strong>
                                    <p>Haga doble clic en la aplicación Docker en su carpeta de Aplicaciones.</p>
                                    <p>Es posible que se le soliciten permisos de administrador durante la primera ejecución.</p>
                                </li>
                                <li>
                                    <strong>Verificar la instalación</strong>
                                    <p>Abra Terminal y ejecute:</p>
                                    <pre><code>docker --version
docker-compose --version</code></pre>
                                </li>
                            </ol>
                            
                            <div class="embed-responsive embed-responsive-16by9 mt-4">
                                <iframe class="embed-responsive-item" src="https://www.youtube.com/embed/SGmFOx5IjOQ" title="Instalación de Docker en macOS" allowfullscreen></iframe>
                            </div>
                        </div>
                        
                        <!-- Linux -->
                        <div class="tab-pane fade" id="linux" role="tabpanel">
                            <h4>Instalación en Ubuntu/Debian</h4>
                            
                            <ol>
                                <li>
                                    <strong>Actualizar los paquetes</strong>
                                    <pre><code>sudo apt update
sudo apt upgrade -y</code></pre>
                                </li>
                                <li>
                                    <strong>Instalar paquetes necesarios</strong>
                                    <pre><code>sudo apt install -y apt-transport-https ca-certificates curl software-properties-common</code></pre>
                                </li>
                                <li>
                                    <strong>Añadir la clave GPG oficial de Docker</strong>
                                    <pre><code>curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo apt-key add -</code></pre>
                                </li>
                                <li>
                                    <strong>Añadir el repositorio de Docker</strong>
                                    <pre><code>sudo add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable"</code></pre>
                                </li>
                                <li>
                                    <strong>Instalar Docker Engine</strong>
                                    <pre><code>sudo apt update
sudo apt install -y docker-ce</code></pre>
                                </li>
                                <li>
                                    <strong>Instalar Docker Compose</strong>
                                    <pre><code>sudo curl -L "https://github.com/docker/compose/releases/download/v2.22.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose</code></pre>
                                </li>
                                <li>
                                    <strong>Añadir usuario al grupo docker</strong>
                                    <pre><code>sudo usermod -aG docker $USER
newgrp docker</code></pre>
                                </li>
                                <li>
                                    <strong>Verificar la instalación</strong>
                                    <pre><code>docker --version
docker-compose --version</code></pre>
                                </li>
                            </ol>
                            
                            <div class="embed-responsive embed-responsive-16by9 mt-4">
                                <iframe class="embed-responsive-item" src="https://www.youtube.com/embed/aMKUuaga85A" title="Instalación de Docker en Linux" allowfullscreen></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Problemas Comunes y Soluciones</h3>
                </div>
                <div class="card-body">
                    <div class="accordion" id="accordionProblems">
                        <!-- Problema 1 -->
                        <div class="card">
                            <div class="card-header" id="headingOne">
                                <h2 class="mb-0">
                                    <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                        Docker no inicia después de la instalación
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseOne" class="collapse" aria-labelledby="headingOne" data-parent="#accordionProblems">
                                <div class="card-body">
                                    <p><strong>Causa posible:</strong> Virtualización no habilitada en BIOS/UEFI o problemas con WSL 2 (Windows).</p>
                                    <p><strong>Solución:</strong></p>
                                    <ul>
                                        <li>Reinicie su computadora y entre en la configuración BIOS/UEFI para habilitar la virtualización (VT-x/AMD-v).</li>
                                        <li>En Windows, reinstale WSL 2 con los comandos mencionados en la guía de instalación.</li>
                                        <li>Reinstale Docker Desktop después de habilitar la virtualización.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Problema 2 -->
                        <div class="card">
                            <div class="card-header" id="headingTwo">
                                <h2 class="mb-0">
                                    <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                        Error "Docker daemon is not running"
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionProblems">
                                <div class="card-body">
                                    <p><strong>Causa posible:</strong> El servicio de Docker no se inició correctamente.</p>
                                    <p><strong>Solución:</strong></p>
                                    <ul>
                                        <li>En Windows: Busque "Docker Desktop" en el menú inicio y ejecútelo manualmente.</li>
                                        <li>En macOS: Busque Docker en la carpeta de Aplicaciones y ejecútelo.</li>
                                        <li>En Linux: Ejecute <code>sudo systemctl start docker</code> para iniciar el servicio.</li>
                                        <li>Si el problema persiste, reinicie su computadora e intente nuevamente.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Problema 3 -->
                        <div class="card">
                            <div class="card-header" id="headingThree">
                                <h2 class="mb-0">
                                    <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                        Error "Permission denied" al ejecutar comandos Docker (Linux)
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordionProblems">
                                <div class="card-body">
                                    <p><strong>Causa posible:</strong> El usuario actual no está en el grupo docker.</p>
                                    <p><strong>Solución:</strong></p>
                                    <ol>
                                        <li>Añada su usuario al grupo docker: <code>sudo usermod -aG docker $USER</code></li>
                                        <li>Cierre sesión y vuelva a iniciar sesión, o ejecute: <code>newgrp docker</code></li>
                                        <li>Intente ejecutar Docker nuevamente: <code>docker --version</code></li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Problema 4 -->
                        <div class="card">
                            <div class="card-header" id="headingFour">
                                <h2 class="mb-0">
                                    <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                        Docker Compose no está disponible después de instalar Docker
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseFour" class="collapse" aria-labelledby="headingFour" data-parent="#accordionProblems">
                                <div class="card-body">
                                    <p><strong>Causa posible:</strong> Docker Compose no se instaló correctamente o no está en el PATH.</p>
                                    <p><strong>Solución:</strong></p>
                                    <ul>
                                        <li>En versiones recientes de Docker Desktop, pruebe usando <code>docker compose</code> (con espacio) en lugar de <code>docker-compose</code>.</li>
                                        <li>Si está usando Linux, instale Docker Compose manualmente:</li>
                                    </ul>
                                    <pre><code>sudo curl -L "https://github.com/docker/compose/releases/download/v2.22.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Recursos Adicionales</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Documentación Oficial</h5>
                                    <p class="card-text">Consulte la documentación oficial de Docker para obtener información más detallada.</p>
                                    <a href="https://docs.docker.com/" target="_blank" class="btn btn-primary">
                                        <i class="fas fa-book mr-1"></i> Documentación Docker
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Tutoriales de Docker</h5>
                                    <p class="card-text">Aprenda a usar Docker con tutoriales paso a paso.</p>
                                    <a href="https://www.docker.com/101-tutorial/" target="_blank" class="btn btn-success">
                                        <i class="fas fa-graduation-cap mr-1"></i> Tutoriales Docker
                                    </a>
                                </div>
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
        .card-header .btn-link {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #eaeaea;
        }
        code {
            color: #d63384;
        }
    </style>
@stop

@section('js')
    <script>
        $(function () {
            // Activar los tooltips
            $('[data-toggle="tooltip"]').tooltip();
            
            // Cambiar el tab activo si viene desde una URL con hash
            var hash = window.location.hash;
            if (hash) {
                $('#instalacion-tabs a[href="' + hash + '"]').tab('show');
            }
            
            // Actualizar la URL cuando cambia el tab
            $('#instalacion-tabs a').on('click', function(e) {
                window.location.hash = $(this).attr('href');
            });
        });
    </script>
@stop
