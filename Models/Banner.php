<?php

namespace Models;

class Banner
{
    private static function conn()
    {
        return \Core\Database::getInstance()->getConnection();
    }

    /**
     * Devuelve los banners activos de un tipo específico, ordenados.
     * @param string $tipo 'principal', 'secundario_izquierda' o 'secundario_derecha'
     * @return array
     */
    public static function obtenerActivosPorTipo(string $tipo = 'principal'): array
    {
        $conn = self::conn();
        $banners = [];
        try {
            if ($conn instanceof \PDO) {
                $stmt = $conn->prepare("SELECT id, nombre_imagen, orden, enlace FROM banners WHERE activo = 1 AND tipo = :tipo ORDER BY orden ASC");
                $stmt->execute([':tipo' => $tipo]);
                $banners = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } elseif ($conn instanceof \mysqli) {
                $tipoEsc = $conn->real_escape_string($tipo);
                $sql = "SELECT id, nombre_imagen, orden, enlace FROM banners WHERE activo = 1 AND tipo = '$tipoEsc' ORDER BY orden ASC";
                if ($res = $conn->query($sql)) {
                    while ($row = $res->fetch_assoc()) $banners[] = $row;
                    $res->free();
                }
            }
        } catch (\Throwable $e) {
            error_log('Banner::obtenerActivosPorTipo error: ' . $e->getMessage());
        }
        return $banners;
    }

    /**
     * Devuelve todos los banners de un tipo específico (admin).
     * @param string $tipo 'principal', 'secundario_izquierda' o 'secundario_derecha'
     * @return array
     */
    public static function obtenerTodosPorTipo(string $tipo = 'principal'): array
    {
        $conn = self::conn();
        $banners = [];
        try {
            if ($conn instanceof \PDO) {
                $stmt = $conn->prepare("SELECT * FROM banners WHERE tipo = :tipo ORDER BY orden ASC, id DESC");
                $stmt->execute([':tipo' => $tipo]);
                $banners = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } elseif ($conn instanceof \mysqli) {
                $tipoEsc = $conn->real_escape_string($tipo);
                $sql = "SELECT * FROM banners WHERE tipo = '$tipoEsc' ORDER BY orden ASC, id DESC";
                if ($res = $conn->query($sql)) {
                    while ($row = $res->fetch_assoc()) $banners[] = $row;
                    $res->free();
                }
            }
        } catch (\Throwable $e) {
            error_log('Banner::obtenerTodosPorTipo error: ' . $e->getMessage());
        }
        return $banners;
    }

    /**
     * Crear un nuevo banner
     * @param string $nombre_imagen
     * @param string $tipo
     * @param int $orden
     * @param int $activo
     * @return int|null devuelve id insertado o null en error
     */
    public static function crear(string $nombre_imagen, string $tipo = 'principal', int $orden = 0, int $activo = 1, ?string $enlace = null): ?int
    {
        $conn = self::conn();
        try {
            if ($conn instanceof \PDO) {
                $stmt = $conn->prepare("INSERT INTO banners (nombre_imagen, enlace, tipo, orden, activo, creado_en) VALUES (:img, :enlace, :tipo, :ord, :act, NOW())");
                $ok = $stmt->execute([':img' => $nombre_imagen, ':enlace' => $enlace, ':tipo' => $tipo, ':ord' => $orden, ':act' => $activo]);
                if ($ok) return (int)$conn->lastInsertId();
            } elseif ($conn instanceof \mysqli) {
                $imgEsc = $conn->real_escape_string($nombre_imagen);
                $enlaceEsc = $enlace ? "'" . $conn->real_escape_string($enlace) . "'" : 'NULL';
                $tipoEsc = $conn->real_escape_string($tipo);
                $orden = (int)$orden;
                $activo = (int)$activo;
                if ($conn->query("INSERT INTO banners (nombre_imagen, enlace, tipo, orden, activo, creado_en) VALUES ('$imgEsc', $enlaceEsc, '$tipoEsc', $orden, $activo, NOW())")) {
                    return (int)$conn->insert_id;
                }
            }
        } catch (\Throwable $e) {
            error_log('Banner::crear error: ' . $e->getMessage());
        }
        return null;
    }

