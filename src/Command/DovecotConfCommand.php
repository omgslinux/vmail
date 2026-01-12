<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;
use Twig\Environment;

#[AsCommand(
    name: 'app:dovecot:conf',
    description: 'Extrae datos de DB, detecta versión de Dovecot y despliega plantillas Twig en /etc/dovecot',
)]
class DovecotConfCommand extends Command
{
    public function __construct(
        private ParameterBagInterface $params,
        private Environment $twig,
        private KernelInterface $kernel
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // 1. Extraer parámetros de conexión a variables
        // Buscamos DATABASE_URL en variables de entorno o parámetros
        $dbUrl = $_ENV['DATABASE_URL'] ?? $this->params->get('database_url');

        $dbParams = parse_url($dbUrl);
        $vars = [
            'db_user' => $dbParams['user'] ?? '',
            'db_pass' => $dbParams['pass'] ?? '',
            'db_host' => $dbParams['host'] ?? '',
            'db_port' => $dbParams['port'] ?? '',
            'db_name' => isset($dbParams['path']) ? ltrim($dbParams['path'], '/') : '',
        ];

        // 2. Averiguar versión de Dovecot
        $process = new Process(['/usr/sbin/dovecot', '--version']);
        $process->run();

        if (!$process->isSuccessful()) {
            $io->error('No se pudo ejecutar /usr/sbin/dovecot --version. ¿Está instalado?');
            return Command::FAILURE;
        }

        // Obtenemos los 3 primeros caracteres (ej: "2.3")
        $version = substr(trim($process->getOutput()), 0, 3);
        $io->note("Versión de Dovecot detectada: $version");

        $vars['dovecot_version'] = $version;

        // 3. Rutas de origen y destino
        $projectDir = $this->kernel->getProjectDir();
        $templateDir = $projectDir . "/templates/dovecot/$version";
        $targetDir = '/etc/dovecot';

        if (!is_dir($templateDir)) {
            $io->error("El directorio de plantillas no existe: $templateDir");
            return Command::FAILURE;
        }

        // Recorrido recursivo de archivos y directorios
        $directoryIterator = new \RecursiveDirectoryIterator($templateDir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $file) {
            $relativeSubPath = $iterator->getSubPathName();
            $destination = $targetDir . DIRECTORY_SEPARATOR . $relativeSubPath;

            if ($file->isDir()) {
                if (!is_dir($destination)) {
                    mkdir($destination, 0755, true);
                    $io->writeln("Directorio creado: $destination");
                }
            } else {
                $content = file_get_contents($file->getRealPath());

                if ($file->getExtension() === 'twig') {
                    // Si es .twig, lo procesamos y quitamos la extensión del nombre final
                    $destination = preg_replace('/\.twig$/', '', $destination);

                    try {
                        $template = $this->twig->createTemplate($content);
                        $content = $template->render($vars);
                    } catch (\Exception $e) {
                        $io->error("Error procesando twig en " . $file->getFilename() . ": " . $e->getMessage());
                        continue;
                    }
                }

                if (file_put_contents($destination, $content) === false) {
                    $io->error("No se pudo escribir en: $destination (¿Faltan permisos de root?)");
                    return Command::FAILURE;
                }

                $io->writeln("Archivo procesado: $destination");
            }
        }

        $io->success("Configuración de Dovecot $version desplegada correctamente.");

        return Command::SUCCESS;
    }
}
