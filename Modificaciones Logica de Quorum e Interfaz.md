# Especificación de Lógica de Quórum e Interfaz - Sistema de Inundaciones

Este documento detalla la implementación de la **Opción 2 (Cálculo Dinámico)** para la gestión de inundaciones, asegurando la integridad de los datos y una visualización reactiva en el módulo **Mapa**.

---

## 1. Estructura de Datos Refinada

Basado en el esquema de la base de datos y los requerimientos de persistencia:

### Tabla: `reportes`
Se mantiene como la fuente primaria de verdad para los cálculos.
*   `peso`: (Integer) Calculado al insertar (+1 reporte rápido, +3 con imagen).
*   `intensidad_propuesta`: (String) 'baja', 'media', 'alta'.
*   `estado_validacion`: (String) 'pendiente', 'aceptado', 'rechazado'.
*   `inundacion_id`: (FK) Relación con la inundación.
*   `created_at`: (Timestamp) Crucial para el filtro de Tiempo de Vida (TTL).

### Tabla: `inundaciones`
Se eliminan atributos calculados para evitar redundancia.
*   `estado`: (String) Valores permitidos: **'activa'**, **'terminada'**, **'falsa'**.
*   *Nota:* Se eliminan `puntos_quorum`, `intensidad_actual` y `expira_at` de la tabla física, ya que se calculan al vuelo.

---

## 2. Lógica del Backend (Cálculo Dinámico)

Para determinar qué mostrar en el módulo **Mapa**, el sistema ejecutará la siguiente lógica cada vez que se consulten las inundaciones:

### A. Filtro de Tiempo y Validez
Solo se consideran reportes que cumplan:
1.  `created_at` dentro de las últimas **3 horas** (TTL configurable).
2.  `estado_validacion` sea diferente de **'rechazado'**.

### B. Cálculo de Quórum
*   **Quórum Total:** Sumatoria de los `peso` de todos los reportes filtrados asociados a una `inundacion_id`.
*   **Umbral de Confirmación:** Se requieren **5 puntos** para que una inundación en estado 'activa' se considere "Confirmada" visualmente.

### C. Determinación de Intensidad (Votación Ponderada)
Si el quórum total es $\ge 5$, la intensidad se decide por la suma de pesos de cada categoría:
*   `Puntos_Baja` = $\sum peso$ donde `intensidad_propuesta` = 'baja'.
*   `Puntos_Media` = $\sum peso$ donde `intensidad_propuesta` = 'media'.
*   `Puntos_Alta` = $\sum peso$ donde `intensidad_propuesta` = 'alta'.

**Resultado:** La intensidad con el mayor puntaje acumulado define el color en el mapa. En caso de empate, el sistema prioriza la intensidad más alta.

---

## 3. Módulo: Mapa (Visualización y Detalle)

Este módulo consume los cálculos dinámicos y gestiona la visualización según el `estado` de la base de datos.

### A. Capa de Visualización (Iconografía)
El mapa renderiza los eventos según su estado y el quórum calculado:

| Estado (DB) | Quórum Calc. | Representación en Mapa |
| :--- | :--- | :--- |
| **activa** | < 5 pts | Icono Gris/Translúcido (Estado: En Validación). |
| **activa** | $\ge$ 5 pts | Icono de Color (Amarillo/Naranja/Rojo según intensidad ganadora). |
| **terminada** | N/A | No se muestra en el mapa activo (pasa a Historial). |
| **falsa** | N/A | No se muestra en el mapa (marcada como error de reporte). |

### B. Vista Detallada de Inundación (Panel Lateral)
Al seleccionar una inundación en el mapa, el panel mostrará:

1.  **Información de Estado:**
    *   Si es dinámica: "Confirmada por comunidad (+X puntos)".
    *   Si es manual: "Validada por autoridad".
2.  **Métrica de Quórum:** Gráfico simple o lista que desglosa los puntos actuales (Ej: "Media: 6 pts / Baja: 1 pt").
3.  **Lista de Reportes:**
    *   Scroll de los reportes vinculados que aún están dentro del TTL.
    *   Previsualización de imágenes (aquellas que aportaron +3 al quórum).
    *   Timestamp relativo (hace 10 min, hace 1 hora).
4.  **Datos Meteorológicos:** Renderización del `datos_clima_json` del último reporte recibido para contrastar con la realidad visual.

---

## 4. Casos de Uso Especiales

*   **Persistencia de Intensidad:** Si una inundación sube de "Baja" a "Media" debido a 2 nuevos reportes con imagen (+6 pts), el cambio es instantáneo en la siguiente petición al Mapa.
*   **Detección de "Falsas":** Si la autoridad marca una inundación como `estado = 'falsa'`, esta desaparece del módulo Mapa para todos los ciudadanos, independientemente de cuántos reportes sigan llegando.
*   **Continuidad por Clima:** Si la API de Open-Meteo indica lluvia intensa persistente, la autoridad puede decidir mantener el estado `activa` manualmente aunque los reportes ciudadanos bajen por falta de quórum (opcional según gestión de autoridad).