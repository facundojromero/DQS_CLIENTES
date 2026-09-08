const express = require('express');

const router = express.Router();

router.get('/sessions', (req, res) => {
    res.json({ sesiones_activas: Object.keys(sessions) });
});

module.exports = router;