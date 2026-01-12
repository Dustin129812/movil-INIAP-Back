<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckPhpConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-php-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('--- Verificación de Configuración de PHP (CLI) ---');

        $loaded_ini = php_ini_loaded_file();
        if ($loaded_ini) {
            $this->line('Archivo php.ini cargado: ' . $loaded_ini);
        } else {
            $this->error('¡No se está cargando ningún archivo php.ini!');
        }

        $curl_cainfo = ini_get('curl.cainfo');
        if ($curl_cainfo) {
            $this->line('Valor de curl.cainfo: ' . $curl_cainfo);
        } else {
            $this->warn('La directiva curl.cainfo está vacía o no definida.');
        }

        $openssl_cafile = ini_get('openssl.cafile');
        if ($openssl_cafile) {
            $this->line('Valor de openssl.cafile: ' . $openssl_cafile);
        } else {
            $this->warn('La directiva openssl.cafile está vacía o no definida.');
        }

        $this->info('--------------------------------------------------');

        return 0;
    }
}
