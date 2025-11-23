<?php

namespace Controllers;

use Models\Cupon;
use Exception;

class CuponController
{
    public function validar()
    {
        // Asegurar que es una petición AJAX
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'mensaje' => 'Método no permitido']);
            exit;
        }

        // Configurar headers para JSON
        header('Content-Type: application/json');

        // Limpiar cualquier output anterior
        if (ob_get_level()) {
            ob_clean();
        }

        $codigo = $_POST['codigo'] ?? '';
        $carrito = $_SESSION['carrito'] ?? [];
        $cliente_id = $_SESSION['cliente_id'] ?? null;

        if (empty($codigo)) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Código de cupón requerido']);
            exit;
        }

        try {
            $cuponModel = new Cupon();
            $cupon = $cuponModel->obtenerPorCodigo($codigo);

            if (!$cupon) {
                echo json_encode(['status' => 'error', 'mensaje' => 'Cupón inválido o vencido']);
                exit;
            }

            // Validaciones básicas (fechas, estado activo)
            $hoy = date('Y-m-d');
            if (!$cupon['activo'] || $hoy < $cupon['fecha_inicio'] || $hoy > $cupon['fecha_fin']) {
                echo json_encode(['status' => 'error', 'mensaje' => 'Cupón inválido o vencido']);
                exit;
            }

            // Validar usos globales
            if ($cupon['limite_uso'] > 0 && $cuponModel->contarUsos($cupon['id']) >= $cupon['limite_uso']) {
                echo json_encode(['status' => 'error', 'mensaje' => 'Este cupón alcanzó su límite de uso.']);
                exit;
            }

            // Validar monto mínimo
            $subtotal = array_sum(array_map(fn($p) => $p['precio'] * $p['cantidad'], $carrito));
            if ($subtotal < $cupon['monto_minimo']) {
                echo json_encode(['status' => 'error', 'mensaje' => 'Monto mínimo no alcanzado.']);
                exit;
            }

            // NUEVA VALIDACIÓN: Categorías aplicables
            if (!empty($cupon['categorias_aplicables'])) {
                $categoriasPermitidas = json_decode($cupon['categorias_aplicables'], true);
                $tieneProductoValido = false;

                foreach ($carrito as $producto) {
                    if (isset($producto['categoria_id']) && in_array($producto['categoria_id'], $categoriasPermitidas)) {
                        $tieneProductoValido = true;
                        break;
                    }
                }

                if (!$tieneProductoValido) {
                    echo json_encode(['status' => 'error', 'mensaje' => 'Cupón no válido para los productos en tu carrito']);
                    return;
                }
            }

            // NUEVA VALIDACIÓN: Público objetivo (solo si hay cliente logueado)
            if ($cliente_id && $cupon['publico_objetivo'] === 'nuevos') {
                // Verificar si es usuario nuevo (sin pedidos anteriores)
                $db = \Core\Database::getInstance()->getConnection();
                $stmt = $db->prepare("SELECT COUNT(*) FROM pedidos WHERE cliente_id = ? AND estado != 'cancelado'");
                $stmt->execute([$cliente_id]);
                $pedidosAnteriores = $stmt->fetchColumn();

                if ($pedidosAnteriores > 0) {
                    echo json_encode(['status' => 'error', 'mensaje' => 'Este cupón es solo para usuarios nuevos']);
                    return;
                }
            }

            // NUEVA VALIDACIÓN: Acumulación con promociones
            if (!($cupon['acumulable_promociones'] ?? 1) && isset($_SESSION['promocion_aplicada'])) {
                echo json_encode([
                    'status' => 'warning',
                    'mensaje' => 'Este cupón no es acumulable con otras promociones. ¿Deseas continuar?',
                    'requiere_confirmacion' => true,
                    'cupon' => $cupon
                ]);
                return;
            }

            // Validación de usuarios autorizados (para checkout posterior)
            if (!empty($cupon['usuarios_autorizados'])) {
                $_SESSION['cupon_aplicado'] = $cupon;
                echo json_encode([
                    'status' => 'success',
                    'mensaje' => 'Cupón aplicado temporalmente. Se validará en el checkout.',
                    'cupon' => $cupon,
                    'advertencia' => 'Este cupón puede estar restringido a clientes específicos.'
                ]);
                exit;
            } else {
                $_SESSION['cupon_aplicado'] = $cupon;
                echo json_encode(['status' => 'success', 'mensaje' => 'Cupón aplicado correctamente.', 'cupon' => $cupon]);
                exit;
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Error interno del servidor']);
            exit;
        }
    }

    /**
     * Remover cupón aplicado
     */
    public function remover()
    {
        // Configurar headers para JSON
        header('Content-Type: application/json');

        // Limpiar cualquier output anterior
        if (ob_get_level()) {
            ob_clean();
        }

        unset($_SESSION['cupon_aplicado']);
        echo json_encode(['status' => 'success', 'mensaje' => 'Cupón removido correctamente']);
        exit;
    }
    /**
     * Eliminar un cupón
     */
    public function eliminar($id)
    {
        // Verificar que sea método POST para seguridad
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('cupon'));
            exit;
        }

        $cuponModel = new Cupon();
        
        // Intentar eliminar
        if ($cuponModel->eliminar($id)) {
            header('Location: ' . url('cupon') . '?success=deleted');
        } else {
            header('Location: ' . url('cupon') . '?error=delete_failed');
        }
        exit;
    }

    /**
     * Mostrar lista de cupones
     */
    public function index()
    {
        $cuponModel = new Cupon();
        $cupones = $cuponModel->obtenerTodos();
        $estadisticas = $cuponModel->obtenerEstadisticas();

        // Calcular estadísticas adicionales para cada cupón
        foreach ($cupones as &$cupon) {
            $cupon['usos_totales'] = $cuponModel->contarUsos($cupon['id']);
            $cupon['estado_vigencia'] = $this->determinarEstadoVigencia($cupon);
        }

        require_once __DIR__ . '/../views/cupon/index.php';
    }

    /**
     * Mostrar formulario de creación
     */
    public function crear()
    {
        // FORZAR zona horaria aquí también
        date_default_timezone_set('America/Lima');
        ini_set('date.timezone', 'America/Lima');
        // Obtener categorías para el formulario
        $categoriaModel = new \Models\Categoria();
        $categorias = $categoriaModel->obtenerTodas();

        require_once __DIR__ . '/../views/cupon/crear.php';
    }

    /**
     * Guardar nuevo cupón
     */
    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('cupon'));
            exit;
        }

        $cuponModel = new Cupon();
        $datos = $this->validarDatos($_POST);

        if (!empty($datos['errores'])) {
            $categoriaModel = new \Models\Categoria();
            $categorias = $categoriaModel->obtenerTodas();
            require_once __DIR__ . '/../views/cupon/crear.php';
            return;
        }

        // Verificar que el código no exista
        if ($cuponModel->existeCodigo($datos['codigo'])) {
            $datos['errores']['codigo'] = 'El código del cupón ya existe';
            $categoriaModel = new \Models\Categoria();
            $categorias = $categoriaModel->obtenerTodas();
            require_once __DIR__ . '/../views/cupon/crear.php';
            return;
        }

        // Procesar usuarios autorizados
        $usuarios_autorizados = null;
        if (!empty($datos['usuarios_autorizados'])) {
            // Convertir string de IDs separados por comas a array JSON
            $ids = array_map('trim', explode(',', $datos['usuarios_autorizados']));
            $ids = array_filter($ids, 'is_numeric'); // Solo números
            $ids = array_map('intval', $ids); // Convertir a enteros
            if (!empty($ids)) {
                $usuarios_autorizados = json_encode(array_values($ids));
            }
        }

        // Procesar categorías aplicables
        $categorias_aplicables = null;
        if (!isset($datos['aplicar_todas_categorias']) && !empty($datos['categorias_aplicables'])) {
            $categorias_aplicables = json_encode($datos['categorias_aplicables']);
        }

        $cuponData = [
            'codigo' => $datos['codigo'],
            'descripcion' => $datos['descripcion'] ?? '',
            'tipo' => $datos['tipo'],
            'valor' => $datos['valor'],
            'monto_minimo' => $datos['monto_minimo'] ?: 0,
            'limite_uso' => $datos['limite_uso'] ?: null,
            'limite_por_usuario' => $datos['limite_por_usuario'] ?: null,
            'usuarios_autorizados' => $usuarios_autorizados,
            'activo' => isset($datos['activo']) ? 1 : 0,
            'fecha_inicio' => $datos['fecha_inicio'],
            'fecha_fin' => $datos['fecha_fin'],
            'categorias_aplicables' => $categorias_aplicables,
            'publico_objetivo' => $datos['publico_objetivo'] ?? 'todos',
            'acumulable_promociones' => isset($datos['acumulable_promociones']) ? 1 : 0
        ];

        if ($cuponModel->crear($cuponData)) {
            header('Location: ' . url('cupon') . '?success=created');
        } else {
            $datos['error'] = 'Error al crear el cupón';
            $categoriaModel = new \Models\Categoria();
            $categorias = $categoriaModel->obtenerTodas();
            require_once __DIR__ . '/../views/cupon/crear.php';
        }
    }

    /**
     * Mostrar formulario de edición
     */
    public function editar($id)
    {
        $cuponModel = new Cupon();
        $cupon = $cuponModel->obtenerPorId($id);

        if (!$cupon) {
            header('Location: ' . url('cupon') . '?error=not_found');
            exit;
        }

        // Obtener categorías para el formulario
        $categoriaModel = new \Models\Categoria();
        $categorias = $categoriaModel->obtenerTodas();

        require_once __DIR__ . '/../views/cupon/editar.php';
    }

    /**
     * Actualizar cupón
     */
    public function actualizar($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('cupon'));
            exit;
        }

        $cuponModel = new Cupon();
        $cupon = $cuponModel->obtenerPorId($id);
        if (!$cupon) {
            header('Location: ' . url('cupon') . '?error=not_found');
            exit;
        }

        $datos = $this->validarDatos($_POST, $id);

        if (!empty($datos['errores'])) {
            $categoriaModel = new \Models\Categoria();
            $categorias = $categoriaModel->obtenerTodas();
            require_once __DIR__ . '/../views/cupon/editar.php';
            return;
        }

        // Verificar que el código no exista (excluyendo el actual)
        if ($cuponModel->existeCodigo($datos['codigo'], $id)) {
            $datos['errores']['codigo'] = 'El código del cupón ya existe';
            $categoriaModel = new \Models\Categoria();
            $categorias = $categoriaModel->obtenerTodas();
            require_once __DIR__ . '/../views/cupon/editar.php';
            return;
        }

        // Procesar usuarios autorizados
        $usuarios_autorizados = null;
        if (!empty($datos['usuarios_autorizados'])) {
            // Convertir string de IDs separados por comas a array JSON
            $ids = array_map('trim', explode(',', $datos['usuarios_autorizados']));
            $ids = array_filter($ids, 'is_numeric'); // Solo números
            $ids = array_map('intval', $ids); // Convertir a enteros
            if (!empty($ids)) {
                $usuarios_autorizados = json_encode(array_values($ids));
            }
        }

        // Procesar categorías aplicables
        $categorias_aplicables = null;
        if (!isset($datos['aplicar_todas_categorias']) && !empty($datos['categorias_aplicables'])) {
            $categorias_aplicables = json_encode($datos['categorias_aplicables']);
        }

        $cuponData = [
            'codigo' => $datos['codigo'],
            'descripcion' => $datos['descripcion'] ?? '',
            'tipo' => $datos['tipo'],
            'valor' => $datos['valor'],
            'monto_minimo' => $datos['monto_minimo'] ?: 0,
            'limite_uso' => $datos['limite_uso'] ?: null,
            'limite_por_usuario' => $datos['limite_por_usuario'] ?: null,
            'usuarios_autorizados' => $usuarios_autorizados,
            'activo' => isset($datos['activo']) ? 1 : 0,
            'fecha_inicio' => $datos['fecha_inicio'],
            'fecha_fin' => $datos['fecha_fin'],
            'categorias_aplicables' => $categorias_aplicables,
            'publico_objetivo' => $datos['publico_objetivo'] ?? 'todos',
            'acumulable_promociones' => isset($datos['acumulable_promociones']) ? 1 : 0
        ];

        if ($cuponModel->actualizar($id, $cuponData)) {
            header('Location: ' . url('cupon') . '?success=updated');
        } else {
            $datos['error'] = 'Error al actualizar el cupón';
            $categoriaModel = new \Models\Categoria();
            $categorias = $categoriaModel->obtenerTodas();
            require_once __DIR__ . '/../views/cupon/editar.php';
        }
    }

    /**
     * Cambiar estado activo/inactivo
     */
    public function toggleEstado($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('cupon'));
            exit;
        }

        $cuponModel = new Cupon();
        $cupon = $cuponModel->obtenerPorId($id);
        if (!$cupon) {
            header('Location: ' . url('cupon') . '?error=not_found');
            exit;
        }

        if ($cuponModel->toggleEstado($id)) {
            header('Location: ' . url('cupon') . '?success=estado_cambiado');
        } else {
            header('Location: ' . url('cupon') . '?error=error_cambio_estado');
        }
    }

    /**
     * Validar datos del formulario
     */
    private function validarDatos($datos, $excluirId = null)
    {
        $errores = [];

        // Código (requerido, alfanumérico)
        if (empty($datos['codigo'])) {
            $errores['codigo'] = 'El código es obligatorio';
        } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $datos['codigo'])) {
            $errores['codigo'] = 'El código solo puede contener letras y números';
        }

        // Descripción (opcional, máximo 255 caracteres)
        if (!empty($datos['descripcion']) && strlen($datos['descripcion']) > 255) {
            $errores['descripcion'] = 'La descripción no puede exceder 255 caracteres';
        }

        // Tipo
        if (empty($datos['tipo'])) {
            $errores['tipo'] = 'El tipo de descuento es obligatorio';
        } elseif (!in_array($datos['tipo'], ['descuento_porcentaje', 'descuento_fijo', 'envio_gratis'])) {
            $errores['tipo'] = 'Tipo de descuento inválido';
        }

        // Valor
        if (empty($datos['valor']) && $datos['valor'] !== '0') {
            $errores['valor'] = 'El valor del descuento es obligatorio';
        } elseif (!is_numeric($datos['valor']) || $datos['valor'] < 0) {
            $errores['valor'] = 'El valor debe ser un número positivo';
        } elseif (in_array($datos['tipo'], ['descuento_porcentaje']) && $datos['valor'] > 100) {
            $errores['valor'] = 'El porcentaje no puede ser mayor a 100%';
        }

        // Fechas
        if (empty($datos['fecha_inicio'])) {
            $errores['fecha_inicio'] = 'La fecha de inicio es obligatoria';
        }
        if (empty($datos['fecha_fin'])) {
            $errores['fecha_fin'] = 'La fecha de fin es obligatoria';
        }
        if (!empty($datos['fecha_inicio']) && !empty($datos['fecha_fin']) && $datos['fecha_inicio'] > $datos['fecha_fin']) {
            $errores['fecha_fin'] = 'La fecha de fin debe ser posterior a la fecha de inicio';
        }

        // Límites (opcionales pero deben ser números positivos si se proporcionan)
        if (!empty($datos['limite_uso']) && (!is_numeric($datos['limite_uso']) || $datos['limite_uso'] <= 0)) {
            $errores['limite_uso'] = 'El límite de uso debe ser un número positivo';
        }
        if (!empty($datos['limite_por_usuario']) && (!is_numeric($datos['limite_por_usuario']) || $datos['limite_por_usuario'] <= 0)) {
            $errores['limite_por_usuario'] = 'El límite por usuario debe ser un número positivo';
        }

        // Monto mínimo
        if (!empty($datos['monto_minimo']) && (!is_numeric($datos['monto_minimo']) || $datos['monto_minimo'] < 0)) {
            $errores['monto_minimo'] = 'El monto mínimo debe ser un número positivo';
        }

        // Público objetivo
        if (!empty($datos['publico_objetivo']) && !in_array($datos['publico_objetivo'], ['todos', 'nuevos', 'usuarios_especificos'])) {
            $errores['publico_objetivo'] = 'Público objetivo inválido';
        }

        // Categorías aplicables
        if (isset($datos['categorias_aplicables']) && !is_array($datos['categorias_aplicables'])) {
            $errores['categorias_aplicables'] = 'Categorías aplicables inválidas';
        }

        return array_merge($datos, ['errores' => $errores]);
    }

    /**
     * Determinar el estado de vigencia de un cupón
     */
    private function determinarEstadoVigencia($cupon)
    {
        $hoy = date('Y-m-d');
        $inicio = $cupon['fecha_inicio'];
        $fin = $cupon['fecha_fin'];

        if (!$cupon['activo']) {
            return 'inactivo';
        } elseif ($hoy < $inicio) {
            return 'pendiente';
        } elseif ($hoy > $fin) {
            return 'expirado';
        } else {
            return 'vigente';
        }
    }

    /**
     * Ver historial de uso de un cupón
     */
    public function historial()
    {
        $id = $_GET['id'] ?? 0;
        if (!$id) {
            header('Location: ' . url('cupon'));
            exit;
        }

        $cuponModel = new Cupon();
        $cupon = $cuponModel->obtenerPorId($id);
        if (!$cupon) {
            header('Location: ' . url('cupon'));
            exit;
        }

        $historial = $cuponModel->obtenerHistorialUso($id);
        $cupon['estado_vigencia'] = $this->determinarEstadoVigencia($cupon);

        require_once __DIR__ . '/../views/cupon/historial.php';
    }

    /**
     * Aplicar cupón en el checkout (para usar en CarritoController o PedidoController)
     */
    public function aplicarEnPedido($codigo, $cliente_id, $monto_total)
    {
        $cuponModel = new Cupon();
        $cupon = $cuponModel->obtenerPorCodigo($codigo);

        if (!$cupon) {
            return ['exito' => false, 'mensaje' => 'Cupón inválido'];
        }

        $validacion = $cuponModel->puedeUsarCupon($cupon['id'], $cliente_id, $monto_total);

        if (!$validacion['valido']) {
            return ['exito' => false, 'mensaje' => $validacion['mensaje']];
        }

        $descuento = 0;

        switch ($cupon['tipo']) {
            case 'descuento_porcentaje':
                $descuento = $monto_total * ($cupon['valor'] / 100);
                break;
            case 'descuento_fijo':
                $descuento = min($cupon['valor'], $monto_total);
                break;
            case 'envio_gratis':
                // El envío gratis no genera descuento monetario directo
                $descuento = 0;
                break;
        }

        return [
            'exito' => true,
            'cupon' => $cupon,
            'descuento' => $descuento,
            'nuevo_total' => max(0, $monto_total - $descuento)
        ];
    }
}
