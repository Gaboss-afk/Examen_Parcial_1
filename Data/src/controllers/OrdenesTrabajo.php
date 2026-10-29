<?php
declare(strict_types=1);

namespace App\controllers;

use App\Models\OrdenesTrabajoModel;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class OrdenesTrabajo
{
    private const ESTADOS = ['Pendiente', 'En Proceso', 'Finalizado', 'Entregado'];
    private const CAMPOS = [
        'placa_vehiculo',
        'propietario',
        'descripcion_falla',
        'costo_estimado',
        'estado',
    ];

    public function __construct(private ContainerInterface $container)
    {
    }

    public function read(Request $request, Response $response, array $args): Response
    {
        $this->container->get('eloquent');

        if (!isset($args['id'])) {
            $orders = OrdenesTrabajoModel::query()->orderBy('id')->get();
            return $this->json($response, ['data' => $orders->toArray()], 200);
        }

        $id = filter_var($args['id'], FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            return $this->json($response, ['error' => 'El id debe ser un entero positivo.'], 422);
        }

        $order = OrdenesTrabajoModel::query()->find($id);
        if ($order === null) {
            return $this->json($response, ['error' => 'Orden de trabajo no encontrada.'], 404);
        }

        return $this->json($response, ['data' => $order->toArray()], 200);
    }

    public function create(Request $request, Response $response, array $args): Response
    {
        $this->container->get('eloquent');
        $data = $this->requestData($request);
        if ($data === null) {
            return $this->json($response, ['error' => 'Debe enviar un objeto JSON válido.'], 400);
        }

        $errors = $this->validate($data);
        if ($errors !== []) {
            return $this->json($response, ['errors' => $errors], 422);
        }

        $order = new OrdenesTrabajoModel();
        $order->fill($this->normalize($data));
        $order->save();
        $order->refresh();

        return $this->json($response, ['data' => $order->toArray()], 201)
            ->withHeader('Location', '/api/ordenes_trabajo/' . $order->getKey());
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = filter_var($args['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            return $this->json($response, ['error' => 'El id debe ser un entero positivo.'], 422);
        }

        $this->container->get('eloquent');
        $data = $this->requestData($request);
        if ($data === null) {
            return $this->json($response, ['error' => 'Debe enviar un objeto JSON válido.'], 400);
        }

        $errors = $this->validate($data);
        if ($errors !== []) {
            return $this->json($response, ['errors' => $errors], 422);
        }

        $order = OrdenesTrabajoModel::query()->find($id);
        if ($order === null) {
            return $this->json($response, ['error' => 'Orden de trabajo no encontrada.'], 404);
        }

        $order->fill($this->normalize($data));
        $order->save();
        $order->refresh();

        return $this->json($response, ['data' => $order->toArray()], 200);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = filter_var($args['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            return $this->json($response, ['error' => 'El id debe ser un entero positivo.'], 422);
        }

        $this->container->get('eloquent');
        $order = OrdenesTrabajoModel::query()->find($id);
        if ($order === null) {
            return $this->json($response, ['error' => 'Orden de trabajo no encontrada.'], 404);
        }

        $order->delete();
        return $this->json($response, ['message' => 'Orden de trabajo eliminada correctamente.'], 200);
    }

    private function requestData(Request $request): ?array
    {
        $data = $request->getParsedBody();
        return is_array($data) ? $data : null;
    }

    private function validate(array $data): array
    {
        $errors = [];

        foreach (array_diff(array_keys($data), self::CAMPOS) as $field) {
            $errors[(string) $field] = 'Este campo no se puede enviar para una orden de trabajo.';
        }

        $plate = $data['placa_vehiculo'] ?? null;
        if (!is_string($plate) || !preg_match('/\A[A-Za-z0-9-]{1,10}\z/', trim($plate))) {
            $errors['placa_vehiculo'] = 'La placa es obligatoria y debe tener hasta 10 letras, números o guiones.';
        }

        $owner = $data['propietario'] ?? null;
        if (
            !is_string($owner)
            || trim($owner) === ''
            || !preg_match('/\A.{1,100}\z/us', trim($owner))
        ) {
            $errors['propietario'] = 'El propietario es obligatorio y no puede superar los 100 caracteres.';
        }

        $description = $data['descripcion_falla'] ?? null;
        if (!is_string($description) || trim($description) === '' || strlen(trim($description)) > 65535) {
            $errors['descripcion_falla'] = 'La descripción es obligatoria y no puede superar el tamaño de TEXT.';
        }

        $cost = $data['costo_estimado'] ?? null;
        if (
            !(is_int($cost) || is_float($cost) || (is_string($cost) && is_numeric($cost)))
            || !is_finite((float) $cost)
            || (float) $cost < 0
            || (float) $cost > 99999999.99
        ) {
            $errors['costo_estimado'] = 'El costo estimado debe ser un número entre 0 y 99999999.99.';
        }

        if (array_key_exists('estado', $data) && !in_array($data['estado'], self::ESTADOS, true)) {
            $errors['estado'] = 'El estado debe ser Pendiente, En Proceso, Finalizado o Entregado.';
        }

        return $errors;
    }

    private function normalize(array $data): array
    {
        foreach (['placa_vehiculo', 'propietario', 'descripcion_falla', 'estado'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = trim($data[$field]);
            }
        }
        if (isset($data['costo_estimado'])) {
            $data['costo_estimado'] = (float) $data['costo_estimado'];
        }

        return $data;
    }

    private function json(Response $response, array $payload, int $status): Response
    {
        $response->getBody()->write(json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        ));

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status);
    }
}
