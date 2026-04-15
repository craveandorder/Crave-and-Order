<?php require_once 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Crave &amp; Order</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'header.php'; ?>

<section class="section" id="menu">
    <div class="hero-content">
        <h2 style="color:white;">Our Menu</h2>
        <p style="color:rgb(253,250,250);">Explore our wide variety of delicious desserts and cakes. Find your favorite treats and place your order today!</p><br>
    </div>
    <div class="menu-items">
        <div class="menu-item"><a href="brownies.php">
            <h3>Brownies</h3>
            <img src="images/brownie.jpg" alt="Brownies"/></a>
        </div>
        <div class="menu-item"><a href="donuts.php">
            <h3>Donuts</h3>
            <img src="images/donuts.jpg" alt="Donuts"/></a>
        </div>
        <div class="menu-item"><a href="cakes.php">
            <h3>Cakes</h3>
            <img src="images/cake.jpg" alt="Cake"/></a>
        </div>
        <div class="menu-item"><a href="coffee.php">
            <h3>Coffee</h3>
            <img src="images/coffee.jpg" alt="Coffee"/></a>
        </div>
        <div class="menu-item"><a href="chocolates.php">
            <h3>Chocolates</h3>
            <img src="images/chocolates.jpg" alt="Chocolates"/></a>
        </div>
        <div class="menu-item"><a href="icecream.php">
            <h3>Ice-cream</h3>
            <img src="images/icecream.jpg" alt="Ice-cream"/></a>
        </div>
        <div class="menu-item"><a href="pasteries.php">
            <h3>Pasteries</h3>
            <img src="images/pasteries.jpg" alt="Pasteries"/></a>
        </div>
        <div class="menu-item"><a href="bread.php">
            <h3>Bread</h3>
            <img src="images/bread.jpg" alt="Bread"/></a>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>

</body>
</html>
