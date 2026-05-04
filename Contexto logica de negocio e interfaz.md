# 📑 ESPECIFICACIÓN TÉCNICA: MÓDULO DE REPORTES RÁPIDOS E INUNDACIONES
**Ubicación del Proyecto:** Santa Cruz, Bolivia.


---

## 🗄️ 1. MODELO DE DATOS (ESQUEMA SQL)

### Tabla: inundaciones
*Representa el evento consolidado y activo en el mapa.*
- `id`: SERIAL PRIMARY KEY.
- `municipio_id`: INTEGER (Relación con tabla de municipios existente).[cite: 1]
- `latitud`, `longitud`: DECIMAL(10, 8) / (11, 8).[cite: 1]
- `intensidad_actual`: VARCHAR (baja, media, alta).[cite: 1]
- `puntos_quorum`: INTEGER DEFAULT 0.[cite: 1]
- `estado`: VARCHAR (activa, finalizada).[cite: 1]
- `expira_at`: TIMESTAMP (Cierre dinámico).[cite: 1]
- `created_at`, `updated_at`: TIMESTAMP.[cite: 1]

### Tabla: reportes
*Propuestas enviadas por ciudadanos pendientes de validación.*
- `id`: SERIAL PRIMARY KEY.
- `user_uuid`: UUID NOT NULL (Persistencia vía LocalStorage).[cite: 1]
- `inundacion_id`: INTEGER NULL (Asignado tras aprobación).[cite: 1]
- `lat_gps`, `long_gps`: DECIMAL (Coordenadas reales del sensor).[cite: 1]
- `lat_reporte`, `long_reporte`: DECIMAL (Punto ajustado por usuario).[cite: 1]
- `intensidad_propuesta`: VARCHAR (baja, media, alta).[cite: 1]
- `foto_path`: VARCHAR (Ruta de evidencia fotográfica).[cite: 1]
- `estado_validacion`: VARCHAR (pendiente, aprobada, rechazada).[cite: 1]
- `datos_clima_json`: JSONB (Respuesta de Open-Meteo al momento del reporte).[cite: 1]
- `created_at`: TIMESTAMP.[cite: 1]


##  Optimización de API de Clima
### Tabla clima_cache 
-     id SERIAL PRIMARY KEY,
-     municipio_id INTEGER REFERENCES municipios(id),
-     precipitacion_mm FLOAT,
-     last_check TIMESTAMP DEFAULT CURRENT_TIMESTAMP
- 


## 🧠 2. LÓGICAS DE NEGOCIO

### 2.1 Identificación y Persistencia (UUID)
- Al ingresar al módulo, generar un UUID en `localStorage` si no existe.[cite: 1]
- **Restricción**: Un usuario solo puede tener un reporte activo/pendiente. Si reingresa, el sistema debe cargar su reporte previo para permitir su actualización (intensidad, ubicación o foto).[cite: 1]
- **Reinicio**: Al marcarse una inundación como "finalizada", liberar los UUID asociados para permitir nuevos reportes.[cite: 1]

### 2.2 Ubicación Híbrida (Regla de los 500m)
- Capturar GPS real, pero permitir al usuario mover el marcador en un radio máximo de **500 metros** en el mapa.[cite: 1]
- **Validación Backend**: Comprobar la distancia entre `lat_gps` y `lat_reporte` usando la fórmula de Haversine:[cite: 1]
  $$d = 2r \arcsin\left(\sqrt{\sin^2\left(\frac{\Delta\phi}{2}\right) + \cos(\phi_1)\cos(\phi_2)\sin^2\left(\frac{\Delta\lambda}{2}\right)}\right)$$

### 2.3 Validación y Fusión (Merge)
- **Sugerencia Automática**: Si un reporte está a $\le 300\text{ m}$ de una inundación activa, sugerir "Vincular". Si es $> 300\text{ m}$, sugerir "Crear Nueva".[cite: 1]
- **Pesos de Quórum**: Baja = 1 pto, Media = 3 ptos, Alta = 5 ptos.[cite: 1]
- **Escalamiento de Intensidad**:
  - $\ge 6\text{ puntos}$: Intensidad de inundación sube a **Media**.[cite: 1]
  - $\ge 15\text{ puntos}$: Intensidad de inundación sube a **Alta**.[cite: 1]

---

## 🕒 3. CICLO DE VIDA Y SCHEDULER (FACTOR DE PERSISTENCIA)

### 3.1 Tiempos Base
Al crear/actualizar la intensidad de una inundación, definir `expira_at`:[cite: 1]
- **Baja**: +5 horas.[cite: 1]
- **Media**: +18 horas.[cite: 1]
- **Alta**: +7 días.[cite: 1]

### 3.2 Extensión por Lluvia (Open-Meteo)
El Scheduler (cada 15-30 min) debe consultar la precipitación ($mm/h$) para extender el tiempo si la inundación está por expirar:[cite: 1]
- **< 2 mm/h**: +30 min.[cite: 1]
- **2 - 10 mm/h**: +1 hora.[cite: 1]
- **10 - 30 mm/h**: +3 horas.[cite: 1]
- **> 30 mm/h**: +6 horas.[cite: 1]

### 3.3 Reglas de Finalización (Vaciado)
Solo finalizar si:
1. `now() > expira_at`.[cite: 1]
2. Lluvia detectada = $0\text{ mm/h}$.[cite: 1]
3. No hay nuevos reportes aprobados en la última hora.[cite: 1]
*Nota: Si deja de llover, respetar el tiempo base remanente para simular el drenaje de la ciudad.*[cite: 1]

---

## 🎨 4. INTERFAZ Y UX
- **Ciudadano**: Formulario de "Un toque", mapa con radio restrictivo de 500m y carga de fotografía.[cite: 1]
- **Autoridad**: Panel de validación con mapa que compare el punto amarillo (reporte) vs puntos/zonas rojas (inundaciones activas) para facilitar el Merge.[cite: 1]