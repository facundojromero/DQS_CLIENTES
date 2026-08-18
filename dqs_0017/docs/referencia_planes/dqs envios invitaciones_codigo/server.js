const express = require('express');
const app = express();
const whatsappRoutes = require('./whatsapp');
const debugRoutes = require('./sessions');
const open = require('open').default;
const path = require('path');
const cors = require('cors');
const PORT = 3000;


app.use(cors());
app.use(express.static(__dirname));
app.use(express.json());
app.use('/', whatsappRoutes);
app.use('/', debugRoutes);


app.listen(PORT, () => {
    console.clear();
    const url = `http://localhost:${PORT}/start-session/1`;
    console.log(`
✨ BIENVENIDO AL SERVIDOR ✨

👉 Se abriá tu navegador con el siguiente link
   🚀 ${url}
   
Inicia la sesión de Whatsapp escaneando QR
`);

    if (open) {
        open(url)
            .catch(err => console.log(`No se pudo abrir el navegador automáticamente. Error: ${err.message}. Abre el link manualmente.`));
    } else {
        console.log("No se pudo importar la función para abrir el navegador. Abre el link manualmente.");
    }
});