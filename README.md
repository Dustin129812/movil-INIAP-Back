# 📂 Backend - SIMPAGI

<p align="center">
  <a href="https://iniap.gob.ec/" target="_blank">
    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/e7/Logo_iniapp.png/800px-Logo_iniapp.png" width="150" alt="INIAP Logo">
  </a>
</p>

<p align="center">
  <code><img src="https://img.shields.io/badge/Laravel-10-red" alt="Laravel Version"></code>
  <code><img src="https://img.shields.io/badge/PHP-8.3-blue" alt="PHP Version"></code>
  <code><img src="https://img.shields.io/badge/PostgreSQL-Supported-green" alt="PostgreSQL"></code>
  <code><img src="https://img.shields.io/badge/license-MIT-green.svg" alt="License"></code>
</p>

## 💡 Sobre SIMPAGI Backend

SIMPAGI (Sistema Simplificado de Gestión de la Información) es una plataforma web robusta y eficiente, diseñada para optimizar la transferencia de información en tiempo real entre los investigadores de las diversas estaciones experimentales del **INIAP (Instituto Nacional de Investigaciones Agropecuarias)**, entidad adscrita al **MAG (Ministerio de Agricultura y Ganadería del Ecuador)**.

Esta potente aplicación backend se encarga de la gestión integral de:

* **🗓️ Planificación del Plan Operativo Anual (POA)**
* **📅 Planificación semanal de actividades**
* **📊 Gestión de estadísticas detalladas para análisis exhaustivos y la generación de reportes precisos.**

## 🛠️ Requisitos del Sistema

Para un funcionamiento óptimo, asegúrate de tener instalado lo siguiente:

* **PHP:** Versión 8.3 o superior
* **Composer:** Administrador de dependencias de PHP
* **PostgreSQL:** Sistema de gestión de bases de datos relacional
* **IDE Recomendado:** PHPStorm (opcional, pero altamente recomendado para desarrollo en PHP)
* **Herramienta de Pruebas:** Postman o similar (para la verificación de la API)

## ⚙️ Instalación Paso a Paso

Sigue estas instrucciones para configurar el backend de SIMPAGI en tu entorno local:

1.  **Clonar el Repositorio:**
    ```bash
    git clone [https://github.com/tu-usuario/backend-simpagi.git](https://github.com/tu-usuario/backend-simpagi.git)
    ```

2.  **Navegar al Directorio del Proyecto:**
    ```bash
    cd backend-simpagi
    ```

3.  **Instalar las Dependencias:**
    ```bash
    composer install
    ```

4.  **Configurar el Archivo de Entorno:**
    ```bash
    cp .env.example .env
    ```
    Edita el archivo `.env` con tus credenciales de conexión a la base de datos PostgreSQL.

5.  **Generar la Clave de la Aplicación:**
    ```bash
    php artisan key:generate
    ```

6.  **Ejecutar las Migraciones y Seeders:**
    ```bash
    php artisan migrate --seed
    ```

7.  **Iniciar el Servidor de Desarrollo:**
    ```bash
    php artisan serve
    ```

    Podrás acceder a la aplicación en tu navegador a través de la dirección mostrada (generalmente `http://127.0.0.1:8000`).

## 🚀 Tecnologías Empleadas

Este proyecto se ha desarrollado utilizando las siguientes tecnologías clave:

* **Backend Framework:** [Laravel](https://laravel.com/) 10
* **Lenguaje de Programación:** [PHP](https://www.php.net/) 8.3
* **Base de Datos:** [PostgreSQL](https://www.postgresql.org/)

## 📄 Licencia

Este proyecto es de **uso interno exclusivo** para el Instituto Nacional de Investigaciones Agropecuarias (INIAP).

---
