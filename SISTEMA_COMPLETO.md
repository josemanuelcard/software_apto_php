# 🏨 Sistema de Reservas - My Suite Cartagena

## 🚀 **Sistema Completamente Funcional**

### ✅ **Módulos Implementados:**

#### **1. 🗓️ Calendario de Reservas**
- **Ubicación:** `en/index.php`
- **Funcionalidades:**
  - Calendario interactivo con fechas ocupadas/disponibles
  - Selección de rango de fechas (check-in/check-out)
  - Validación de rangos con días ocupados
  - Precios dinámicos por fecha
  - Resumen de costos en tiempo real
  - Responsive para móviles

#### **2. 📋 Formulario de Reserva**
- **Ubicación:** `en/index.php` (modal)
- **Funcionalidades:**
  - Formulario completo de datos del cliente
  - Métodos de pago (tarjeta/efectivo)
  - Descuento automático del 3% para efectivo
  - Validación de campos
  - Instrucciones de comprobante de pago

#### **3. 📧 Sistema de Emails Reales**
- **Ubicación:** `includes/GmailSender.php`
- **Funcionalidades:**
  - Envío real de emails a Gmail
  - PHPMailer con STARTTLS
  - Templates HTML profesionales
  - Instrucciones claras de pago
  - Recordatorio de comprobante

#### **4. 👨‍💼 Panel de Administración**
- **Ubicación:** `admin/`
- **Funcionalidades:**
  - Dashboard con gráficas (Chart.js)
  - Gestión de reservas
  - Aprobación/rechazo de reservas
  - Sistema de comprobantes
  - Reportes y estadísticas

#### **5. 💾 Sistema de Comprobantes**
- **Ubicación:** `admin/upload_comprobante.php`
- **Funcionalidades:**
  - Subida con drag & drop
  - Preview de imágenes
  - Validación de archivos (JPG, PNG, PDF)
  - Protección de archivos
  - Visor seguro

## 🗂️ **Estructura de Archivos:**

```
software_apto_php/
├── en/
│   ├── index.php              # Calendario y formulario principal
│   └── process_reservation.php # Procesamiento de reservas
├── admin/
│   ├── index.php              # Dashboard principal
│   ├── login.php              # Login de administradores
│   ├── reservas.php           # Gestión de reservas
│   ├── upload_comprobante.php # Subida de comprobantes
│   └── view_comprobante.php  # Visor de comprobantes
├── config/
│   └── database.php           # Configuración de BD
├── includes/
│   ├── functions.php          # Funciones del sistema
│   └── GmailSender.php        # Envío de emails
├── uploads/
│   └── comprobantes/          # Archivos de comprobantes
└── vendor/                    # PHPMailer
```

## 🎯 **Flujo Completo del Sistema:**

### **1. Cliente hace reserva:**
1. Visita `en/index.php`
2. Selecciona fechas en el calendario
3. Llena formulario de reserva
4. Selecciona método de pago
5. Envía reserva → Estado: "pendiente"

### **2. Admin aprueba reserva:**
1. Ve a `admin/reservas.php`
2. Hace clic en "Aprobar"
3. **Email automático** se envía al cliente
4. Estado cambia a "aprobada"

### **3. Cliente recibe email:**
- **Resumen completo** de la reserva
- **Total a pagar** claramente indicado
- **Instrucciones** para enviar comprobante
- **Correo destino:** jose.cardenas01@uceva.edu.co

### **4. Cliente envía comprobante:**
- Toma foto del comprobante
- Envía a: jose.cardenas01@uceva.edu.co

### **5. Admin confirma pago:**
1. Ve a `admin/reservas.php`
2. Hace clic en botón "$" (comprobante)
3. Sube el comprobante en `upload_comprobante.php`
4. Sistema actualiza base de datos
5. Comprobante visible en detalle de reserva

## 🔧 **Configuración Técnica:**

### **Base de Datos:**
- **Host:** localhost:3307
- **Base de datos:** softwarePHP
- **Usuario:** root
- **Contraseña:** admin

### **Email:**
- **SMTP:** smtp.gmail.com:587
- **Seguridad:** STARTTLS
- **Usuario:** jose.cardenas01@uceva.edu.co
- **App Password:** Configurado

### **Archivos:**
- **Directorio:** uploads/comprobantes/
- **Protección:** .htaccess
- **Formatos:** JPG, PNG, PDF
- **Tamaño máximo:** 5MB

## 📊 **Características del Sistema:**

✅ **Calendario interactivo** con validación de fechas
✅ **Formulario responsive** con validaciones
✅ **Emails reales** a Gmail del cliente
✅ **Panel admin** con gráficas y reportes
✅ **Sistema de comprobantes** con drag & drop
✅ **Protección de archivos** y seguridad
✅ **Base de datos** optimizada con índices
✅ **Diseño moderno** y responsive

## 🚀 **Para Usar el Sistema:**

1. **Cliente:** `http://localhost:8000/en/index.php`
2. **Admin:** `http://localhost:8000/admin/login.php`
3. **Usuario admin:** admin@gmail.com
4. **Contraseña:** 123456

---

**¡Sistema completamente funcional y listo para producción!** 🎉✅
