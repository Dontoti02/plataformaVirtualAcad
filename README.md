# Plataforma Virtual Académica

Plataforma integral de gestión académica y aula virtual para Institutos de Educación Superior Tecnológica (IESTP), diseñada para cumplir con la normativa y lineamientos del **MINEDU**.

---

## 🏛️ Estructura del Proyecto

El repositorio contiene todos los componentes necesarios para el despliegue y desarrollo del sistema:

* **`/backend`**: API REST modular construida sobre Laravel 10 / PHP 8.2.
  * Módulos académicos: Matrículas, Planes de Estudio, Aulas y Clases, Evaluaciones y Calificaciones, Horarios, Asistencia, Historial Académico, Lista de Mérito, Bolsa Laboral y Gestión de Usuarios.
  * Roles MINEDU integrados:
    * `DIRECTOR GENERAL` (ID: 9)
    * `JEFE DE UNIDAD ACADÉMICA` (ID: 10)
    * `COORDINADOR ACADÉMICO` (ID: 11)
    * `ADMINISTRADOR` (ID: 4)
    * `SECRETARIO ACADÉMICO` (ID: 1)
    * `DOCENTE` (ID: 2)
    * `ESTUDIANTE` (ID: 3)
* **`/frontend`**: Interfaz de usuario (Single Page Application - SPA) basada en Vue 3, Vite y Vuetify.
  * Filtro por ciclo y periodo académico en Lista de Mérito.
  * Tableros estadísticos según perfil y permisos.
* **`/docker`**: Configuración de contenedores y orquestación con Docker Compose:
  * `docker-compose.yml`: Servicios de Caddy (Reverse Proxy con TLS automático), PHP-FPM, Colas (Worker), Programador de tareas (Cron Scheduler), MySQL 8.0 y Redis.
  * `Caddyfile`: Configuración del servidor web.
  * `mysql-init/`: Scripts de inicialización de base de datos y auditoría.

---

## 🚀 Requisitos de Despliegue

* **Docker** >= 24.0
* **Docker Compose** >= 2.20
* **PHP** >= 8.2 (para desarrollo local sin Docker)
* **Composer** >= 2.5
* **MySQL** >= 8.0

---

## ⚙️ Configuración y Puesta en Marcha

1. **Clonar el repositorio**:
   ```bash
   git clone https://github.com/Dontoti02/plataformaVirtualAcad.git
   cd plataformaVirtualAcad
   ```

2. **Configurar variables de entorno**:
   ```bash
   cp .env.example .env
   ```
   Completar los valores de base de datos, URLs y claves de seguridad (`APP_KEY`, `JWT_SECRET`).

3. **Iniciar servicios con Docker**:
   ```bash
   cd docker
   docker compose up -d
   ```

4. **Instalar dependencias del backend (opcional para desarrollo)**:
   ```bash
   cd backend
   composer install
   php artisan migrate
   ```

---

## 📄 Licencia

Sistema desarrollado para la gestión académica institucional.
