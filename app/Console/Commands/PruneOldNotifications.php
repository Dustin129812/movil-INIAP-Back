<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneOldNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:prune-old-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina notificaciones antiguas de la base de datos según la política de retención.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Limpiando notificaciones antiguas...');

        // Política: Borra notificaciones leídas de más de 30 días
        $deletedRead = DB::table('notifications')
            ->whereNotNull('read_at')
            ->where('created_at', '<', now()->subDays(7))
            ->delete();

        // Política: Borra notificaciones no leídas de más de 90 días
        $deletedUnread = DB::table('notifications')
            ->whereNull('read_at')
            ->where('created_at', '<', now()->subDays(14))
            ->delete();

        $this->info("-> Se eliminaron {$deletedRead} notificaciones leídas.");
        $this->info("-> Se eliminaron {$deletedUnread} notificaciones no leídas.");
        $this->info('Limpieza de notificaciones completada.');

        return self::SUCCESS;
    }
}
