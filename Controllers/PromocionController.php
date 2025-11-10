<?php

namespace Controllers;

use Models\Promocion;
use Core\Database;

class PromocionController
{
    private $promocionModel;

    public function __construct()
    {
        $this->promocionModel = new Promocion();
    }

    /**
     * Muestra el listado de todas las promociones.
     */
    public function index()
    {
        $promociones = $this->promocionModel->obtenerTodas();
        require_once __DIR__ . '/../views/promocion/index.php';
    }

    /**
     * Muestra el formulario para crear una nueva promoción.
     */
    public function crear()
    {
        // Aquí podrías cargar datos necesarios para los desplegables, como categorías y productos.
        // $categorias = $this->categoriaModel->obtenerTodas();
        // $productos = $this->productoModel->obtenerTodos();
        require_once __DIR__ . '/../views/promocion/crear.php';
    }

    /**
     * Guarda una nueva promoción en la base de datos.
     */
    public function guardar()
    {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . url('promocion/crear'));
            exit;
        }

        try {
            $datos = [
                'nombre'       => $_POST['nombre'] ?? '',
                'prioridad'    => $_POST['prioridad'] ?? 3,
                'fecha_inicio' => $_POST['fecha_inicio'] ?? '',
                'fecha_fin'    => $_POST['fecha_fin'] ?? '',
                'activo'       => isset($_POST['activo']) ? 1 : 0,
                'acumulable'   => isset($_POST['acumulable']) ? 1 : 0,
                'exclusivo'    => isset($_POST['exclusivo']) ? 1 : 0,
                'condicion'    => $this->construirCondicion($_POST),
                'accion'       => $this->construirAccion($_POST),
                'tipo'         => $_POST['tipo_condicion'] ?? 'general'
            ];

            $resultado = $this->promocionModel->crear($datos);

            if ($resultado) {
                $_SESSION['mensaje'] = "✅ Promoción creada correctamente.";
                header("Location: " . url('promocion/index'));
            } else {
                throw new \Exception("Hubo un problema al guardar la promoción.");
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = "❌ " . $e->getMessage();
            header("Location: " . url('promocion/crear'));
        }
        exit;
    }

    /**
     * Muestra el formulario para editar una promoción existente.
     */
    public function editar($id)
    {
        $promocion = $this->promocionModel->obtenerPorId($id);
        if (!$promocion) {
            $_SESSION['error'] = "❌ Promoción no encontrada.";
            header("Location: " . url('promocion/index'));
            exit;
        }
        // Los datos de condicion y accion ya vienen decodificados desde el modelo.
        include __DIR__ . '/../views/promocion/editar.php';
    }

    /**
     * Actualiza una promoción existente en la base de datos.
     */
    public function actualizar($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . url('promocion/editar/' . $id));
            exit;
        }

        try {
            $promocionExistente = $this->promocionModel->obtenerPorId($id);
            if (!$promocionExistente) {
                throw new \Exception("Promoción no encontrada.");
            }

            // Validar tipo_condicion
            $tipoCondicion = $_POST['tipo_condicion'] ?? null;
            $tiposValidos = [
                'subtotal_minimo',
                'primera_compra',
                'cantidad_producto_identico',
                'cantidad_producto_categoria',
                'cantidad_total_productos',
                'todos'
            ];
            if (!$tipoCondicion || !in_array($tipoCondicion, $tiposValidos)) {
                throw new \Exception("Tipo de condición inválido: " . ($tipoCondicion ?: 'no proporcionado'));
            }

            // Obtener los JSON strings
            $condicionJson = $this->construirCondicion($_POST);
            $accionJson = $this->construirAccion($_POST);

            // Convertir a arrays para validar
            $condicionArray = json_decode($condicionJson, true);
            $accionArray = json_decode($accionJson, true);

            // Validar los arrays
            if (empty($condicionArray['tipo']) || empty($accionArray['tipo'])) {
                error_log("JSON inválido - Condicion: " . $condicionJson . ", Accion: " . $accionJson);
                throw new \Exception("Error al construir la regla de la promoción.");
            }

            // Usar los JSON strings en los datos
            $datos = [
                'nombre'       => $_POST['nombre'] ?? '',
                'prioridad'    => $_POST['prioridad'] ?? 3,
                'fecha_inicio' => $_POST['fecha_inicio'] ?? '',
                'fecha_fin'    => $_POST['fecha_fin'] ?? '',
                'activo'       => isset($_POST['activo']) ? 1 : 0,
                'acumulable'   => isset($_POST['acumulable']) ? 1 : 0,
                'exclusivo'    => isset($_POST['exclusivo']) ? 1 : 0,
                'condicion'    => $condicionJson,  // ← CORREGIDO: Usar el JSON string
                'accion'       => $accionJson,     // ← CORREGIDO: Usar el JSON string
                'tipo'         => $tipoCondicion,  // Forzar consistencia
                'codigo'       => $promocionExistente['codigo']
            ];

            $resultado = $this->promocionModel->actualizar($id, $datos);

            if ($resultado) {
                $_SESSION['mensaje'] = "✅ Promoción actualizada correctamente.";
                header("Location: " . url('promocion/index'));
            } else {
                throw new \Exception("No se pudo actualizar la promoción.");
            }
        } catch (\Exception $e) {
            error_log("Error en actualizar promoción: " . $e->getMessage());
            $_SESSION['error'] = "❌ " . $e->getMessage();
            header("Location: " . url('promocion/editar/' . $id));
        }
        exit;
    }

    /**
     * Construye el array de condición basado en los datos del POST.
     * @return string JSON con la estructura de la condición.
     */
    private function construirCondicion($post)
    {
        $tipoCondicion = $post['tipo_condicion'] ?? '';
        if (empty($tipoCondicion)) {
            error_log("Error: tipo_condicion no proporcionado en POST: " . json_encode($post));
            throw new \Exception("El tipo de condición no se especificó. Por favor, seleccione una regla válida.");
        }

        $condicion = ['tipo' => $tipoCondicion];

        switch ($tipoCondicion) {
            case 'todos':
                break;
            case 'subtotal_minimo':
                if (!isset($post['cond_subtotal_minimo'])) {
                    error_log("Error: cond_subtotal_minimo no proporcionado para subtotal_minimo");
                    throw new \Exception("El monto mínimo del carrito es obligatorio.");
                }
                $condicion['valor'] = (float)($post['cond_subtotal_minimo']);
                if ($condicion['valor'] <= 0) {
                    throw new \Exception("El monto mínimo del carrito debe ser mayor a 0.");
                }
                break;
            case 'primera_compra':
                break;
            case 'cantidad_producto_identico':
                // ✅ CORREGIDO: Usar los nombres reales de las vistas
                if (!isset($post['cond_producto_id']) || !isset($post['accion_cantidad_lleva'])) {
                    error_log("Error: cond_producto_id o accion_cantidad_lleva no proporcionados");
                    throw new \Exception("El ID del producto y la cantidad a llevar son obligatorios.");
                }
                $condicion['producto_id'] = (int)($post['cond_producto_id']);
                // ✅ Para NxM, la condición es llevar al menos la cantidad que se especifica
                $condicion['cantidad_min'] = (int)($post['accion_cantidad_lleva']);
                if ($condicion['producto_id'] <= 0 || $condicion['cantidad_min'] <= 0) {
                    throw new \Exception("El ID del producto y la cantidad mínima deben ser mayores a 0.");
                }
                break;
            case 'cantidad_producto_categoria':
                if (!isset($post['cond_categoria_id']) || !isset($post['cond_cantidad_min_categoria'])) {
                    error_log("Error: cond_categoria_id o cond_cantidad_min_categoria no proporcionados");
                    throw new \Exception("El ID de la categoría y la cantidad mínima son obligatorios.");
                }

                // ✅ VALIDACIÓN EXTRA: Verificar que el ID sea numérico y válido
                $categoriaId = $post['cond_categoria_id'];
                if (!is_numeric($categoriaId) || $categoriaId <= 0) {
                    error_log("Error: cond_categoria_id no es numérico o es inválido: " . $categoriaId);
                    throw new \Exception("El ID de la categoría debe ser un número válido.");
                }

                $condicion['categoria_id'] = (int)$categoriaId;
                $condicion['cantidad_min'] = (int)($post['cond_cantidad_min_categoria']); // ✅ CORRECTO: 'cantidad_min'

                if ($condicion['categoria_id'] <= 0 || $condicion['cantidad_min'] <= 0) {
                    throw new \Exception("El ID de la categoría y la cantidad mínima deben ser mayores a 0.");
                }

                // DEBUG para verificar
                error_log("✅ CONDICIÓN GUARDADA - Categoría ID: " . $condicion['categoria_id'] . ", Cantidad mínima: " . $condicion['cantidad_min']);
                break;
            case 'cantidad_total_productos':
                if (!isset($post['cond_cantidad_total'])) {
                    error_log("Error: cond_cantidad_total no proporcionado");
                    throw new \Exception("La cantidad mínima de productos es obligatoria.");
                }
                $condicion['cantidad_min'] = (int)($post['cond_cantidad_total']);
                if ($condicion['cantidad_min'] <= 0) {
                    throw new \Exception("La cantidad mínima de productos debe ser mayor a 0.");
                }
                break;
            default:
                error_log("Error: Tipo de condición no reconocido: " . $tipoCondicion);
                throw new \Exception("Tipo de condición no reconocido: " . htmlspecialchars($tipoCondicion));
        }

        return json_encode($condicion);
    }

    private function construirAccion($post)
    {
        $tipoAccion = $post['tipo_accion'] ?? '';
        if (empty($tipoAccion)) {
            error_log("Error: tipo_accion no proporcionado en POST: " . json_encode($post));
            throw new \Exception("El tipo de acción no se especificó. Por favor, seleccione una regla válida.");
        }

        $accion = ['tipo' => $tipoAccion];

        switch ($tipoAccion) {
            case 'descuento_porcentaje':
                if (!isset($post['accion_valor_descuento'])) {
                    error_log("Error: accion_valor_descuento no proporcionado");
                    throw new \Exception("El porcentaje de descuento es obligatorio.");
                }
                $accion['valor'] = (float)($post['accion_valor_descuento']);
                if ($accion['valor'] <= 0) {
                    throw new \Exception("El porcentaje de descuento debe ser mayor a 0.");
                }
                break;

            case 'descuento_fijo':
                if (!isset($post['accion_valor_descuento_fijo'])) {
                    error_log("Error: accion_valor_descuento_fijo no proporcionado");
                    throw new \Exception("El monto fijo de descuento es obligatorio.");
                }
                $accion['valor'] = (float)($post['accion_valor_descuento_fijo']);
                if ($accion['valor'] <= 0) {
                    throw new \Exception("El monto fijo de descuento debe ser mayor a 0.");
                }
                break;

            case 'envio_gratis':
                break;
            case 'compra_n_paga_m':
                if (!isset($post['accion_cantidad_lleva']) || !isset($post['accion_cantidad_paga'])) {
                    error_log("Error: accion_cantidad_lleva o accion_cantidad_paga no proporcionados");
                    throw new \Exception("Las cantidades para la promoción N x M son obligatorias.");
                }
                $accion['cantidad_lleva'] = (int)($post['accion_cantidad_lleva']);
                $accion['cantidad_paga'] = (int)($post['accion_cantidad_paga']);
                if ($accion['cantidad_lleva'] <= $accion['cantidad_paga'] || $accion['cantidad_paga'] <= 0) {
                    throw new \Exception("Valores inválidos para la promoción N x M. 'Lleva' debe ser mayor que 'Paga' y ambos deben ser mayores a 0.");
                }
                break;
            case 'compra_n_paga_m_general':
                if (!isset($post['accion_cantidad_lleva_general']) || !isset($post['accion_cantidad_paga_general'])) {
                    error_log("Error: accion_cantidad_lleva_general o accion_cantidad_paga_general no proporcionados");
                    throw new \Exception("Las cantidades para la promoción N x M General son obligatorias.");
                }
                $accion['cantidad_lleva'] = (int)($post['accion_cantidad_lleva_general']);
                $accion['cantidad_paga'] = (int)($post['accion_cantidad_paga_general']);
                if ($accion['cantidad_lleva'] <= $accion['cantidad_paga'] || $accion['cantidad_paga'] <= 0) {
                    throw new \Exception("Valores inválidos para la promoción N x M General. 'Lleva' debe ser mayor que 'Paga' y ambos deben ser mayores a 0.");
                }
                $accion['aplica_a'] = 'menor_valor';
                break;
            case 'descuento_enesima_unidad':
                if (!isset($post['accion_numero_unidad']) || !isset($post['accion_descuento_unidad'])) {
                    error_log("Error: accion_numero_unidad o accion_descuento_unidad no proporcionados");
                    throw new \Exception("La unidad y el descuento para la N-ésima unidad son obligatorios.");
                }
                $accion['numero_unidad'] = (int)($post['accion_numero_unidad']);
                $accion['descuento_unidad'] = (float)($post['accion_descuento_unidad']);
                if ($accion['numero_unidad'] <= 1 || $accion['descuento_unidad'] <= 0) {
                    throw new \Exception("Valores inválidos para el descuento en la N-ésima unidad. La unidad debe ser mayor a 1 y el descuento mayor a 0.");
                }
                break;
            case 'descuento_menor_valor':
                if (!isset($post['accion_valor'])) {
                    error_log("Error: accion_valor no proporcionado");
                    throw new \Exception("El porcentaje de descuento para el producto de menor valor es obligatorio.");
                }
                $accion['valor'] = (float)($post['accion_valor']);
                if ($accion['valor'] <= 0) {
                    throw new \Exception("El porcentaje de descuento para el producto de menor valor debe ser mayor a 0.");
                }
                break;
            case 'descuento_enesimo_producto':
                if (!isset($post['accion_descuento_porcentaje'])) {
                    error_log("Error: accion_descuento_porcentaje no proporcionado");
                    throw new \Exception("El porcentaje de descuento para el producto de menor valor es obligatorio.");
                }
                $accion['valor'] = (float)($post['accion_descuento_porcentaje']);
                if ($accion['valor'] <= 0) {
                    throw new \Exception("El porcentaje de descuento para el producto de menor valor debe ser mayor a 0.");
                }
                // ✅ CORREGIDO: NO cambiar el tipo, mantener consistencia
                break;
            // ✅ AGREGAR ESTE NUEVO CASO
            case 'descuento_producto_mas_barato':
                if (!isset($post['accion_valor'])) {
                    error_log("Error: accion_valor no proporcionado para descuento_producto_mas_barato");
                    throw new \Exception("El porcentaje de descuento para el producto de menor valor es obligatorio.");
                }
                $accion['valor'] = (float)($post['accion_valor']);
                if ($accion['valor'] <= 0) {
                    throw new \Exception("El porcentaje de descuento para el producto de menor valor debe ser mayor a 0.");
                }
                break;
            default:
                error_log("Error: Tipo de acción no reconocido: " . $tipoAccion);
                throw new \Exception("Tipo de acción no reconocido: " . htmlspecialchars($tipoAccion));
        }

        return json_encode($accion);
    }

    /**
     * Elimina una promoción de forma permanente.
     */
    public function eliminar($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('promocion/index'));
            exit;
        }

        if ($this->promocionModel->eliminar($id)) {
            $_SESSION['mensaje'] = "Promoción eliminada correctamente.";
        } else {
            $_SESSION['error'] = "❌ No se pudo eliminar la promoción o no fue encontrada.";
        }
        header('Location: ' . url('promocion/index'));
        exit;
    }

    /**
     * Cambia el estado 'activo' de una promoción.
     */
    public function toggleEstado($id)
    {
        $promocion = $this->promocionModel->obtenerPorId($id);
        if (!$promocion) {
            $_SESSION['error'] = "❌ Promoción no encontrada.";
            header('Location: ' . url('promocion/index'));
            exit;
        }

        $nuevoEstado = $promocion['activo'] ? 0 : 1;
        if ($this->promocionModel->actualizarCampo($id, 'activo', $nuevoEstado)) {
            $_SESSION['mensaje'] = $nuevoEstado ? "✅ Promoción activada." : "☑️ Promoción desactivada.";
        } else {
            $_SESSION['error'] = "❌ Error al cambiar el estado de la promoción.";
        }

        header('Location: ' . url('promocion/index'));
        exit;
    }
}
