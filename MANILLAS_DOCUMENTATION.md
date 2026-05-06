# Gestión de Tarifas de Manillas - Documentación

## Descripción General

La nueva funcionalidad de **Manillas** permite parametrizar el precio de las manillas por cantidad de personas. Esto facilita el cálculo automatizado de tarifas basadas en grupos de diferentes tamaños.

## Características

✅ Crear múltiples rangos de tarifas  
✅ Definir precio para grupos de "N a M" personas  
✅ Soporte para "en adelante" (sin límite máximo)  
✅ Activar/Desactivar tarifas sin eliminarlas  
✅ Interfaz amigable y responsiva  
✅ Almacenamiento permanente en base de datos  

## Acceso

**URL:** `/admin/manillas.php`

**Panel Alternativo:** `app/views/admin/index.php` - En el sidebar, opción "Manillas"

## Cómo Usar

### 1. Agregar una Nueva Tarifa

1. Haz clic en el botón **"+ Nueva Tarifa"**
2. Completa los campos:
   - **Personas Desde:** Cantidad mínima (obligatorio)
   - **Personas Hasta:** Cantidad máxima (opcional - dejar vacío para "en adelante")
   - **Precio:** Precio en COP (obligatorio)
   - **Estado:** Activo/Inactivo

3. Haz clic en **"Guardar Tarifa"**

### 2. Ejemplos de Configuración

#### Ejemplo 1: Rango Específico
```
Personas Desde: 1
Personas Hasta: 7
Precio: $70.000
```
**Resultado:** Un grupo de 1 a 7 personas pagará $70.000

#### Ejemplo 2: "En Adelante"
```
Personas Desde: 8
Personas Hasta: (dejar vacío)
Precio: $90.000
```
**Resultado:** Un grupo de 8 personas o más pagará $90.000

#### Ejemplo 3: Configuración Completa
```
Tarifa 1: 1-3 personas = $50.000
Tarifa 2: 4-6 personas = $60.000
Tarifa 3: 7-10 personas = $80.000
Tarifa 4: 11+ personas = $100.000
```

### 3. Editar una Tarifa

1. En la tabla de tarifas registradas, haz clic en el botón ✏️ (editar)
2. Modifica los valores necesarios
3. Haz clic en **"Guardar Tarifa"**

### 4. Eliminar una Tarifa

1. En la tabla de tarifas registradas, haz clic en el botón 🗑️ (eliminar)
2. Confirma la eliminación

### 5. Desactivar una Tarifa

1. Edita la tarifa
2. Cambia el estado a "Inactivo"
3. Guarda los cambios

Una tarifa inactiva seguirá en la base de datos pero no se aplicará automáticamente en nuevas reservas.

## Estructura de Base de Datos

### Tabla: `manillas_tarifas`

```sql
CREATE TABLE manillas_tarifas (
  id_tarifa INT AUTO_INCREMENT PRIMARY KEY,
  personas_desde INT NOT NULL,           -- Cantidad mínima
  personas_hasta INT,                    -- Cantidad máxima (NULL = sin límite)
  precio DECIMAL(10,2) NOT NULL,         -- Precio en COP
  activo BOOLEAN DEFAULT TRUE,            -- Estado de la tarifa
  creado_en TIMESTAMP,                   -- Fecha de creación
  actualizado_en TIMESTAMP,              -- Última actualización
  UNIQUE KEY unique_rango (personas_desde, personas_hasta)
);
```

## APIs

### Obtener todas las tarifas
**Endpoint:** `GET /app/api/admin/get_manillas_tarifas.php`

**Respuesta Exitosa:**
```json
{
  "success": true,
  "tarifas": [
    {
      "id_tarifa": 1,
      "personas_desde": 1,
      "personas_hasta": 7,
      "precio": "70000.00",
      "activo": 1,
      "creado_en": "2025-05-04 10:30:00",
      "actualizado_en": "2025-05-04 10:30:00"
    },
    {
      "id_tarifa": 2,
      "personas_desde": 8,
      "personas_hasta": null,
      "precio": "90000.00",
      "activo": 1,
      "creado_en": "2025-05-04 10:30:00",
      "actualizado_en": "2025-05-04 10:30:00"
    }
  ]
}
```

### Guardar/Actualizar tarifa
**Endpoint:** `POST /app/api/admin/save_manillas_tarifa.php`

**Parámetros (JSON):**
```json
{
  "id_tarifa": 0,           // 0 para crear nueva, > 0 para actualizar
  "personas_desde": 1,      // Requerido
  "personas_hasta": 7,      // Opcional (null para "en adelante")
  "precio": 70000,          // Requerido
  "activo": 1               // Opcional (por defecto 1)
}
```

**Respuesta Exitosa:**
```json
{
  "success": true,
  "message": "Tarifa creada exitosamente",
  "id_tarifa": 1
}
```

### Eliminar tarifa
**Endpoint:** `POST /app/api/admin/delete_manillas_tarifa.php`

**Parámetros (JSON):**
```json
{
  "id_tarifa": 1
}
```

**Respuesta Exitosa:**
```json
{
  "success": true,
  "message": "Tarifa eliminada exitosamente"
}
```

## Validaciones

- **Personas Desde:** Debe ser mayor a 0
- **Personas Hasta:** Debe ser mayor o igual a "Personas Desde" (si se proporciona)
- **Precio:** No puede ser negativo
- **Rangos Únicos:** No se pueden crear dos tarifas con el mismo rango de personas desde y hasta

## Notas Importantes

⚠️ **Permisos:** Solo usuarios administradores pueden acceder a esta sección

⚠️ **Datos Iniciales:** La tabla se crea automáticamente con dos tarifas de ejemplo:
- 1-7 personas: $70.000
- 8+ personas: $90.000

⚠️ **Eliminación Permanente:** Al eliminar una tarifa, se elimina permanentemente de la base de datos

⚠️ **Actualización de Fechas:** La fecha de "Actualizado en" cambia automáticamente cada vez que se modifica la tarifa

## Resolución de Problemas

### Error: "Tabla no existe"
Ejecuta el script de setup:
```bash
php setup_manillas.php
```

### Error: "No autorizado"
Asegúrate de estar logueado como administrador

### Las tarifas no se guardan
Revisa los logs de error en `logs/email_errors.log` o consulta con soporte

## Futuras Mejoras

- [ ] Estadísticas de uso de tarifas
- [ ] Importar/Exportar tarifas en CSV
- [ ] Historial de cambios de tarifas
- [ ] Buscar y filtrar tarifas
- [ ] Duplicar tarifas existentes

## Soporte

Para reportar problemas o sugerencias sobre la funcionalidad de Manillas, contacta al equipo de desarrollo.

