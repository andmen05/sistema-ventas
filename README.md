# Sistema de Gestión de Ventas e Inventario

Sistema completo para la gestión de ventas, inventario y administración de negocios, desarrollado con PHP y MySQL.

## 🚀 Características

- **Gestión de Inventario**
  - Control de stock en tiempo real
  - Registro de productos con códigos únicos
  - Categorización de productos
  - Alertas de stock bajo

- **Sistema de Ventas**
  - Proceso de venta intuitivo
  - Generación de facturas
  - Múltiples métodos de pago
  - Historial de ventas

- **Gestión de Clientes**
  - Base de datos de clientes
  - Historial de compras por cliente
  - Sistema de puntos de fidelización
  - Gestión de información de contacto

- **Panel Administrativo**
  - Dashboard con estadísticas en tiempo real
  - Reportes personalizables
  - Gráficos de ventas y tendencias
  - Control de usuarios y permisos

## 📋 Requisitos Previos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- Extensiones PHP:
  - mysqli
  - mbstring
  - json
  - session

## 🛠️ Instalación

1. **Clonar el repositorio**
   ```powershell
   git clone https://github.com/andmen05/sistema-ventas.git
   cd sistema-ventas
   ```

2. **Configurar la base de datos**
   - Copiar el archivo de configuración de ejemplo:
     ```powershell
     copy config\database.example.php config\database.php
     ```
   - Editar `config/database.php` con tus credenciales de MySQL
   - Importar el esquema inicial:
     ```powershell
     mysql -u tu_usuario -p < config/schema.sql
     ```

3. **Configurar el servidor web**
   - Asegurar que el directorio del proyecto es accesible por el servidor web
   - Configurar los permisos de archivos necesarios:
     ```powershell
     icacls * /grant "IIS_IUSRS:(OI)(CI)F" /T
     icacls uploads /grant "IIS_IUSRS:(OI)(CI)F" /T
     ```

## 🚦 Primeros Pasos

1. Acceder al sistema con las credenciales por defecto:
   - Usuario: admin
   - Contraseña: admin123

2. Cambiar la contraseña del administrador por seguridad

3. Configurar los parámetros básicos del sistema:
   - Información de la empresa
   - Configuración de impuestos
   - Métodos de pago
   - Roles y permisos

## 📁 Estructura del Proyecto

```
sistema-ventas/
├── assets/         # Archivos multimedia y recursos
├── config/         # Archivos de configuración
├── css/           # Hojas de estilo
├── includes/      # Funciones y componentes PHP
├── js/           # Scripts JavaScript
└── pages/        # Páginas del sistema
```

## 🔒 Seguridad

- Autenticación de usuarios
- Sistema de roles y permisos
- Protección contra SQL injection
- Validación de formularios
- Sesiones seguras
- Contraseñas hasheadas

## 📑 Documentacion 

[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/andmen05/sistema-ventas)

## 📄 Licencia

Este proyecto está bajo la Licencia MIT - ver el archivo [LICENSE.md](LICENSE.md) para más detalles.

---
⌨️ por @andmen05
