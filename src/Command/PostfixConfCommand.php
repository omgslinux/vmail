<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;
use Twig\Environment;

#[AsCommand(
    name: 'app:postfix:conf',
    description: 'Deploy postfix templates for setup in /etc/postfix',
)]
class PostfixConfCommand extends Command
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

        $targetDir = '/etc/postfix';

        if (!is_dir($targetDir)) {
            $io->error("Postfix directory does not exist: $targetDir");
            return Command::FAILURE;
        }

        $projectDir = $this->kernel->getProjectDir();
        $templateDir = $projectDir . "/templates/conffiles/postfix";

        if (!is_dir($templateDir)) {
            $io->error("Template directory does not exist: $templateDir");
            return Command::FAILURE;
        }


        $dbUrl = $_ENV['DATABASE_URL'] ?? $this->params->get('database_url');

        $dbParams = parse_url($dbUrl);
        $UGID = 5000;
        $user = 'vmail';


        $isDovecotPresent = false;
        $dovecotDir = '/etc/dovecot';

        // 1. Comprobar existencia del directorio
        if (is_dir($dovecotDir)) {
            // 2. Comprobar estado del servicio
            // Usamos --quiet para que solo nos interese el código de salida (0 = activo)
            $process = new Process(['systemctl', 'is-active', '--quiet', 'dovecot.service']);
            $process->run();

            if ($process->isSuccessful()) {
                $isDovecotPresent = true;
            }
        }

        // Find amavis
        $isAmavisPresent = false;
        $amavisDir = '/etc/amavis';

        // 1. Comprobar existencia del directorio
        if (is_dir($amavisDir)) {
            // 2. Comprobar estado del servicio
            // Amavis a veces se llama amavis o amavisd-new, comprobamos el genérico
            $process = new Process(['systemctl', 'is-active', '--quiet', 'amavis.service']);
            $process->run();

            if ($process->isSuccessful()) {
                $isAmavisPresent = true;
            }
        }

        $isOpendkimPresent = false;
        $opendkimSocket = null;
        $opendkimDir = '/etc/opendkim';

        // 1. Comprobar existencia del directorio
        if (is_dir($opendkimDir)) {
            // 2. Comprobar estado del servicio
            $process = new Process(['systemctl', 'is-active', '--quiet', 'opendkim.service']);
            $process->run();

            if ($process->isSuccessful()) {
                $isOpendkimPresent = true;

                // 3. Detectar el socket configurado usando awk
                // Buscamos la línea que empieza por Socket y extraemos el segundo parámetro
                $awkProcess = new Process(['awk', '/^Socket/ {print $2}', '/etc/opendkim.conf']);
                $awkProcess->run();

                if ($awkProcess->isSuccessful()) {
                    $opendkimSocket = trim($awkProcess->getOutput());
                }
            }
        }

        $vars = [
            'dbuser' => $dbParams['user'] ?? '',
            'dbpass' => $dbParams['pass'] ?? '',
            'dbhost' => $dbParams['host'] ?? '',
            'dbport' => $dbParams['port'] ?? '',
            'dbname' => isset($dbParams['path']) ? ltrim($dbParams['path'], '/') : '',
            'enctype' => 'SHA512-CRYPT',
            'virtual_mailbox_base' => '/var/lib/vmail',
            'UID' => $UGID,
            'GID' => $UGID,
            'user' => $user,
            'group' => $user,
            'dovecot' => $isDovecotPresent,
            'has_amavis' => $isAmavisPresent,
            'has_opendkim' => $isOpendkimPresent,
            'opendkim_socket' => $opendkimSocket,
        ];



        $mysqlDir = $templateDir . '/vmail';
        $targetMysqlDir = $targetDir . '/vmail';

        if (!is_dir($targetMysqlDir)) {
            mkdir($targetMysqlDir, 0755, true);
        }

        // Iterar sobre los archivos .twig en la carpeta mysql
        $finder = new Finder();
        $finder->files()->in($mysqlDir)->name('*.twig');

        foreach ($finder as $file) {
            $targetName = str_replace('.twig', '', $file->getFilename());
            $content = $this->twig->render('conffiles/postfix/vmail/' . $file->getFilename(), $vars);
            file_put_contents($targetMysqlDir . '/' . $targetName, $content);
        }


        $renderedMain = $this->twig->render('conffiles/postfix/_main.cf.twig', $vars);
        $lines = explode("\n", $renderedMain);

        foreach ($lines as $line) {
            $line = trim($line);

            // Saltamos comentarios y líneas vacías
            if (empty($line) || str_starts_with($line, '#')) continue;

            if (str_contains($line, '=')) {
                // Dividimos la línea en máximo 2 partes: parámetro y valor
                $parts = explode('=', $line, 2);
                $parameter = trim($parts[0]);
                $value = isset($parts[1]) ? trim($parts[1]) : '';

                // Si el valor está vacío, o son comillas vacías, usamos -# para resetear
                if ($value === '' || $value === '""' || $value === "''") {
                    $process = new Process(['postconf', '-#', $parameter]);
                } else {
                    // Si hay valor, usamos el -e (edit) con la línea completa
                    $process = new Process(['postconf', '-e', $line]);
                }

                $process->run();
            }
        }

        if (!$isDovecotPresent) {
            $saslSource = $templateDir . '/sasl/smtpd.conf';
            $saslTarget = $targetDir . '/sasl/smtpd.conf'; // Ruta estándar

            if (file_exists($saslSource)) {
                if (!is_dir($targetDir . '/sasl')) mkdir($targetDir . '/sasl', 0755, true);
                copy($saslSource, $saslTarget);
            }
        }

        // 1. Renderizar la plantilla
        $rendered = $this->twig->render('conffiles/postfix/_master.cf.twig', $vars);

        // 2. Procesar líneas para unir los "-o" al servicio correspondiente
        $lines = explode("\n", $rendered);
        $services = [];
        $buffer = "";

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;

            if (str_starts_with($line, '-o')) {
                // Es una opción, la añadimos al servicio actual
                $buffer .= " " . $line;
            } else {
                // Es un nuevo servicio, guardamos el anterior si existe
                if ($buffer !== "") $services[] = $buffer;
                $buffer = $line;
            }
        }
        if ($buffer !== "") $services[] = $buffer;

        // 3. Ejecutar postconf para cada servicio aplanado
        foreach ($services as $serviceLine) {
            // Normalizar espacios (quitar tabuladores o espacios dobles)
            $serviceLine = preg_replace('/\s+/', ' ', $serviceLine);

            $parts = explode(' ', $serviceLine);
            if (count($parts) < 2) continue;

            // Crear el identificador tipo submission/inet
            $identifier = $parts[0] . '/' . $parts[1];

            // Formato final: "submission/inet=submission inet n - n... -o..."
            $finalEntry = $identifier . "=" . $serviceLine;

            $process = new Process(['postconf', '-M', '-e', $finalEntry]);
            $process->run();

            if (!$process->isSuccessful()) {
                $io->error("Error en $identifier: " . $process->getErrorOutput());
            }
        }

        $io->success("Postfix Configuration successful.");

        return Command::SUCCESS;
    }
}