    public static function obtenerPorId(int $id): ?array
    {
        $conn = self::conn();
        try {
            if ($conn instanceof \PDO) {
                $stmt = $conn->prepare("SELECT * FROM banners WHERE id = ? LIMIT 1");
                $stmt->execute([$id]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                return $row ?: null;
            } elseif ($conn instanceof \mysqli) {
                $id = (int)$id;
                $res = $conn->query("SELECT * FROM banners WHERE id = $id LIMIT 1");
                if ($res && $row = $res->fetch_assoc()) {
                    $res->free();
                    return $row;
                }
            }
        } catch (\Throwable $e) {
            error_log('Banner::obtenerPorId error: ' . $e->getMessage());
        }
        return null;
    }

    public static function actualizarImagen(int $id, string $nombre_imagen): bool
    {
        $conn = self::conn();
        try {
            if ($conn instanceof \PDO) {
                $stmt = $conn->prepare("UPDATE banners SET nombre_imagen = :img WHERE id = :id");
                return $stmt->execute([':img' => $nombre_imagen, ':id' => $id]);
            } elseif ($conn instanceof \mysqli) {
                $imgEsc = $conn->real_escape_string($nombre_imagen);
                $id = (int)$id;
                return (bool)$conn->query("UPDATE banners SET nombre_imagen = '$imgEsc' WHERE id = $id");
            }
        } catch (\Throwable $e) {
            error_log('Banner::actualizarImagen error: ' . $e->getMessage());
        }
        return false;
    }

    public static function eliminar(int $id): ?string
    {
        $conn = self::conn();
        $oldName = null;
        try {
            if ($conn instanceof \PDO) {
                $stmt = $conn->prepare("SELECT nombre_imagen FROM banners WHERE id = ?");
                $stmt->execute([$id]);
                $oldName = $stmt->fetchColumn();
                $del = $conn->prepare("DELETE FROM banners WHERE id = ?");
                $del->execute([$id]);
            }
            elseif ($conn instanceof \mysqli) {
                $res = $conn->query("SELECT nombre_imagen FROM banners WHERE id = $id LIMIT 1");
                if ($res && $row = $res->fetch_assoc()) $oldName = $row['nombre_imagen'];
                $conn->query("DELETE FROM banners WHERE id = $id");
            }
        } catch (\Throwable $e) {
            error_log('Banner::eliminar error: ' . $e->getMessage());
        }
        return $oldName ?: null;
    }

    public static function ordenar(array $ids): bool
    {
        $conn = self::conn();
        try {
            if ($conn instanceof \PDO) {
                $conn->beginTransaction();
                $stmt = $conn->prepare("UPDATE banners SET orden = :pos WHERE id = :id");
                foreach ($ids as $pos => $id) {
                    $stmt->execute([':pos' => (int)$pos, ':id' => (int)$id]);
                }
                $conn->commit();
                return true;
            } elseif ($conn instanceof \mysqli) {
                foreach ($ids as $pos => $id) {
                    $id = (int)$id;
                    $pos = (int)$pos;
                    $conn->query("UPDATE banners SET orden = $pos WHERE id = $id");
                }
                return true;
            }
        } catch (\Throwable $e) {
            if ($conn instanceof \PDO) {
                try { $conn->rollBack(); } catch (\Throwable $x) {}
            }
            error_log('Banner::ordenar error: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * Actualiza el enlace de un banner
     */
    public static function actualizarEnlace(int $id, ?string $enlace): bool
    {
        $conn = self::conn();
        try {
            if ($conn instanceof \PDO) {
                $stmt = $conn->prepare("UPDATE banners SET enlace = :enlace WHERE id = :id");
                return $stmt->execute([':enlace' => $enlace, ':id' => $id]);
            } elseif ($conn instanceof \mysqli) {
                $id = (int)$id;
                $enlaceEsc = $enlace ? "'" . $conn->real_escape_string($enlace) . "'" : 'NULL';
                return (bool)$conn->query("UPDATE banners SET enlace = $enlaceEsc WHERE id = $id");
            }
        } catch (\Throwable $e) {
            error_log('Banner::actualizarEnlace error: ' . $e->getMessage());
        }
        return false;
    }
}

