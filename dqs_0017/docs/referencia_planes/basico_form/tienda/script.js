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
    // FUNCIÓN CORREGIDA
    function loadCartItems() {
        // Obtener el valor de la moneda del parámetro 'currency' de la URL. Si no existe, usa "1" (Pesos) por defecto.
        const urlParams = new URLSearchParams(window.location.search);
        const selectedCurrency = urlParams.get('currency') || '1';
    
        // Cargar el contenido del carrito pasando la moneda como parámetro
        fetch(`ver_carrito.php?currency=${selectedCurrency}`)
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