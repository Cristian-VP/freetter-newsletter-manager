# Freetter: The Open Source, Sustainable Newsletter Platform

<!-- Badges (Escudos) -->
[![Build Status](https://github.com/freetter/freetter/actions/workflows/ci.yml/badge.svg)](https://github.com/freetter/freetter/actions)
[![License: AGPL v3](https://img.shields.io/badge/License-AGPL%20v3-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)
[![Laravel v12](https://img.shields.io/badge/Laravel-11-FF2D20.svg?logo=laravel)](https://laravel.com/)
[![Carbon Neutral](https://img.shields.io/badge/Carbon-Tracking%20Integrated-4CAF50.svg?style=flat)](docs/sustainability.md)

## Sobre Freetter

**Freetter** es una plataforma de newsletters 100% código abierto, orientada a la **sostenibilidad, la colaboración y la transparencia radical**. Nace como una alternativa ética y propositiva a los servicios dominantes (como Substack o Mailchimp) que imponen comisiones elevadas y gobernanza opaca.

Nuestro objetivo es ofrecer a creadores, periodistas independientes y organizaciones comunitarias una herramienta profesional gratuita, sin comisiones sobre donaciones, e integrando la huella ambiental desde el diseño. El resultado es un Producto Mínimo Viable (MVP) funcional que demuestra competencias avanzadas en arquitectura web, DevOps y seguridad.

## Características Destacadas del MVP

*   **Carbon Tracking Integrado**: Freetter integra un módulo de cálculo estimado de huella de carbono por cada envío de newsletter, basado en una fórmula simplificada para sensibilizar al creador.
*   **Modelo Sin Comisiones**: La plataforma no aplica comisiones sobre las donaciones que los creadores puedan recibir directamente a través de enlaces a proveedores externos (ej. Stripe o PayPal).
*   **Autenticación sin Contraseñas (Magic Links)**: Acceso simple y seguro mediante un enlace de un solo uso enviado por email, reduciendo la gestión de contraseñas y la superficie de ataque.
*   **Editor de Contenido por Bloques**: Utilización de Editor.js para crear contenido estructurado y reutilizable mediante el sistema de clips. El editor del MVP está limitado a un conjunto acotado de bloques esenciales para mitigar la complejidad.
*   **Cola de Envíos Asíncrona**: Gestión robusta de envíos masivos e importación de CSVs mediante colas de trabajo, evitando el bloqueo de la interfaz durante operaciones costosas.
*   **Colaboración Básica**: Implementación de un sistema simple de roles (Owner/Writer) para permitir la gestión colaborativa dentro de cada espacio de trabajo (workspace).

## Stack Tecnológico

**Freetter** se basa en una arquitectura de monolito modular eficiente, elegida para maximizar el rendimiento en servidores de recursos limitados.

| Componente               | Tecnología     | Versión Clave | Propósito Principal                                                               |
|:-------------------------|:---------------|:--------------|:----------------------------------------------------------------------------------|
| **Backend Core**         | Laravel        | **12**        | Ecosistema maduro, ORM potente y sistema de colas nativo (Queue).                 |
| **Frontend Interactivo** | React          | **19**        | Desarrollo de componentes de interfaz modernos.                                   |
| **Puente SPA**           | Inertia.js     | **2.0**       | Permite una Single Page Application (SPA) sin necesidad de una API REST separada. |
| **Base de Datos**        | PostgreSQL     | **17**        | Robustez, soporte de transacciones y tipo JSONB para contenido estructurado.      |
| **Caché & Colas**        | Redis          | **7**         | Backend de colas de trabajo y caché de sesiones.                                  |
| **Runtime JavaScript**   | Node.js        | **22 LTS**    | Entorno de ejecución para React y Vite.                                           |
| **Emails**               | MJML / Mailgun | -             | MJML para renderizado; Mailgun para entregabilidad y rate limiting.               |

## Arquitectura del Sistema

El proyecto se desarrolla como un **Monolito Modular**, una elección sólida para proyectos con recursos limitados.

*   **Monolito Modular por Dominios**: organiza la lógica por dominios funcionales (Newsletter, Subscriber, Clip, etc.) en lugar de por capas técnicas para mejorar cohesión y mantenibilidad.
*   **Server-Side First con Inertia**: Laravel gestiona routing y estado principal; Inertia inyecta datos a componentes React, simplificando seguridad y lógica.
*   **Mitigación de Picos de Envío**: las colas de Redis se configuran con throttling (por ejemplo, 60 envíos/minuto) para evitar el rate limiting del proveedor de email. El procesamiento masivo de suscriptores se realiza mediante `cursor()` y chunks para reducir el uso de memoria.

---

## Guía de Instalación Rápida (Getting Started)

### Requisitos mínimos

* PHP 8.4+
* Composer
* Node.js 22 LTS y npm
* PostgreSQL 17+
* Redis 7+ 

### Pasos de instalación (rápidos)

1. Clonar el repositorio:

```bash
git clone https://github.com/freetter/freetter.git
cd freetter
```

1. Instalar dependencias PHP:

```bash
composer install
```

1. Copiar el fichero de entorno y configurar credenciales:

```bash
cp .env.example .env
# editar .env con los datos de PostgreSQL, Redis, Mailgun, etc.
```

1. Instalar dependencias JS e iniciar el frontend (Vite):

```bash
npm install
npm run dev
```

1. Ejecutar migraciones y seeders:

```bash
php artisan migrate --seed
```

1. Asegurar el procesamiento de colas (CRÍTICO): ejecutar el worker de colas en producción mediante un supervisor (ej. SupervisorD).

---

## Roadmap (Evolución Post-MVP)

### V1.1 - Robustez y Usabilidad
- Programación de envíos de newsletters para fechas futuras.
- Estadísticas básicas (tasas de apertura y clicks).
- Exportación de listas de suscriptores en formato CSV.
- Templates adicionales de email basados en MJML.

### V1.2 - Maduración de la Plataforma
- Integración de Webhooks de Mailgun para procesar bounces y reclamaciones.
- Segmentación de suscriptores mediante criterios o etiquetas.
- Historial de versiones de la newsletter.
- API pública limitada para integraciones sencillas.

---

## Contribución

Freetter es un proyecto Open Source. Si quieres colaborar:

- Abre un issue para discutir cambios o bugs.
- Abre un Pull Request con tests y descripción clara de los cambios.
- Consulta la guía de contribución en `CONTRIBUTING.md` (si no existe, crea una propuesta en un issue).

Gracias por contribuir: todo aporte es bienvenido.

---

## Licencia

Este proyecto está licenciado bajo la GNU Affero General Public License v3.0 (AGPLv3):

- Ver licencia: https://www.gnu.org/licenses/agpl-3.0

---

## Créditos

**Autor:** Cristian Andrés Vacacela Procel  
**PRA / Mentor:** Miquel Antoni Capellà Arrom

El proyecto demuestra competencias en arquitectura web, DevOps, seguridad y optimización de rendimiento, con foco en responsabilidad social y ambiental
