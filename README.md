# Plugin WordPress - VERSIÓN COMPLETA (API OFICIAL)

## ✅ Todos los 5 widgets incluidos

Este plugin incluye TODOS los widgets de una vez y conecta automáticamente con la API de Loterías y Apuestas del Estado:

1. `[loteria_premios]` - Premios principales
2. `[loteria_comprobador]` - Comprobador
3. `[loteria_buscar]` - Buscar número (dónde se vende)
4. `[loteria_admin_premiadas]` - Administraciones premiadas
5. `[loteria_buscador_admin]` - Buscador de administraciones

## 📦 Instalación

1. **Borra plugins anteriores** de lotería si los tienes
2. Descarga: `loteria-navidad-2025-completo.zip`
3. **Plugins → Añadir nuevo → Subir plugin**
4. Instalar y **ACTIVAR**

¡Y listo! No necesitas configurar nada más.

## 🚀 Cómo funciona (API Proxy)

Este plugin incluye un **Proxy Interno** que conecta con los servidores de SELAE (`www.loteriasyapuestas.es`) para obtener los datos oficiales en tiempo real.

- **Sin errores CORS**: El servidor de WordPress hace la petición, no el navegador.
- **Cache Inteligente**: Guarda los resultados durante 60 segundos para no saturar la API oficial.
- **Endpoints**:
  - `/wp-json/loteria-navidad/v1/datos/premios`
  - `/wp-json/loteria-navidad/v1/datos/repartido`

## 🐛 Solución de problemas

Si ves mensajes de error:
- **Error 502**: La web de Loterías está caída o bloqueando peticiones.
- **Resultados vacíos**: El sorteo aún no ha comenzado o no hay datos publicados.
