<?php

namespace Modules\DireccionInvestigaciones\Services\Protocolos;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\DireccionInvestigaciones\Entities\Protocolos\IdiProtocol;
use Modules\DireccionInvestigaciones\Entities\Protocolos\ProtocolAnnex;

class IdiProtocolService
{
    /**
     * Crea un protocolo completo con sus relaciones y anexos de forma atómica.
     */
    public function createProtocol(array $data, array $collaborators, array $cantons, ?array $annexes): IdiProtocol
    {
        return DB::transaction(function () use ($data, $collaborators, $cantons, $annexes) {

            // 1. Crear el registro base del protocolo
            $protocol = IdiProtocol::create($data);

            // 2. Sincronizar colaboradores internos (Tabla pivote user_protocol)
            if (!empty($collaborators)) {
                $protocol->collaborators()->sync($collaborators);
            }

            // 3. Sincronizar cantones de influencia (Tabla pivote canton_protocol)
            if (!empty($cantons)) {
                $protocol->influenceCantons()->sync($cantons);
            }

            // 4. Procesar archivos adjuntos de forma segura
            if (!empty($annexes)) {
                $this->processAnnexes($protocol, $annexes);
            }

            // Devolver el modelo con las relaciones cargadas para el API Resource
            return $protocol->load(['responsible', 'station', 'collaborators', 'influenceCantons', 'annexes']);
        });
    }

    /**
     * Procesa y almacena los anexos en el disco privado.
     */
    private function processAnnexes(IdiProtocol $protocol, array $files): void
    {
        foreach ($files as $file) {
            $uuid = Str::uuid();
            $extension = $file->getClientOriginalExtension();

            $storedName = "protocolos/{$protocol->id}/{$uuid}.{$extension}";

            $file->storeAs('', $storedName, 'private');

            ProtocolAnnex::create([
                'protocol_id' => $protocol->id,
                'file_name'   => $file->getClientOriginalName(),
                'file_path'   => $storedName,
                'file_type'   => $file->getMimeType(),
                'file_size'   => $file->getSize(),
            ]);
        }
    }
}
