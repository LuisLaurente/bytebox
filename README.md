#  TecnoVedades - Sistema de Gestión de Productos

Un sistema web moderno para la gestión de productos con funcionalidades avanzadas de filtrado en tiempo real, desarrollado con PHP siguiendo el patrón MVC.

## 🚀 Características Principales

- ✅ **Gestión Completa de Productos**: CRUD completo para productos y sus variantes
- ✅ **Filtrado en Tiempo Real**: Sistema de filtros por precio con AJAX sin recarga de página
- ✅ **Arquitectura MVC**: Separación clara de responsabilidades
- ✅ **Validación Robusta**: Sistema de validación centralizado para datos de entrada
- ✅ **Interfaz Responsive**: Diseño adaptativo para diferentes dispositivos
- ✅ **Separación de Responsabilidades**: CSS y JavaScript en archivos externos

## 🛠️ Tecnologías Utilizadas

### Backend
- **PHP 8+**: Lenguaje principal del servidor
- **MySQL**: Base de datos relacional
- **PDO**: Abstracción de base de datos con prepared statements
- **Custom Router**: Sistema de enrutamiento personalizado

### Frontend
- **HTML5**: Estructura semántica
- **CSS3**: Estilos modernos con Flexbox y Grid
- **JavaScript (ES6+)**: Lógica del cliente con Fetch API
- **AJAX**: Comunicación asíncrona sin recarga de página

### Herramientas de Desarrollo
- **XAMPP**: Servidor local de desarrollo
- **Git**: Control de versiones
- **VS Code**: Editor de código

## 🏗️ Arquitectura del Proyecto

El proyecto sigue el patrón **MVC (Model-View-Controller)** con una estructura limpia y escalable:

```
📁 TecnoVedades/
├── 📁 config/                 # Configuración del sistema
│   ├── 📄 config.php         # Configuración general
│   ├── 📄 database.php       # Configuración de base de datos
│   └── 📁 db/               # Scripts de base de datos
├── 📁 controllers/           # Controladores MVC
│   ├── 📄 BaseController.php # Controlador base
│   ├── 📄 ErrorController.php# Manejo de errores
│   ├── 📄 HomeController.php # Controlador de inicio
│   ├── 📄 ProductoController.php # Controlador de productos
│   └── 📄 VarianteController.php # Controlador de variantes
├── 📁 Core/                  # Núcleo del sistema
│   ├── 📄 autoload.php      # Carga automática de clases
│   ├── 📄 Database.php      # Conexión a base de datos (Singleton)
│   ├── 📄 Router.php        # Sistema de enrutamiento
│   └── 📁 Helpers/          # Clases auxiliares
│       ├── 📄 Sanitizer.php # Sanitización de datos
│       ├── 📄 SessionHelper.php # Manejo de sesiones
│       └── 📄 Validator.php # Validación de datos
├── 📁 Models/               # Modelos de datos
│   ├── 📄 Producto.php      # Modelo de productos
│   └── 📄 VarianteProducto.php # Modelo de variantes
├── 📁 public/               # Archivos públicos
│   ├── 📄 index.php         # Punto de entrada
│   ├── 📁 css/             # Hojas de estilo
│   │   └── 📄 producto-index.css
│   └── 📁 js/              # Scripts de JavaScript
│       └── 📄 producto-filtros.js
└── 📁 views/                # Vistas del sistema
    └── 📁 producto/         # Vistas de productos
        ├── 📄 crear.php     # Formulario de creación
        ├── 📄 editar.php    # Formulario de edición
        └── 📄 index.php     # Lista de productos
```

##  Requisitos del Sistema

- **PHP**: 8.0 o superior
- **MySQL**: 5.7 o superior
- **Apache**: 2.4 o superior (incluido en XAMPP)
- **Navegador**: Compatible con ES6+ (Chrome, Firefox, Safari, Edge)

## 🚀 Instalación y Configuración

### 1. Clonar el Repositorio
```bash
git clone https://github.com/LuisLaurente/tecnovedades.git
cd tecnovedades
```

