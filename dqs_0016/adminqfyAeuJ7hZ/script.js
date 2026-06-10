document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('cartLink').onclick = function(event) {
        event.preventDefault();
        document.getElementById('cartModal').style.display = 'block';
        loadCartItems();
    }
    document.getElementsByClassName('close')[0].onclick = function() {
        document.getElementById('cartModal').style.display = 'none';
    }
    window.onclick = function(event) {
        if (event.target == document.getElementById('cartModal')) {
            document.getElementById('cartModal').style.display = 'none';
        }
    }
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const productoId = this.getAttribute('data-id');
            fetch('carrito.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'producto_id=' + productoId
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('cartModal').style.display = 'block';
                    loadCartItems();
                } else {
                    console.error('Error:', data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
    function loadCartItems() {
        fetch('ver_carrito.php')
        .then(response => response.text())
        .then(data => {
            document.getElementById('cartItems').innerHTML = data;
            addCartFunctionality();
        })
        .catch(error => console.error('Error:', error));
    }
    function addCartFunctionality() {
        const emptyCartButton = document.getElementById('emptyCartButton');
        if (emptyCartButton) {
            emptyCartButton.onclick = function() {
                fetch('vaciar_carrito.php', { method: 'POST' })
                .then(response => response.text())
                .then(data => {
                    loadCartItems();
                })
                .catch(error => console.error('Error:', error));
            }
        }
        document.querySelectorAll('.remove-item').forEach(button => {
            button.onclick = function() {
                const itemId = this.closest('.cart-item').getAttribute('data-id');
                fetch('eliminar_producto.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + itemId
                })
                .then(response => response.text())
                .then(data => {
                    loadCartItems();
                })
                .catch(error => console.error('Error:', error));
            }
        });
        document.querySelectorAll('.increase-quantity').forEach(button => {
            button.onclick = function() {
                const itemId = this.closest('.cart-item').getAttribute('data-id');
                fetch('modificar_cantidad.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + itemId + '&action=increase'
                })
                .then(response => response.text())
                .then(data => {
                    loadCartItems();
                })
                .catch(error => console.error('Error:', error));
            }
        });
        document.querySelectorAll('.decrease-quantity').forEach(button => {
            button.onclick = function() {
                const itemId = this.closest('.cart-item').getAttribute('data-id');
                fetch('modificar_cantidad.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + itemId + '&action=decrease'
                })
                .then(response => response.text())
                .then(data => {
                    loadCartItems();
                })
                .catch(error => console.error('Error:', error));
            }
        });
        const continueShoppingButton = document.getElementById('continueShoppingButton');
        if (continueShoppingButton) {
            continueShoppingButton.onclick = function() {
                document.getElementById('cartModal').style.display = 'none';
            }
        }
    }
    document.querySelectorAll('.producto img').forEach(img => {
        img.addEventListener('click', function() {
            const modal = document.getElementById('myModal');
            const modalImg = document.getElementById('modalImage');
            const carousel = this.closest('.carousel');
            const images = carousel.querySelectorAll('img');
            let currentIndex = Array.from(images).indexOf(this);
            modal.style.display = 'block';
            modalImg.src = this.src;
            const prevButton = modal.querySelector('.prev');
            const nextButton = modal.querySelector('.next');
            prevButton.addEventListener('click', () => {
                currentIndex = (currentIndex - 1 + images.length) % images.length;
                modalImg.src = images[currentIndex].src;
            });
            nextButton.addEventListener('click', () => {
                currentIndex = (currentIndex + 1) % images.length;
                modalImg.src = images[currentIndex].src;
            });
            const closeButton = modal.querySelector('.close');
            closeButton.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        });
    });
    window.onclick = function(event) {
        const modal = document.getElementById('myModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
    document.getElementById('sortSelect').addEventListener('change', function() {
        document.getElementById('sortForm').submit();
    });
});



let lastAction = null;
let lastActionId = null;
let lastActionElement = null;

function handleCancel(event, id) {
    event.preventDefault();
    lastAction = 'cancel';
    lastActionId = id;
    lastActionElement = document.getElementById('gift-' + id);
    lastActionElement.style.display = 'none';
    document.getElementById('notification-message').innerText = 'Regalo cancelado. ';
    document.getElementById('notification').style.display = 'block';

    // Realizar la llamada AJAX inmediatamente
    fetch('cancelar_regalo.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id })
    }).then(response => response.json()).then(data => {
        if (data.success) {
            console.log('Regalo cancelado correctamente.');
            updateTotals();
        } else {
            console.error('Error al cancelar el regalo:', data.error);
            lastActionElement.style.display = 'block';
        }
    });

    // Ocultar la notificación después de 3 segundos
    setTimeout(() => {
        document.getElementById('notification').style.display = 'none';
    }, 3000); // 3 segundos para deshacer

    return false;
}

function handleConfirm(event, id) {
    event.preventDefault();
    lastAction = 'confirm';
    lastActionId = id;
    lastActionElement = document.getElementById('gift-' + id);
    lastActionElement.style.display = 'none';
    document.getElementById('notification-message').innerText = 'Pago confirmado. ';
    document.getElementById('notification').style.display = 'block';

    // Realizar la llamada AJAX inmediatamente
    fetch('confirmar_pago.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id })
    }).then(response => response.json()).then(data => {
        if (data.success) {
            console.log('Pago confirmado correctamente.');
            updateTotals();
        } else {
            console.error('Error al confirmar el pago:', data.error);
            lastActionElement.style.display = 'block';
        }
    });

    // Ocultar la notificación después de 5 segundos
    setTimeout(() => {
        document.getElementById('notification').style.display = 'none';
    }, 5000); // 5 segundos para deshacer

    return false;
}

function undoAction() {
    if (lastAction === 'cancel') {
        fetch('deshacer_cancelacion.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: lastActionId })
        }).then(response => response.json()).then(data => {
            if (data.success) {
                console.log('Cancelación deshecha correctamente.');
                lastActionElement.style.display = 'block';
                updateTotals();
            } else {
                console.error('Error al deshacer la cancelación:', data.error);
            }
        });
    } else if (lastAction === 'confirm') {
        fetch('deshacer_confirmacion.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: lastActionId })
        }).then(response => response.json()).then(data => {
            if (data.success) {
                console.log('Confirmación de pago deshecha correctamente.');
                lastActionElement.style.display = 'block';
                updateTotals();
            } else {
                console.error('Error al deshacer la confirmación de pago:', data.error);
            }
        });
    }
    lastAction = null;
    lastActionId = null;
    document.getElementById('notification').style.display = 'none';
}


