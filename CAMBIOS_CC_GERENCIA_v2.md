# 📧 Cambios Implementados - CC a Gerencia en Correos de Reservas (v2.0)

**Fecha:** 2026-05-04  
**Versión:** 2.0  
**Descripción:** Correos de reservas/pagos incluyen CC a gerencia. Correos de recuperación de contraseña NO incluyen CC.

---

## 📝 Resumen de Cambios

Se modificó `GmailSender.php` para agregar un parámetro **opcional** `$addCC` (por defecto `true`) que permite controlar si un correo lleva CC a gerencia. Los correos de **recuperación de contraseña** usan `$addCC = false`.

---

## 📂 Archivos Modificados

### 1. `includes/GmailSender.php` (3 cambios)

**Cambio A: Método `sendEmail()` - Agregar parámetro**
```php
// Firma actualizada:
public function sendEmail($to, $subject, $message, $is_html = true, $image_path = null, $addCC = true)
```

**Cambio B: Método `sendWithPHPMailer()` - Parámetro y lógica**
```php
// Firma actualizada:
private function sendWithPHPMailer($to, $subject, $message, $is_html, $image_path = null, $addCC = true)

// Agregar CC condicionalmente (en lugar de siempre):
if ($addCC) {
    $mail->addCC('gerencia@mysuiteincartagena.com.co');
}
```

**Cambio C: Método `sendWithBasic()` - Parámetro y lógica**
```php
// Firma actualizada:
private function sendWithBasic($to, $subject, $message, $is_html, $addCC = true)

// Agregar CC condicionalmente en headers:
if ($addCC) {
    $headers[] = 'Cc: gerencia@mysuiteincartagena.com.co';
}
```

### 2. `app/services/send_password_reset.php` - Línea 104

```php
// ANTES:
$result = $gmailSender->sendEmail($to, $subject, $htmlContent);

// DESPUÉS:
$result = $gmailSender->sendEmail($to, $subject, $htmlContent, true, null, false);
                                                           ^^   ^^^   ^^
                                                           |    |     +-- FALSE = sin CC
                                                           |    +-- null = no image
                                                           +-- true = HTML format
```

### 3. `app/controllers/auth/forgot-password.php` - Línea 112

```php
// ANTES:
$result = $gmailSender->sendEmail($to, $subject, $htmlContent);

// DESPUÉS:
$result = $gmailSender->sendEmail($to, $subject, $htmlContent, true, null, false);
```

---

## 📧 Matriz de Correos

### ✅ CON CC a gerencia@mysuiteincartagena.com.co

| Tipo | Archivo | Línea | Motivo |
|------|---------|-------|--------|
| ✅ Reserva Aprobada | `GmailSender.php` | 345 | `$addCC` omitido → true (default) |
| ⚠️ Rechazo por conflicto | `GmailSender.php` | 364 | `$addCC` omitido → true (default) |
| ⚠️ Rechazo manual | `GmailSender.php` | 381 | `$addCC` omitido → true (default) |
| ❌ Cancelación | `GmailSender.php` | 398 | `$addCC` omitido → true (default) |
| 💰 Abono 20% | `marcar_pagada.php` | 251 | `$addCC` omitido → true (default) |
| ✅ Pago 100% | `marcar_pagada.php` | 376 | `$addCC` omitido → true (default) |

### ❌ SIN CC a gerencia

| Tipo | Archivo | Línea | Parámetro |
|------|---------|-------|-----------|
| 🔑 Reset Password | `send_password_reset.php` | 104 | `false` |
| 🔐 Verification Code | `forgot-password.php` | 112 | `false` |

---

## ✅ Validación Exitosa

```bash
$ php -l includes/GmailSender.php
No syntax errors detected ✅

$ php -l app/services/send_password_reset.php
No syntax errors detected ✅

$ php -l app/controllers/auth/forgot-password.php
No syntax errors detected ✅
```

---

## 🚀 Cómo Subir a cPanel

Reemplaza estos archivos en cPanel/FTP:

```
software_apto_php/includes/GmailSender.php
software_apto_php/app/services/send_password_reset.php
software_apto_php/app/controllers/auth/forgot-password.php
```

**No requiere cambios en BD** ❌

---

## 🔍 Cómo Funciona (Ejemplos)

### Caso 1: Correo de Reserva Aprobada (CON CC)

```php
// Dentro de GmailSender.php
public function sendReservaAprobada($reserva) {
    // ...
    return $this->sendEmail($reserva['correo'], $subject, $message, true, $hotel_image_path);
    // ↑ No pasamos $addCC, así que usa true (default)
}
```

**Resultado:** El correo llega a:
- ✅ $reserva['correo'] (cliente)
- ✅ gerencia@mysuiteincartagena.com.co (CC)

### Caso 2: Correo de Reset de Contraseña (SIN CC)

```php
// En send_password_reset.php
$gmailSender->sendEmail($to, $subject, $htmlContent, true, null, false);
//                                                                    ↑ FALSE
```

**Resultado:** El correo llega a:
- ✅ $to (usuario)
- ❌ gerencia (NO recibe copia)

---

## 📋 Parametrización de `sendEmail()`

```php
public function sendEmail(
    $to,              // email destinatario
    $subject,         // asunto
    $message,         // contenido HTML/texto
    $is_html = true,  // formato (default: HTML)
    $image_path = null,  // ruta de imagen incrustada (default: null)
    $addCC = true     // agregar CC a gerencia (default: true) ← NUEVO
)
```

---

## 📊 Resumen

| Métrica | Antes | Ahora |
|---------|-------|-------|
| Archivos modificados | - | 3 |
| Parámetro nuevo | - | `$addCC` (bool) |
| Correos con CC | 8 | 6 (sin reset/verify) |
| Correos sin CC | - | 2 (reset + verify) |
| BD afectada | - | No |
| Backward compatible | - | Sí (default true) |

---

## ✨ Características

- ✅ **Backward compatible**: código existente sigue funcionando sin cambios (usa true por defecto)
- ✅ **Flexible**: permite habilitar/deshabilitar CC por correo
- ✅ **Escalable**: fácil agregar más correos con opción de CC
- ✅ **Seguro**: parámetro boolean explícito, no magic strings

---

**Versión 2.0 - Completado** ✅
Fecha: 2026-05-04 11:30

