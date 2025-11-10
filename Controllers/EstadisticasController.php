<?php

namespace Controllers;

class EstadisticasController
{
    public function __construct()
    {
        // Constructor vacío por ahora
    }

    /**
     * Muestra la vista de estadísticas del sitio
     */
    public function index()
    {
        // Aquí podrías obtener datos reales de estadísticas en el futuro
        // Por ahora solo mostramos la vista
        require __DIR__ . '/../views/admin/estadisticas/estadisticas.php';
    }

    /**
     * Método alternativo para mostrar estadísticas (alias de index)
     */
    public function mostrar()
    {
        $this->index();
    }
}
