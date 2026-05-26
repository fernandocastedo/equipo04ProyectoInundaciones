<div align="center">
  <h1>🌊 Sistema de Gestión de Inundaciones (SGI) - Santa Cruz</h1>
  <p><strong>Plataforma Inteligente de Alerta Temprana, Topografía Dinámica y Mapeo Ciudadano</strong></p>
</div>

---

## 📖 Visión General del Proyecto

El **Sistema de Gestión de Inundaciones (SGI)** es una plataforma web desarrollada para monitorear, validar y visualizar eventos hidrológicos extremos en **Santa Cruz de la Sierra, Bolivia**. 

Este sistema aborda el problema combinando la **participación ciudadana (Crowdsourcing)** para predecir y dibujar cómo el agua se acumula en tiempo real.

---

## 🏗️ Arquitectura y Tecnologías

El proyecto sigue una arquitectura monolítica moderna impulsada por eventos, utilizando el ecosistema de Laravel.

* **Backend:** Laravel 11 (PHP 8.2+).
* **Frontend:** Blade Templates + Tailwind CSS (Glassmorphism & Diseño Premium) + JS Vainilla.
* **Base de Datos:** PostgreSQL.
* **Mapas y GIS:** Leaflet.js + Leaflet.heat (Capa de Calor).
* **Infraestructura Local:** Laravel Sail (Docker).

---

## 🧠 Lógica Core: El Mapa de Calor y la Topografía Inteligente

A diferencia de los mapas estáticos tradicionales, el SGI no muestra simples "marcadores" donde alguien reportó agua. Simula el comportamiento del agua basándose en el tiempo y el terreno. 

### 1. Sistema de Quórum Dinámico y TTL (Time-To-Live)
Las inundaciones son eventos dinámicos que aparecen y desaparecen. 
* **Reportes Ciudadanos:** Cuando un usuario envía un reporte, este se asocia a una inundación.
* **Tiempo de Vida (TTL):** Cada reporte tiene un tiempo de vida activo de **3 horas**, basado estrictamente en el campo **`updated_at`** de la base de datos.
* **Renovación (Autoridades):** Si el evento de lluvia continúa, una autoridad puede pulsar el botón **"Renovar"**. Esto hace un `touch()` al reporte (actualiza su `updated_at` al momento actual), otorgándole 3 horas adicionales de vida, manteniendo el área visualmente inundada en el mapa sin requerir reportes basura.
* Si el tiempo (`updated_at` + 3h) se agota, el reporte pasa a estar "Caducado" o inactivo, y sus puntos de calor se retiran automáticamente del mapa principal, reduciendo la intensidad de la inundación de forma realista a medida que el agua drena.

### 2. Motor Topográfico (`CalcularPoligonoInundacion` Job)
Cuando se aprueba un reporte, el sistema no asume que el agua se queda en un punto exacto (1 metro cuadrado). El agua fluye:
1. El backend lanza un **Job asíncrono/síncrono** que se conecta a un servicio de elevación externa.
2. Lee la latitud y longitud del reporte, y muestrea 8 puntos a su alrededor (como los radios de una bicicleta) a diferentes distancias (ej. 15, 35 o 60 metros dependiendo de la "Intensidad" reportada).
3. Evalúa el terreno: **El agua solo fluye hacia áreas que tengan una elevación igual o menor** al epicentro (con un margen de error de `0.5 metros` por bordes de calle/acera).
4. Genera un **Casco Convexo (Convex Hull)** con los puntos inundables y guarda este polígono en la base de datos (`polygon_coords`).
5. **Resultado:** En lugar de un círculo perfecto, el frontend dibuja la forma irregular real de cómo se empozó el agua en la calle.

### 3. Puentes Térmicos (Fusión de Mapas de Calor)
Si llueve fuerte en el 4to Anillo, es probable que haya múltiples reportes en la misma avenida.
* El mapa extrae todos los reportes activos vinculados a la misma inundación.
* Si la distancia entre dos reportes es entre **10 y 250 metros**, el algoritmo de JavaScript inyecta automáticamente puntos de interpolación térmica en el medio (uno cada 15 metros).
* El motor `leaflet.heat` renderiza estos puntos con una opacidad calculada, lo que resulta en la ilusión visual de un **río continuo** que conecta los reportes ciudadanos, reflejando el colapso de un canal o avenida entera.

### 4. Capa de Intervención de Autoridades
El sistema reconoce que el cálculo algorítmico puede no ser suficiente en desastres mayores. Una Autoridad puede usar herramientas de dibujo (`L.Draw`) para "manchar el mapa" manualmente. Si el backend detecta que la *Inundación* (no el reporte individual) tiene un polígono trazado por una autoridad, este tiene **absoluta prioridad**. Se oculta la simulación ciudadana y se dibuja un polígono de borde azul punteado indicando una "Zona de Desastre Oficial".

---

## 🚀 Guía de Instalación Rápida para Desarrolladores

El proyecto utiliza **Laravel Sail**, lo que significa que solo necesitas Docker instalado.

1. **Clonar el repositorio y entrar a la carpeta del proyecto.**
2. **Instalar dependencias de PHP usando un contenedor efímero:**
   ```bash
   docker run --rm \
       -u "$(id -u):$(id -g)" \
       -v "$(pwd):/var/www/html" \
       -w /var/www/html \
       laravelsail/php84-composer:latest \
       composer install --ignore-platform-reqs
   ```
3. **Copiar el archivo de entorno y levantar los contenedores:**
   ```bash
   cp .env.example .env
   ./vendor/bin/sail up -d
   ```
4. **Generar la clave de la app y correr migraciones (con PostGIS):**
   ```bash
   ./vendor/bin/sail artisan key:generate
   ./vendor/bin/sail artisan migrate --seed
   ```
5. **Compilar los assets del Frontend (Tailwind):**
   ```bash
   ./vendor/bin/sail npm install
   ./vendor/bin/sail npm run dev
   ```
6. Ingresa a `http://localhost:8001` (o el puerto configurado) en tu navegador.

---

## 🎨 Arquitectura del Diseño (UI/UX)
Toda la interfaz del sistema ha sido construida siguiendo estándares **Premium**.
* Se hace uso intensivo del concepto de **Glassmorphism**: paneles translúcidos (`backdrop-blur`) que flotan sobre un fondo vibrante.
* Tipografía **Outfit** (Google Fonts) para un aspecto limpio y moderno.
* Animaciones orgánicas (`hover:-translate-y`, `transition-all`) para darle reactividad al ecosistema.

---
*Desarrollado para la protección y gestión del riesgo hídrico en la ciudad.*
