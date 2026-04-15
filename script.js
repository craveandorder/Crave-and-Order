// ============================================================
//  Crave & Order - script.js (PHP Version)
//  Cart & UI logic - Auth is handled server-side via PHP sessions
// ============================================================

function addToCart(name, price) {
    fetch('cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add&name=${encodeURIComponent(name)}&price=${price}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.redirect) {
            alert(data.message);
            window.location.href = data.redirect;
        } else {
            alert(data.message);
            let cartLink = document.querySelector('nav a[href="cart.php"]');
            if (cartLink && data.count) cartLink.textContent = `🛒 Cart (${data.count})`;
        }
    })
    .catch(() => alert('Something went wrong. Please try again.'));
}

function changeQty(index, change) {
    fetch('cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=change_qty&index=${index}&change=${change}`
    }).then(() => location.reload());
}

function removeItem(index) {
    fetch('cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=remove&index=${index}`
    }).then(() => location.reload());
}

function clearCart() {
    if (!confirm('Clear all items?')) return;
    fetch('cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=clear'
    }).then(() => location.reload());
}

function goToPayment() {
    window.location.href = 'payment.php';
}

function togglePassword() {
    let pass = document.getElementById('password');
    if (pass) pass.type = pass.type === 'password' ? 'text' : 'password';
}

document.addEventListener('DOMContentLoaded', () => {
    let radios = document.querySelectorAll('input[name="payment"]');
    radios.forEach(r => {
        r.addEventListener('change', () => {
            let upi  = document.getElementById('upiBox');
            let card = document.getElementById('cardBox');
            if (upi)  upi.style.display  = 'none';
            if (card) card.style.display = 'none';
            if (r.value === 'upi'  && upi)  upi.style.display  = 'block';
            if (r.value === 'card' && card) card.style.display = 'block';
        });
    });
});
