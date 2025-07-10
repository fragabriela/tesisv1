<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckDockerAvailabilityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docker:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica la disponibilidad de Docker y Docker Compose en el sistema';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Verificando la disponibilidad de Docker en el sistema...');
        
        // Comprobar Docker
        $this->output->write('Comprobando instalación de Docker: ');
        exec('docker --version 2>&1', $dockerOutput, $dockerReturnVar);
        
        if ($dockerReturnVar === 0) {
            $this->output->writeln('<info>✓ Instalado</info> - ' . $dockerOutput[0]);
            $dockerInstalled = true;
        } else {
            $this->output->writeln('<error>✗ No disponible</error>');
            $this->error('  Docker no está instalado o no es accesible en el PATH del sistema.');
            $dockerInstalled = false;
        }
        
        // Comprobar Docker Compose (nuevo formato con espacio)
        $this->output->write('Comprobando Docker Compose (formato nuevo): ');
        exec('docker compose version 2>&1', $composeOutput, $composeReturnVar);
        
        $composeNewFormat = false;
        if ($composeReturnVar === 0) {
            $this->output->writeln('<info>✓ Instalado</info> - ' . $composeOutput[0]);
            $composeNewFormat = true;
        } else {
            $this->output->writeln('<error>✗ No disponible</error>');
        }
        
        // Comprobar Docker Compose (formato antiguo con guión)
        $this->output->write('Comprobando Docker Compose (formato antiguo): ');
        exec('docker-compose --version 2>&1', $composeOldOutput, $composeOldReturnVar);
        
        $composeOldFormat = false;
        if ($composeOldReturnVar === 0) {
            $this->output->writeln('<info>✓ Instalado</info> - ' . $composeOldOutput[0]);
            $composeOldFormat = true;
        } else {
            $this->output->writeln('<error>✗ No disponible</error>');
        }
        
        // Comprobar si Docker está en ejecución
        if ($dockerInstalled) {
            $this->output->write('Comprobando si Docker está en ejecución: ');
            exec('docker info 2>&1', $infoOutput, $infoReturnVar);
            
            if ($infoReturnVar === 0) {
                $this->output->writeln('<info>✓ En ejecución</info>');
            } else {
                $this->output->writeln('<error>✗ No en ejecución</error>');
                $this->error('  El daemon de Docker no está en ejecución. Inicie Docker Desktop o el servicio Docker.');
            }
        }
        
        // Diagnóstico y soluciones
        $this->newLine();
        $this->info('Diagnóstico:');
        
        if (!$dockerInstalled) {
            $this->error('- Docker no está instalado. Instale Docker Desktop desde https://www.docker.com/products/docker-desktop/');
        }
        
        if ($dockerInstalled && !$composeNewFormat && !$composeOldFormat) {
            $this->error('- Docker Compose no está disponible. Viene incluido con Docker Desktop, o puede instalarlo por separado.');
        }
        
        if ($dockerInstalled && ($composeNewFormat || $composeOldFormat) && $infoReturnVar !== 0) {
            $this->error('- Docker está instalado pero no está en ejecución. Inicie Docker Desktop o el servicio Docker.');
        }
        
        // Mensaje final
        $this->newLine();
        if ($dockerInstalled && ($composeNewFormat || $composeOldFormat) && $infoReturnVar === 0) {
            $this->info('Docker está correctamente configurado y listo para desplegar proyectos.');
            return 0;
        } else {
            $this->error('Se encontraron problemas con la configuración de Docker. Resuelva los problemas mencionados para poder desplegar proyectos.');
            return 1;
        }
    }
}
