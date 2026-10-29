<?php
declare(strict_types=1);

namespace App\Validators;

use Respect\Validation\ValidatorBuilder as v;

final class OrdenesTrabajoValidator {
    private function reglas(): array {
        return [
            'placa_vehiculo' => [
                'regla' => v::stringType()
                ->notBlank()
                ->length(v::between(1, 10))
                ->regex('/^[A-Za-z0-9-]+$/'),
                'mensaje' => 'La placa es obligatoria y debe tener hasta 10 letras, números o guiones.',
            ],
            'propietario' => [
                'regla' => v::stringType()
                ->notBlank()
                ->length(v::between(1, 100)),
                'mensaje' => 'El propietario es obligatorio y no puede superar los 100 caracteres.',
            ],
            'descripcion_falla' => [
                'regla' => v::stringType()
                ->notBlank()
                ->length(v::between(1, 65535)),
                'mensaje' => 'La descripción es obligatoria y no puede superar el tamaño de TEXT.',
            ],
            'costo_estimado' => [
                'regla' => v::numericVal()
                ->between(0, 10000000.00),
                'mensaje' => 'El costo estimado debe ser un número entre 0 y 10000000.00.',
            ],
            'estado' => [
                'regla' => v::stringType()
                ->notBlank()
                ->in(['Pendiente', 'En Proceso', 'Finalizado', 'Entregado']),
                'mensaje' => 'El estado debe ser Pendiente, En Proceso, Finalizado o Entregado.',
            ],
        ];
    }

    public function validate(array $data): array {
        $errores = [];

        foreach ($this->reglas() as $campo => $config) {
            if ($campo === 'estado' && !array_key_exists($campo, $data)) {
                continue;
            }

            $valor = $data[$campo] ?? null;
            $regla = $config['regla'];
            $mensaje = $config['mensaje'];

            if ($regla->validate($valor)->hasFailed()) {
                $errores[$campo] = $mensaje;
            }
        }

        return $errores;
    }
}
