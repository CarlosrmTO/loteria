# Documentación Técnica - Plugin Lotería Navidad 2025

**Fecha:** 4 de Diciembre, 2025
**Estado:** Producción (API Proxy Mode)

## Resumen de Cambios
Este plugin ha sido modificado para eliminar la dependencia de archivos JSON estáticos subidos manualmente y conectar directamente con la API oficial de SELAE.

## Arquitectura de Conexión

### Problema
La API de SELAE (`loteriasyapuestas.es`) no permite peticiones AJAX directas desde navegadores externos debido a políticas CORS (Cross-Origin Resource Sharing).

### Solución: Proxy API Interno
Se ha implementado un endpoint REST personalizado en WordPress que actúa como proxy:

1. **Frontend (JS):** Pide datos a `/wp-json/loteria-navidad/v1/datos/premios`
2. **Backend (PHP):** 
   - Verifica si tiene los datos en caché (Transient API).
   - Si no, hace una petición HTTP GET (`wp_remote_get`) a los servidores de SELAE.
   - Guarda la respuesta en caché por 60 segundos.
   - Devuelve el JSON al frontend.

## Endpoints Oficiales Implementados
Basado en la documentación "SELAE - Instrucciones Información Sorteos Navidad y El Niño campaña Navidad 2025.pdf":

| Tipo de Dato | Endpoint Interno | Endpoint Oficial SELAE |
|--------------|------------------|------------------------|
| Premios | `/premios` | `https://www.loteriasyapuestas.es/servicios/premioDecimoProvisionalWeb?s={ID}` |
| Buscar/Repartido | `/repartido` | `https://www.loteriasyapuestas.es/servicios/repartidoEn1?s={ID}` |

## ⚠️ Sobre la terminología "Provisional"
Es normal que la URL contenga la palabra `Provisional`. **Este es el endpoint correcto para el día 22 de Diciembre.**

- **¿Qué significa?**: En el contexto de Loterías del Estado, "Provisional" significa "Resultados en directo, pendientes de verificación final en acta".
- **¿Funcionará el día 22?**: **SÍ**. Es el feed oficial que alimenta las webs de medios y la propia web de Loterías durante el sorteo en vivo.
- **Después del sorteo**: Los datos de este endpoint se mantienen disponibles y válidos hasta que se publica la lista oficial en PDF, pero para widgets web, este JSON sigue siendo la fuente estándar de consulta rápida.

**ID Sorteo Configurado:** `1259409103` (Navidad 2025)

## ⚠️ PROBLEMAS COMUNES Y SOLUCIONES (TROUBLESHOOTING)

### 1. Error: "Unexpected token < in JSON" / Cuadro Negro en Debug Mode
**Causa:** El servidor de Loterías (SELAE) está bloqueando la IP de tu servidor mediante Akamai (Firewall). En lugar de devolver JSON, devuelve una página HTML de "Access Denied" (Error 403).
**Diagnóstico:**
- El Debug Mode muestra `Status: 200` o `403`.
- El `Preview` muestra código HTML (`<!DOCTYPE html>...`).
**Solución:**
- **Contactar con SELAE:** Debes solicitar que añadan la IP de tu servidor (o el dominio) a la **Whitelist de Akamai**.
- Sin este paso, el plugin no funcionará en el servidor (aunque funcione en local/localhost).

### 2. Error: "API Error 404"
**Causa:** WordPress no reconoce las rutas de la API interna (`/wp-json/loteria-navidad/v5/...`).
**Solución:**
- Ve a **Ajustes > Enlaces permanentes** en WordPress y pulsa "Guardar cambios" para regenerar el `.htaccess`.
- La versión V5.3 intenta hacer esto automáticamente al iniciarse.

### 3. El buscador recarga la página
**Causa:** Conflicto de IDs en versiones antiguas (V1-V4) o JavaScript no cargado.
**Solución:**
- Asegúrate de usar la **Versión V5+** (Arquitectura Event Delegation).
- Verifica que `wp_footer()` se está ejecutando en tu tema.

---

## 📞 Contacto Técnico
Para solicitar el desbloqueo a SELAE, proporcionar:
- IP del Servidor de Producción.
- Dominio (`theobjective.com`).
- User-Agent utilizado (ver código en `loteria-navidad-2025.php`).

## Notas para Desarrolladores
- **No tocar:** La lógica de los shortcodes en JS espera la estructura exacta que devuelve SELAE.
- **Caché:** Si necesitas ver datos en tiempo real real sin caché, borra los transients `loteria_nav_premios_{ID}` en la base de datos o espera 60s.
- **User-Agent:** Las peticiones salen con el UA `Mozilla/5.0 (WordPress; LoteriaNavidadPlugin/1.0)`.