### 2. Configurar XAMPP
1. Descargar e instalar [XAMPP](https://www.apachefriends.org/)
2. Copiar el proyecto a `C:\xampp\htdocs\tecnovedades`
3. Iniciar Apache y MySQL desde el panel de XAMPP

### 3. Configurar la Base de Datos
1. Abrir **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Crear una nueva base de datos llamada `tecnovedades`
3. Ejecutar el script SQL que se encuentra en `config/db/`

### 4. Configurar Conexión
Editar el archivo `config/database.php` con tus credenciales:
```php
<?php
return [
    'host' => 'localhost',
    'database' => 'tecnovedades',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];
```

### 5. Ejecutar el Proyecto

```bash
php -S localhost:8000 -t public
```

Abrir en el navegador: `http://localhost/tecnovedades`

## 🎯 Funcionalidades Implementadas

###  Gestión de Productos
- **Crear Productos**: Formulario completo con validación
- **Listar Productos**: Vista con paginación y filtros
- **Editar Productos**: Modificación de datos existentes
- **Eliminar Productos**: Confirmación de eliminación

### 🔍 Sistema de Filtros
- **Filtro por Precio**: Rango mínimo y máximo
- **Filtrado AJAX**: Sin recarga de página
- **Validación en Tiempo Real**: Feedback inmediato al usuario
- **Indicadores Visuales**: Muestra filtros activos y resultados

### 🎨 Interfaz de Usuario
- **Diseño Responsive**: Adaptable a móviles y escritorio
- **Feedback Visual**: Loading states y mensajes de error
- **Navegación Intuitiva**: Enlaces y botones claros
- **Accesibilidad**: Etiquetas semánticas y contraste adecuado

## 🔧 Componentes Técnicos

### Router Personalizado
```php
// Ejemplo de ruta
$router->add('/producto/index', 'ProductoController', 'index');
```

### Validación de Datos
```php
// Ejemplo de validación
$validator = new Validator();
$validator->required('nombre', 'El nombre es requerido');
$validator->numeric('precio', 'El precio debe ser numérico');
```

### Conexión a Base de Datos (Singleton)
```php
// Obtener instancia única
$db = Database::getInstance();
$connection = $db->getConnection();
```

### AJAX con Fetch API
```javascript
// Filtrado en tiempo real
fetch('/producto/index?' + params.toString(), {
    method: 'GET',
    headers: {
        'X-Requested-With': 'XMLHttpRequest'
    }
})
.then(response => response.json())
.then(data => {
    // Actualizar interfaz
});
```


## 📚 Guía de Desarrollo

### Agregar Nuevas Funcionalidades
1. **Crear Modelo**: En `Models/` para la lógica de datos
2. **Crear Controlador**: En `controllers/` para la lógica de negocio
3. **Crear Vista**: En `views/` para la interfaz
4. **Registrar Ruta**: En `public/index.php`

### Mejores Prácticas
- ✅ Usar **prepared statements** para consultas SQL
- ✅ Validar **todos los datos** de entrada
- ✅ Separar **lógica de presentación**
- ✅ Manejar **errores** adecuadamente
- ✅ Escribir **código limpio** y comentado

## 🐛 Solución de Problemas

### Errores Comunes
1. **Error de Conexión**: Verificar credenciales en `config/database.php`
2. **404 Not Found**: Comprobar configuración de Apache y .htaccess
3. **AJAX no funciona**: Verificar rutas y headers de petición
4. **Estilos no cargan**: Verificar paths de CSS en las vistas

### Logs de Error
```bash
# Ver logs de Apache
tail -f C:\xampp\apache\logs\error.log

# Ver logs de PHP
tail -f C:\xampp\php\logs\php_error_log
```

## 🤝 Contribución

1. Fork el proyecto
2. Crear una rama para la funcionalidad (`git checkout -b feature/nueva-funcionalidad`)
3. Commit los cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear un Pull Request


Este proyecto está bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para más detalles.

## 👨‍💻 Autores

- GitHub: [@LuisLaurente](https://github.com/LuisLaurente) 
**Luis Laurente**

- GitHub: [@CarlJSL](https://github.com/CarlJSL) **Carlos Sipan**

- GitHub: [@RodrigoSsria](https://github.com/RodrigoSsria) **Rodrigo Sanchú**
- Proyecto: TecnoVedades

##  Agradecimientos

- Comunidad PHP por la documentación y recursos
- XAMPP por el entorno de desarrollo
- Desarrolladores que contribuyen con feedback y mejoras

---

⭐ **¡Si te gustó este proyecto, dale una estrella!** ⭐