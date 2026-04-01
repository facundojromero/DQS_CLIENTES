document.addEventListener('DOMContentLoaded', function() {
    const cartModal = document.getElementById('cartModal');
    const freeGiftModal = document.getElementById('freeGiftModal');
    const freeGiftForm = document.getElementById('freeGiftForm');
    const freeGiftAmount = document.getElementById('freeGiftAmount');
    const freeGiftProductId = document.getElementById('freeGiftProductId');

    document.getElementById('cartLink').onclick = function(event) {
        event.preventDefault();
        cartModal.style.display = 'block';
        loadCartItems();
    };

    document.querySelectorAll('#cartModal .close').forEach(btn => {
        btn.onclick = function() {
            cartModal.style.display = 'none';
        };
    });

    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const productoId = this.getAttribute('data-id');
            addItemToCart(productoId);
        });
    });

    document.querySelectorAll('.add-free-gift').forEach(button => {
        button.addEventListener('click', function() {
            freeGiftProductId.value = this.getAttribute('data-id');
            freeGiftAmount.value = '';
            freeGiftModal.style.display = 'block';
            freeGiftAmount.focus();
        });
    });

    if (freeGiftForm) {
        freeGiftForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const amount = parseFloat(freeGiftAmount.value);
            if (isNaN(amount) || amount <= 0) {
                alert('Ingresá un monto válido mayor a 0.');
                return;
            }

            addItemToCart(freeGiftProductId.value, amount.toFixed(2));
            freeGiftModal.style.display = 'none';
        });
    }

    document.querySelectorAll('.close-free-gift').forEach(btn => {
        btn.addEventListener('click', function() {
            freeGiftModal.style.display = 'none';
        });
    });

    window.onclick = function(event) {
        if (event.target === cartModal) {
            cartModal.style.display = 'none';
        }
        if (freeGiftModal && event.target === freeGiftModal) {
            freeGiftModal.style.display = 'none';
        }

        const modalImage = document.getElementById('myModal');
        if (event.target === modalImage) {
            modalImage.style.display = 'none';
        }
    };

    function addItemToCart(productoId, montoLibre) {
        const params = new URLSearchParams();
        const urlParams = new URLSearchParams(window.location.search);
        const selectedCurrency = urlParams.get('currency') || '1';

        params.append('producto_id', productoId);
        params.append('currency', selectedCurrency);
        if (montoLibre !== undefined) {
            params.append('monto_libre', montoLibre);
        }

        fetch('carrito.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    cartModal.style.display = 'block';
                    loadCartItems();
                } else {
                    alert(data.message || 'No se pudo agregar al carrito.');
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function loadCartItems() {
        const urlParams = new URLSearchParams(window.location.search);
        const selectedCurrency = urlParams.get('currency') || '1';

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
                    .then(() => loadCartItems())
                    .catch(error => console.error('Error:', error));
            };
        }

        document.querySelectorAll('.remove-item').forEach(button => {
            button.onclick = function() {
                const itemId = this.closest('.cart-item').getAttribute('data-id');
                fetch('eliminar_producto.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + itemId
                })
                    .then(() => loadCartItems())
                    .catch(error => console.error('Error:', error));
            };
        });

        document.querySelectorAll('.increase-quantity').forEach(button => {
            button.onclick = function() {
                const itemId = this.closest('.cart-item').getAttribute('data-id');
                fetch('modificar_cantidad.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + itemId + '&action=increase'
                })
                    .then(() => loadCartItems())
                    .catch(error => console.error('Error:', error));
            };
        });

        document.querySelectorAll('.decrease-quantity').forEach(button => {
            button.onclick = function() {
                const itemId = this.closest('.cart-item').getAttribute('data-id');
                fetch('modificar_cantidad.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + itemId + '&action=decrease'
                })
                    .then(() => loadCartItems())
                    .catch(error => console.error('Error:', error));
            };
        });

        const continueShoppingButton = document.getElementById('continueShoppingButton');
        if (continueShoppingButton) {
            continueShoppingButton.onclick = function() {
                cartModal.style.display = 'none';
            };
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

    document.getElementById('sortSelect').addEventListener('change', function() {
        document.getElementById('sortForm').submit();
    });
});
