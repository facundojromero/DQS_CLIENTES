const express = require('express');
const mysql = require('mysql2/promise');
// const venom = require('venom-bot');
const wppconnect = require('@wppconnect-team/wppconnect');
const fs = require('fs/promises');
const path = require('path');
const config = require('./config');

const app = express();
app.use(express.json());

const router = express.Router();
const sessions = {};
const progress = {};

const TEMPLATE_FILE_PLURAL = path.join(__dirname, 'template_plural.txt');
const TEMPLATE_FILE_SINGULAR = path.join(__dirname, 'template_singular.txt');

function getDbConfig() {
    return config.db;
}


router.get('/get-config', (req, res) => {
    try {
        res.json({ web_base_url: config.web.base_url });
    } catch (err) {
        res.status(500).json({ error: 'Error al cargar la configuración.' });
    }
});


router.get('/start-session/:usuarioId', async (req, res) => {
    const usuarioId = req.params.usuarioId;

    if (sessions[usuarioId]) {
        return res.redirect(`/connected-status/${usuarioId}?message=Sesión ya iniciada para este usuario.`);
    }

    console.log(`⏳ Iniciando sesión para el usuario ${usuarioId}...`);
    console.log("Por favor, espere mientras se carga el código QR.");

    try {
        const client = await wppconnect.create({
            session: `session_${usuarioId}`,
            headless: false,
            useChrome: true,
            browserArgs: ['--no-sandbox', '--disable-setuid-sandbox'],
            multidevice: true,
            disableSpins: true,
            log: 'silent'
        });

        sessions[usuarioId] = client;

        client.onStateChange(state => {
            console.log(`Estado sesión ${usuarioId}:`, state);
            if (state === 'CONNECTED') {
                console.log(`✅ Usuario ${usuarioId} conectado a WhatsApp.`);
            }
        });

        client.onStreamChange(qr => {
            console.log(`QR para el usuario ${usuarioId}:`);
            console.log(qr);
        });

        res.redirect(`/connected-status/${usuarioId}?message=Por favor, escanee el código QR en la consola.`);
    } catch (error) {
        console.error('Error al iniciar la sesión:', error);
        res.status(500).send(`Error al iniciar la sesión: ${error.message}`);
    }
});


router.get('/connected-status/:usuarioId', (req, res) => {
    const usuarioId = req.params.usuarioId;
    const message = req.query.message || 'Esperando conexión...';
    
    const isConnected = sessions[usuarioId] && sessions[usuarioId].isLoggedIn();

    let statusMessage = message;
    if (isConnected) {
      statusMessage = `✅ Sesión para el usuario conectada con éxito a WhatsApp.`;
    }
    
    res.send(`
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado de la Sesión</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
        }

        .container {
            max-width: 90%;
            margin: auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            flex: 0.7;
        }

        @media (max-width: 600px) {
            .container {
                padding: 10px;
            }
        }

        h1 {
            text-align: center;
            color: #333;
        }

        p {
            color: #666;
            margin: 10px 0;
        }

        .navbar-link {
            display: flex;
            align-items: center;
            color: white;
            text-align: center;
            padding: 10px 20px;
            text-decoration: none;
            background-color: #444;
            border-radius: 5px;
            transition: background-color 0.3s;
            border: none;
            font-size: 1em;
            cursor: pointer;
            justify-content: center;
            width: 50%;
            margin: 0 auto;
        }

        .navbar-link:hover {
            background-color: #555;
            color: white;
        }

        .link-container {
            margin-top: 20px;
        }

        .link-container a {
            font-size: 1.1em;
            text-decoration: none;
        }

        .link-container a:hover {
            text-decoration: underline;
        }

        /* Estilos del footer */
        .footer {
            margin-top: auto;
            width: 100%;
            text-align: center;
            padding: 20px 0;
            background-color: #333;
            color: #fff;
            font-size: 0.9em;
        }

        .footer-link {
            color: #fff;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-link:hover {
            color: #00bcd4;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Estado de la Sesión de WhatsApp</h1>
        <p>${statusMessage}</p>
        <p>Ya estas conectado para enviar las invitaciones.</p>
        <div class="link-container">
            <a href="/" class="navbar-link">Ir a la página de envíos</a>
        </div>
    </div>
    
    <footer class="footer">
        &copy; <?= date('Y') ?> <a href="https://www.instagram.com/dijequesi.ar/" target="_blank" class="footer-link">Dije que Sí - Todos los derechos reservados.</a>
    </footer>
    
</body>
</html>
`);
});


