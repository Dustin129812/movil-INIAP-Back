<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BuildSchemaContext extends Command
{
    protected $signature = 'app:build-schema-context';
    protected $description = 'Genera un contexto de esquema detallado directamente desde la base de datos.';

    public function handle()
    {
        $this->info('Generando contexto de esquema avanzado desde la base de datos...');

        // --- SECCIÓN DE REGLAS DE NEGOCIO Y SINÓNIMOS (¡PERSONALÍZALA!) ---
        $businessRulesAndSynonyms = "
### Reglas de Negocio y Sinónimos Clave ###

- **Sinónimos:**
  - El término 'proyectos' o 'iniciativas' se refiere a la tabla `products`.
  - 'Colaboradores', 'empleados' o 'personal' se refiere a la tabla `users`.
  - 'Departamentos', 'sedes' u 'oficinas' se refiere a la tabla `locations`.
  - 'Tareas' o 'entregables' se refiere a la tabla `activities`.
  - 'Rubros' también puede ser llamado 'categorías' o 'áreas de negocio'.

- **Definiciones de Valores:**
  - En la tabla `users`, la columna `gender` usa 'M' para 'masculino' y 'F' para 'femenino'.
  - En la tabla `incidents`, el estado por defecto es 'Abierta'.

- **Lógica de Búsqueda:**
  - Para cualquier búsqueda de texto libre (nombres de personas, productos, locaciones), usa siempre el operador `ILIKE` con el comodín `%` para encontrar coincidencias parciales y no distinguir mayúsculas/minúsculas. Ejemplo: `WHERE name ILIKE '%valor%'`.

- **Relaciones Importantes:**
  - Un `product` (proyecto) está compuesto por muchas `activities` (tareas).
  - Un `user` puede estar asignado a muchas `activities`.
  - Cada `product` pertenece a un `rubro` y a una `location`.
";

        try {
            $schemaManager = Schema::getConnection()->getDoctrineSchemaManager();
            $tables = $schemaManager->listTableNames();
            $fullContext = "### Contexto Detallado de la Base de Datos ###\n";

            foreach ($tables as $tableName) {
                // Ignorar tablas internas de Laravel
                if (in_array($tableName, ['migrations', 'password_resets', 'failed_jobs', 'cache', 'cache_locks', 'jobs', 'job_batches', 'sessions'])) {
                    continue;
                }

                $fullContext .= "\n--- Tabla: `$tableName` ---\n";
                $columns = $schemaManager->listTableColumns($tableName);
                foreach ($columns as $column) {
                    $columnName = $column->getName();
                    $columnType = $column->getType()->getName();
                    $isNullable = !$column->getNotnull() ? " (opcional)" : "";
                    $fullContext .= "- `$columnName` (tipo: $columnType)$isNullable\n";
                }

                $foreignKeys = $schemaManager->listTableForeignKeys($tableName);
                if (count($foreignKeys) > 0) {
                    $fullContext .= "  Relaciones (Claves Foráneas):\n";
                    foreach ($foreignKeys as $foreignKey) {
                        $localColumns = implode(', ', $foreignKey->getLocalColumns());
                        $foreignTable = $foreignKey->getForeignTableName();
                        $foreignColumns = implode(', ', $foreignKey->getForeignColumns());
                        $fullContext .= "    - `$localColumns` se conecta con `$foreignTable($foreignColumns)`.\n";
                    }
                }
            }

            // Unimos las reglas de negocio con el esquema técnico
            $finalContext = $businessRulesAndSynonyms . $fullContext;

            Storage::disk('local')->put('schema_context.txt', $finalContext);

            $this->info('¡Contexto avanzado del esquema generado exitosamente en storage/app/schema_context.txt!');
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error al generar el contexto del esquema: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
