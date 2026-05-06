# 📧 ACTUALIZACIÓN - CARTAS DE PAGO Y SERVICIOS ADICIONALES

**Fecha:** 2026-05-04  
**Versión:** 3.0  
**Descripción:** Integración de nuevas plantillas de correos con cartas modelo profesionales + Panel de control para servicios adicionales (Early check-in y Late checkout)

---

## 📋 RESUMEN DE CAMBIOS

Se han realizado los siguientes cambios:

### 1. **Nueva Tabla en BD: `servicios_adicionales`**
Se agregó tabla para gestionar precios de:
- Early Check-in
- Late Checkout

**Ejecutar en BD:**
```sql
CREATE TABLE servicios_adicionales (
  id_servicio INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  tipo ENUM('early_checkin','late_checkout') NOT NULL,
  precio DECIMAL(10,2) NOT NULL,
  descripcion TEXT,
  activo BOOLEAN DEFAULT TRUE,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertar valores iniciales de ejemplo:
INSERT INTO servicios_adicionales (nombre, tipo, precio, descripcion, activo)
VALUES 
('Early Check-in', 'early_checkin', 50000, 'Ingreso antes de las 3 PM', 1),
('Late Checkout', 'late_checkout', 50000, 'Salida después de las 11 AM', 1);
```

### 2. **Panel Admin: Servicios Adicionales**

**Archivo nuevo:**
- `admin/servicios_adicionales.php`

Interfaz completa para CRUD de servicios adicionales similar a:
- ✅ Tarifas
- ✅ Descuentos
- ✅ Manillas

**Características:**
- Crear, editar, eliminar servicios
- Mostrar lista con estado (Activo/Inactivo)
- Filtrado por tipo (Early Check-in / Late Checkout)

### 3. **Endpoints API para Servicios Adicionales**

**Archivos nuevos:**
- `app/api/admin/get_servicios_adicionales.php` - Obtener lista de servicios
- `app/api/admin/save_servicio_adicional.php` - Guardar/Actualizar servicio
- `app/api/admin/delete_servicio_adicional.php` - Eliminar servicio

### 4. **Nuevas Plantillas de Correo en GmailSender.php**

Se agregaron **4 métodos públicos** y **4 plantillas privadas** para enviar cartas profesionales:

#### **Métodos Públicos Nuevos:**

| Método | Disparador | Contenido |
|--------|-----------|-----------|
| `sendReservaAprobadaTransferencia20()` | Reserva aprobada, pago por transferencia 20% | Carta oficial Andrés Diaz |
| `sendReservaAprobadaTarjeta20()` | Reserva aprobada, pago por tarjeta 20% | Carta oficial Andrés Diaz |
| `sendSaldoTransferencia80()` | Pago saldo pendiente, transferencia 80% | Carta oficial Andrés Diaz |
| `sendSaldoTarjeta80()` | Pago saldo pendiente, tarjeta 80% | Carta oficial Andrés Diaz |

#### **Métodos Privados de Plantillas:**
- `getEmailTemplateTransferencia20()` - Carta 20% Transferencia
- `getEmailTemplateTarjeta20()` - Carta 20% Tarjeta de Crédito
- `getEmailTemplateTransferencia80()` - Carta 80% Transferencia
- `getEmailTemplateTarjeta80()` - Carta 80% Tarjeta de Crédito

#### **Métodos Auxiliares Privados (nuevos):**
- `getPrecioServicioAdicional($tipo)` - Obtiene precio de early check-in o late checkout desde BD
- `getPrecioManillas($cantidad_personas)` - Obtiene precio de manillas según cantidad de personas desde BD

### 5. **Contenido de las Cartas**

Cada carta incluye:

✅ **Logo del hotel** (arriba a la derecha)  
✅ **Nombre del huésped** (personalizado)  
✅ **Normas a confirmar:**
   - No se aceptan visitas no registradas
   - Check-in 3 PM / Check-out 11 AM  
   - Horarios de vuelos (información a pedir)  

✅ **Servicios adicionales dinámicos:**
   - Precio Early Check-in (desde BD)
   - Precio Late Checkout (desde BD)  

