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

## 🚀 Guía de Instalación Rápida para Desarrolladores (Optimizado para WSL/Ubuntu)

Para maximizar el rendimiento de Docker en Windows, este proyecto se ha configurado para ejecutarse nativamente dentro de **WSL (Ubuntu)**. Sigue estos pasos para levantar el entorno:

1. **Instalar WSL (Linux) en Windows:**
   Abre una terminal de PowerShell como administrador y ejecuta:
   ```powershell
   wsl --install
   ```
   *(Si ya lo tienes, asegúrate de que esté actualizado con `wsl --update` y reinicia si es necesario).*

2. **Mover o copiar el proyecto a Ubuntu:**
   Para que Docker funcione a la máxima velocidad, el proyecto **no** debe estar en tu disco de Windows (como `C:\Users\...`). 
   - Abre tu terminal de Ubuntu y clona o copia tu proyecto dentro del sistema de archivos de Linux (por ejemplo, en `/home/tu_usuario/ProyectoInundaciones2` o `/root/...`).

3. **Instalar dependencias clave en Ubuntu:**
   Abre tu terminal de Ubuntu y asegúrate de instalar PHP, Composer y Node.js para poder gestionar los paquetes correctamente de forma local antes de levantar Sail:
   ```bash
   # 1. Actualizar sistema
   sudo apt update

   # 2. Instalar PHP y extensiones básicas
   sudo apt install -y php-cli php-curl php-xml php-mbstring unzip

   # 3. Instalar Composer
   curl -sS https://getcomposer.org/installer -o composer-setup.php
   sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
   rm composer-setup.php

   # 4. Instalar Node.js y NPM
   sudo apt install -y nodejs npm
   ```

4. **Configurar Docker y levantar el sistema (Laravel Sail):**
   Dentro de Ubuntu, navega a la carpeta principal de tu proyecto web (ej. `cd "cosa web/chirper"`) y ejecuta:
   ```bash
   # Instalar dependencias de backend
   composer install

   # Copiar archivo de entorno
   cp .env.example .env

   # Levantar los contenedores de Docker (Base de Datos, Redis, etc.) en segundo plano
   ./vendor/bin/sail up -d

   # Generar clave de aplicación y correr las migraciones (PostGIS)
   ./vendor/bin/sail artisan key:generate
   ./vendor/bin/sail artisan migrate --seed
   ```

5. **Correr el programa (Frontend):**
   Finalmente, instala los paquetes de Node y arranca el servidor de desarrollo:
   ```bash
   npm install
   npm run dev
   ```

6. Ingresa a `http://localhost:8001` (o el puerto configurado en tu `.env`) en tu navegador para ver la plataforma funcionando a máxima velocidad.

---

## 🎨 Arquitectura del Diseño (UI/UX)
Toda la interfaz del sistema ha sido construida siguiendo estándares **Premium**.
* Se hace uso intensivo del concepto de **Glassmorphism**: paneles translúcidos (`backdrop-blur`) que flotan sobre un fondo vibrante.
* Tipografía **Outfit** (Google Fonts) para un aspecto limpio y moderno.
* Animaciones orgánicas (`hover:-translate-y`, `transition-all`) para darle reactividad al ecosistema.

---
*Desarrollado para la protección y gestión del riesgo hídrico en la ciudad.*
