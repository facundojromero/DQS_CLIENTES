const botonEnviar = document.getElementById('btn-enviar');
const estadoMensaje = document.getElementById('estado');
const logContainer = document.getElementById('log-container');
const mensajePlural = document.getElementById('mensaje-plural');
const mensajeSingular = document.getElementById('mensaje-singular');
const variableButtons = document.querySelectorAll('#variable-buttons .dynamic-button');
const formatButtons = document.querySelectorAll('#format-buttons .dynamic-button');
const USUARIO_ID = '1';
const incluirImagenCheckbox = document.getElementById('incluir-imagen'); // MODIFICACIÓN: Referencia al checkbox

let webBaseUrl = '';

function insertAtCursor(textArea, textToInsert) {
    const start = textArea.selectionStart;
    const end = textArea.selectionEnd;
    const value = textArea.value;
    textArea.value = value.substring(0, start) + textToInsert + value.substring(end);
    textArea.selectionStart = textArea.selectionEnd = start + textToInsert.length;
    textArea.focus();
}


function getActiveTextarea() {
    return document.activeElement === mensajePlural ? mensajePlural : mensajeSingular;
}

window.addEventListener('DOMContentLoaded', async () => {
    let defaultTemplatePlural = '';
    let defaultTemplateSingular = '';
    
    try {
        const configResponse = await fetch('http://localhost:3000/get-config');
        const configData = await configResponse.json();
        
        webBaseUrl = configData.web_base_url;
        
        defaultTemplatePlural = `*{{invitados}}*
Con gran alegría queremos invitarlos a nuestro casamiento ❤️✉️

Por favor confirmen asistencia ingresando al link
${webBaseUrl}?busqueda={{codigo}}#rsvp

Código de Invitación: {{codigo}}

¡Los esperamos!

Maria y Jose`;

        defaultTemplateSingular = `*{{invitados}}*
Con gran alegría queremos invitarte a nuestro casamiento ❤️✉️

Por favor confirma asistencia ingresando al link
${webBaseUrl}?busqueda={{codigo}}#rsvp

Código de Invitación: {{codigo}}

¡Te esperamos!

Maria y Jose`;

    } catch (err) {
        console.error('Error al obtener la configuración del servidor:', err);
        
        defaultTemplatePlural = `*{{invitados}}*
Con gran alegría queremos invitarlos a nuestro casamiento ❤️✉️

Por favor confirmen asistencia ingresando al link
${webBaseUrl}?busqueda={{codigo}}#rsvp

Código de Invitación: {{codigo}}

¡Los esperamos!

Maria y Jose`;

        defaultTemplateSingular = `*{{invitados}}*
Con gran alegría queremos invitarte a nuestro casamiento ❤️✉️

Por favor confirma asistencia ingresando al link
${webBaseUrl}?busqueda={{codigo}}#rsvp

Código de Invitación: {{codigo}}

¡Te esperamos!

Maria y Jose`;
    }

    try {
        const pluralResponse = await fetch('http://localhost:3000/load-template/plural');
        const pluralData = await pluralResponse.json();
        if (pluralData.mensaje) {
            mensajePlural.value = pluralData.mensaje;
        } else {
            mensajePlural.value = defaultTemplatePlural;
        }

        const singularResponse = await fetch('http://localhost:3000/load-template/singular');
        const singularData = await singularResponse.json();
        if (singularData.mensaje) {
            mensajeSingular.value = singularData.mensaje;
        } else {
            mensajeSingular.value = defaultTemplateSingular;
        }
    } catch (error) {
        console.error('Error al cargar las plantillas:', error);
        mensajePlural.value = defaultTemplatePlural;
        mensajeSingular.value = defaultTemplateSingular;
    }
});

variableButtons.forEach(button => {
    button.addEventListener('click', () => {
        const variableName = button.getAttribute('data-variable');
        const activeTextarea = getActiveTextarea();
        
        if (variableName === 'web_base_url') {
            insertAtCursor(activeTextarea, webBaseUrl);
        } else {
            insertAtCursor(activeTextarea, `{{${variableName}}}`);
        }
    });
});


formatButtons.forEach(button => {
    button.addEventListener('click', () => {
        const formatType = button.getAttribute('data-format');
        const activeTextarea = getActiveTextarea();
        const start = activeTextarea.selectionStart;
        const end = activeTextarea.selectionEnd;
        const selectedText = activeTextarea.value.substring(start, end);

        let formattedText = '';
        if (formatType === 'bold') {
            formattedText = `*${selectedText}*`;
        } else if (formatType === 'italic') {
            formattedText = `_${selectedText}_`;
        } else if (formatType === 'strikethrough') {
            formattedText = `~${selectedText}~`;
        }
        
        const value = activeTextarea.value;
        activeTextarea.value = value.substring(0, start) + formattedText + value.substring(end);
        activeTextarea.focus();
    });
});

botonEnviar.addEventListener('click', () => {
    botonEnviar.disabled = true;
    estadoMensaje.textContent = 'Iniciando el proceso de envío...';
    estadoMensaje.className = '';
    logContainer.innerHTML = '';
    
    const mensajePluralTexto = mensajePlural.value;
    const mensajeSingularTexto = mensajeSingular.value;
    const incluirImagen = incluirImagenCheckbox.checked;

    fetch(`http://localhost:3000/start-send/${USUARIO_ID}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },

        body: JSON.stringify({ 
            mensaje_plural: mensajePluralTexto, 
            mensaje_singular: mensajeSingularTexto,
            incluir_imagen: incluirImagen 
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => Promise.reject(err));
        }
        return response.json();
    })
    .then(data => {
        console.log('Proceso de envío iniciado:', data);
        estadoMensaje.textContent = 'Proceso de envío iniciado. Esperando actualizaciones...';

        const intervalId = setInterval(() => {
            fetch(`http://localhost:3000/progress/${USUARIO_ID}`)
            .then(res => res.json())
            .then(progress => {
                logContainer.innerHTML = '';
                progress.log.forEach(update => {
                    const p = document.createElement('p');
                    p.textContent = update.message;
                    p.className = update.type;
                    logContainer.appendChild(p);
                });

                estadoMensaje.innerHTML = `✅ Proceso en curso...<br>Enviados: ${progress.enviados}<br>Errores: ${progress.errores}<br>Total: ${progress.total}`;

                if (progress.completed) {
                    clearInterval(intervalId);
                    botonEnviar.disabled = false;
                    estadoMensaje.innerHTML = `✅ ¡Proceso finalizado!<br>Enviados: ${progress.enviados}<br>Errores: ${progress.errores}<br>Total: ${progress.total}`;
                    estadoMensaje.className = 'success';
                    
                    fetch('http://localhost:3000/save-template', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ mensaje_plural: mensajePluralTexto, mensaje_singular: mensajeSingularTexto })
                    }).then(res => res.json()).then(data => {
                        console.log('Plantillas guardadas:', data.mensaje);
                    }).catch(err => {
                        console.error('Error al guardar las plantillas:', err);
                    });
                }
            });
        }, 2000);
    })
    .catch(error => {
        botonEnviar.disabled = false;
        const errorMessage = (error && error.error) ? error.error : 'No se pudo conectar con el servidor.';
        estadoMensaje.textContent = '❌ Error: ' + errorMessage;
        estadoMensaje.className = 'error';
        console.error('Error en la solicitud:', error);
    });
});