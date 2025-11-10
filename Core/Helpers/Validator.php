<?php

namespace Core\Helpers;

class Validator
{
    public static function isEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function email($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function isRequired($value)
    {
        return trim($value) !== '';
    }

    public static function minLength($value, $min)
    {
        return mb_strlen(trim($value)) >= $min;
    }

    public static function isNumeric($value)
    {
        return is_numeric($value);
    }
    public static function validarTexto($valor, $campo = 'Campo', $max = 100)
    {
        $valor = trim($valor);

        if (!self::isRequired($valor)) {
            throw new \Exception("❌ El campo '$campo' es obligatorio.");
        }

        if (mb_strlen($valor) > $max) {
            throw new \Exception("❌ El campo '$campo' no puede tener más de $max caracteres.");
        }

        return htmlspecialchars($valor);
    }

    public static function generarSlug($texto)
    {
        $slug = strtolower(trim($texto));
        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
        return rtrim($slug, '-');
    }

    /**
     * Validar precio individual
     */
    public static function validarPrecio($precio, $campo = 'precio')
    {
        if ($precio === null || $precio === '') {
            return null; // Precio vacío es válido para filtros
        }

        if (!self::isNumeric($precio)) {
            throw new \Exception("❌ El {$campo} debe ser un valor numérico válido.");
        }

        $precioFloat = (float)$precio;

        if ($precioFloat < 0) {
            throw new \Exception("❌ El {$campo} no puede ser negativo.");
        }

        if ($precioFloat > 999999.99) {
            throw new \Exception("❌ El {$campo} no puede ser mayor a S/ 999,999.99.");
        }

        return $precioFloat;
    }

    /**
     * Validar rango de precios para filtros
     */
    public static function validarRangoPrecios($minPrice, $maxPrice)
    {
        $errores = [];

        try {
            $min = self::validarPrecio($minPrice, 'precio mínimo');
            $max = self::validarPrecio($maxPrice, 'precio máximo');

            // Validar que el rango sea lógico
            if (!is_null($min) && !is_null($max) && $min > $max) {
                $errores[] = "❌ El precio mínimo (S/ {$min}) no puede ser mayor que el precio máximo (S/ {$max}).";
            }

            return [
                'errores' => $errores,
                'min' => $min,
                'max' => $max
            ];
        } catch (\Exception $e) {
            $errores[] = $e->getMessage();
            return [
                'errores' => $errores,
                'min' => null,
                'max' => null
            ];
        }
    }

    /**
     * Validar parámetros de filtro GET
     */
    public static function validarFiltrosGET($filtros)
    {
        $resultado = [
            'errores' => [],
            'filtros_validos' => []
        ];

        // Validar precio mínimo
        if (isset($filtros['min_price']) && $filtros['min_price'] !== '') {
            try {
                $min = self::validarPrecio($filtros['min_price'], 'precio mínimo');
                $resultado['filtros_validos']['min_price'] = $min;
            } catch (\Exception $e) {
                $resultado['errores'][] = $e->getMessage();
            }
        }

        // Validar precio máximo
        if (isset($filtros['max_price']) && $filtros['max_price'] !== '') {
            try {
                $max = self::validarPrecio($filtros['max_price'], 'precio máximo');
                $resultado['filtros_validos']['max_price'] = $max;
            } catch (\Exception $e) {
                $resultado['errores'][] = $e->getMessage();
            }
        }

        // Validar rango si ambos están presentes
        if (
            isset($resultado['filtros_validos']['min_price']) &&
            isset($resultado['filtros_validos']['max_price'])
        ) {

            $min = $resultado['filtros_validos']['min_price'];
            $max = $resultado['filtros_validos']['max_price'];

            if ($min > $max) {
                $resultado['errores'][] = "❌ El precio mínimo (S/ {$min}) no puede ser mayor que el precio máximo (S/ {$max}).";
                // Limpiar filtros inválidos
                unset($resultado['filtros_validos']['min_price']);
                unset($resultado['filtros_validos']['max_price']);
            }
        }

        return $resultado;
    }

    /**
     * Sanitizar valores de precio
     */
    public static function sanitizarPrecio($precio)
    {
        if ($precio === null || $precio === '') {
            return null;
        }

        // Remover caracteres no numéricos excepto punto decimal
        $precio = preg_replace('/[^0-9.]/', '', $precio);

        // Convertir a float y formatear
        $precioFloat = (float)$precio;

        return round($precioFloat, 2);
    }
}
