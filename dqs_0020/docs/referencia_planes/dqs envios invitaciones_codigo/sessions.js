const express = require('express');

const router = express.Router();
const whatsappRoutes = require('./whatsapp');
const sessions = whatsappRoutes.sessions;

router.get('/sessions', (req, res) => {
    res.json({ sesiones_activas: Object.keys(sessions) });
});

module.exports = router;