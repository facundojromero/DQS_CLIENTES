# DQS Envíos de invitaciones por WhatsApp

## Mantenimiento operativo de sesiones (whatsapp-web.js)

- Dependencias recomendadas:
  - `npm i whatsapp-web.js@latest qrcode-terminal@latest`
- Si aparecen fallos raros de envío de imágenes:
  - Borrar la carpeta de sesión de `LocalAuth` para `session_1` (o la sesión afectada), normalmente dentro de `.wwebjs_auth`.
  - Reiniciar `server.js` y volver a escanear el QR.
- El envío se ejecuta únicamente cuando el estado de sesión está en `CONNECTED` (`ready === true`).
