const express = require('express');
const open = require('open');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3000;


app.use(express.json());


app.listen(PORT, () => {
    console.clear();
    const url = `http://localhost:${PORT}/start-session/1`;
    console.log(`
✨ BIENVENIDO AL SERVIDOR ✨

👉 Se abriá tu navegador con el siguiente link
   🚀 ${url}
   
Inicia la sesión de Whatsapp escaneando QR
   
`);

    open(url)
        .catch(err => console.log(`No se pudo abrir el navegador automáticamente: ${err.message}. Abre el link manualmente.`));
});