router.get('/load-template/:type', async (req, res) => {
    const type = req.params.type;
    const filePath = type === 'plural' ? TEMPLATE_FILE_PLURAL : TEMPLATE_FILE_SINGULAR;
    try {
        const data = await fs.readFile(filePath, 'utf-8');
        res.json({ mensaje: data });
    } catch (err) {
        res.status(200).json({ mensaje: null });
    }
});


router.post('/save-template', async (req, res) => {
    const mensajePlural = req.body.mensaje_plural;
    const mensajeSingular = req.body.mensaje_singular;
    if (!mensajePlural || !mensajeSingular) {
        return res.status(400).json({ error: 'Mensajes no proporcionados.' });
    }
    try {
        await fs.writeFile(TEMPLATE_FILE_PLURAL, mensajePlural);
        await fs.writeFile(TEMPLATE_FILE_SINGULAR, mensajeSingular);
        res.json({ mensaje: 'Plantillas guardadas con éxito.' });
    } catch (err) {
        console.error('Error al guardar las plantillas:', err);
        res.status(500).json({ error: 'Error al guardar las plantillas.' });
    }
});


router.post('/start-send/:usuarioId', async (req, res) => {
    const usuarioId = req.params.usuarioId;
    const client = sessions[usuarioId];
    const mensajePluralBase = req.body.mensaje_plural;
    const mensajeSingularBase = req.body.mensaje_singular;
    const incluirImagen = req.body.incluir_imagen;

    if (!client) {
        return res.status(400).json({ error: 'No hay sesión activa para este usuario.' });
    }

    progress[usuarioId] = {
        total: 0,
        enviados: 0,
        errores: 0,
        log: [],
        completed: false
    };

    try {
        const connectionState = await client.getConnectionState();
        if (connectionState !== 'CONNECTED') {
            return res.status(400).json({ error: `La sesión no está conectada. Estado actual: ${connectionState}` });
        }

        const dbConfig = getDbConfig();
        const connection = await mysql.createConnection(dbConfig);
        const [invitados] = await connection.execute(`SELECT 
    mi.telefono, 
    mi.telefono2, 
    a.id AS id_invitados, 
    g.id_invitados_tel, 
    a.codigo, 
    a.apellido, 
    a.nombre, 
    e.invitados, 
    a.cantidad_mayores, 
    a.cantidad_menores, 
    a.ingreso, 
    g.tel_enviar 
FROM pre_invitados a -- MODIFICADO
LEFT JOIN (
    SELECT 
        a.id_invitados, 
        CASE 
            WHEN cantidad_mayores + cantidad_menores < 2 THEN nombre_invitado 
            ELSE CONCAT(
                IF(COUNT(*) > 1, 
                    SUBSTRING_INDEX(GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', '), ', ', COUNT(*) - 1), 
                    GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', ')
                ), 
                ' y ', 
                SUBSTRING_INDEX(GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', '), ', ', -1)
            ) 
        END AS invitados 
    FROM pre_invitados_listado_mesa a -- MODIFICADO
    INNER JOIN pre_invitados b ON a.id_invitados = b.id -- MODIFICADO
    GROUP BY a.id_invitados
) e ON a.id = e.id_invitados 
LEFT JOIN (
    SELECT 
        id_invitados, 
        id AS id_invitados_tel, 
        (id * 1000000 + id_invitados) AS id_unico, 
        tel_enviar 
    FROM pre_invitados_tel -- MODIFICADO
) g ON a.id = g.id_invitados 
INNER JOIN invitados_a_enviar h ON g.id_invitados = h.id_invitados AND g.id_invitados_tel = h.id_invitados_tel -- SE MANTIENE
INNER JOIN cliente mi -- SE MANTIENE
WHERE a.activo = 1 
ORDER BY a.apellido, a.nombre;`);
        progress[usuarioId].total = invitados.length;

        (async () => {
            for (const inv of invitados) {
                const telefono = inv.tel_enviar.toString();
                const telefonoConPrefijo = `549${telefono.startsWith('549') ? telefono.substring(3) : telefono}`;

                try {
                    const totalInvitados = (inv.cantidad_mayores || 0) + (inv.cantidad_menores || 0);
                    const mensajeBase = totalInvitados > 1 ? mensajePluralBase : mensajeSingularBase;

                    const idInvitadoFormateado = String(inv.id_invitados).padStart(4, '0');
                    const urlImagen = `${config.web.base_url}${config.web.admin_folder}/invitaciones/${idInvitadoFormateado}.jpg`;
                    
                    let mensajeFinal = mensajeBase;
                    
                    for (const key in inv) {
                        if (inv.hasOwnProperty(key)) {
                            const placeholder = `{{${key}}}`;
                            mensajeFinal = mensajeFinal.replace(new RegExp(placeholder, 'g'), inv[key]);
                        }
                    }

                    mensajeFinal = mensajeFinal.replace(`${config.web.base_url}?busqueda={{codigo}}#rsvp`, `${config.web.base_url}?busqueda=${inv.codigo}#rsvp`);


                    if (incluirImagen) {

                        await client.sendImage(`${telefonoConPrefijo}@c.us`, urlImagen, 'invitacion', mensajeFinal);
                    } else {

                        await client.sendText(`${telefonoConPrefijo}@c.us`, mensajeFinal);
                    }



                    const fechaEnvio = new Date().toISOString().slice(0, 19).replace('T', ' ');
                    await connection.beginTransaction();
                    try {
                        await connection.execute(`INSERT INTO invitados_enviados (id_invitados, id_invitados_tel, tel_enviar, fecha_envio) VALUES (?, ?, ?, ?)`, [inv.id_invitados, inv.id_invitados_tel, inv.tel_enviar, fechaEnvio]);
						await connection.execute(`INSERT INTO registro_mensajes_enviados (id_invitados, id_invitados_tel, tel_enviar, fecha_envio) VALUES (?, ?, ?, ?)`, [inv.id_invitados, inv.id_invitados_tel, inv.tel_enviar, fechaEnvio]);						
                        await connection.execute(`DELETE FROM invitados_a_enviar WHERE id_invitados = ? AND id_invitados_tel = ?`, [inv.id_invitados, inv.id_invitados_tel]);
                        await connection.commit();
                        
                        progress[usuarioId].enviados++;
                        progress[usuarioId].log.push({
                            type: 'success',
                            message: `✅ Enviado a: ${inv.nombre} ${inv.apellido} (${telefono})`
                        });

                    } catch (dbError) {
                        await connection.rollback();
                        progress[usuarioId].errores++;
                        progress[usuarioId].log.push({
                            type: 'error',
                            message: `❌ Error de BD para ${inv.nombre} (${telefono}): ${dbError.message}`
                        });
                    }
                } catch (err) {
                    progress[usuarioId].errores++;
                    progress[usuarioId].log.push({
                        type: 'error',
                        message: `❌ Error de envío a ${inv.nombre} (${telefono}): ${err.message}`
                    });
                }
            }
            progress[usuarioId].completed = true;
            connection.end();
            console.log(`Proceso de envío para ${usuarioId} finalizado.`);
        })();

        res.json({ mensaje: 'Proceso de envío iniciado' });

    } catch (err) {
        console.error('Error al iniciar el proceso de envío:', err);
        res.status(500).json({ error: err.message });
    }
});


router.get('/progress/:usuarioId', (req, res) => {
    const usuarioId = req.params.usuarioId;
    const currentProgress = progress[usuarioId] || { log: [], completed: false, enviados: 0, errores: 0 };
    res.json(currentProgress);
});

module.exports = router;