✅ **Monto a pagar:**
   - 20% anticipado
   - 80% saldo pendiente

✅ **Método de pago:**
   - Transferencia: Llave @millave3137910897
   - Tarjeta: "Link de BoldPayment adjunto"

✅ **Manillas dinámicas:**
   - Precio según cantidad de personas (desde BD)

✅ **Firma:**
   - Andrés Diaz, Soporte My Suite In Cartagena

---

## 📂 ARCHIVOS A SUBIR A cPANEL

### **Nuevos:**
1. `admin/servicios_adicionales.php`
2. `app/api/admin/get_servicios_adicionales.php`
3. `app/api/admin/save_servicio_adicional.php`
4. `app/api/admin/delete_servicio_adicional.php`

### **Modificados:**
1. `config/database.php` - Ejecutar SQL que aparece arriba (tabla `servicios_adicionales`)
2. `includes/GmailSender.php` - 4 métodos nuevos + 4 plantillas nuevas

---

## 🔄 CÓMO USAR EN PRODUCCIÓN

### **Paso 1: Ejecutar SQL en BD**
```sql
CREATE TABLE servicios_adicionales (...)  -- Ver arriba en "Nueva Tabla"
```

### **Paso 2: Subir archivos a cPanel**
```
Nuevos:
  /admin/servicios_adicionales.php
  /app/api/admin/get_servicios_adicionales.php
  /app/api/admin/save_servicio_adicional.php
  /app/api/admin/delete_servicio_adicional.php

Modificados:
  /includes/GmailSender.php
```

### **Paso 3: Configurar Servicios Adicionales en Admin**
1. Login como admin
2. Ir a "Servicios Adicionales" (nuevo link en sidebar)
3. Crear/Editar precios de Early Check-in y Late Checkout
4. Guardar

### **Paso 4: Integrar en Flujo de Reservas**
Actualizar código en:
- `marcar_pagada.php` - Llamar a nuevos métodos según método_pago y porcentaje
- Ejemplo:
```php
if ($metodo_pago === 'transferencia' && $porcentaje === 20) {
    $gmail->sendReservaAprobadaTransferencia20($reserva);
} elseif ($metodo_pago === 'tarjeta' && $porcentaje === 20) {
    $gmail->sendReservaAprobadaTarjeta20($reserva);
}
// ... etc
```

---

## ✅ VALIDACIÓN

```bash
✅ admin/servicios_adicionales.php - No syntax errors
✅ get_servicios_adicionales.php - No syntax errors
✅ save_servicio_adicional.php - No syntax errors
✅ delete_servicio_adicional.php - No syntax errors
✅ includes/GmailSender.php - No syntax errors
```

---

## 📊 DIAGRAMA DE FLUJO

```
Reserva Aprobada
    ↓
¿Método de pago?
    ├─ Transferencia
    │   ├─ 20% → sendReservaAprobadaTransferencia20()
    │   └─ 80% → sendSaldoTransferencia80()
    └─ Tarjeta
        ├─ 20% → sendReservaAprobadaTarjeta20()
        └─ 80% → sendSaldoTarjeta80()
    ↓
Obtener datos dinámicos:
    ├─ Precio Early Check-in (BD)
    ├─ Precio Late Checkout (BD)
    └─ Precio Manillas (BD por cantidad)
    ↓
Render carta con datos
    ↓
Enviar email con CC a gerencia
```

---

## 🎯 PRÓXIMOS PASOS

1. **Ejecutar SQL en BD**
2. **Subir 5 archivos a cPanel**
3. **Ir a admin > Servicios Adicionales > Configurar precios**
4. **Integrar llamadas en `marcar_pagada.php` u otros endpoints**
5. **Probar flujo de reservas**

---

## 📞 SOPORTE

Si encuentras errores:
- ✅ Revisar `logs/email_errors.log`
- ✅ Validar que tabla `servicios_adicionales` está creada
- ✅ Confirmar que precios están configurados en admin
- ✅ Verificar que BD tiene tabla `tarifas_manillas` (si existe)

---

**Versión 3.0 - Completado** ✅  
Fecha: 2026-05-04

