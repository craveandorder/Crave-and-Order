<?php
/**
 * Reusable category page template.
 * Products are hardcoded (matching original HTML exactly) and used as
 * immediate fallback when the DB is empty or not yet imported.
 */
require_once 'db.php';

$hardcoded = [
  'brownie' => [
    ['name'=>'Classic Chocolate Brownie',    'price'=>120,'image'=>'brownies/classic-brownie.jpg',              'ingredients'=>'Dark Chocolate, Unsalted Butter, Sugar, Milk, Maida, Cocoa Powder, Baking Powder, Salt'],
    ['name'=>'Fudge Chocolate Brownie',      'price'=>130,'image'=>'brownies/fudge-chocolate-brownie.jpg',      'ingredients'=>'Dark chocolate, butter, sugar, flour, cocoa powder, milk, vanilla essence, baking powder'],
    ['name'=>'Choco Chip Brownie',           'price'=>135,'image'=>'brownies/choco-chip-brownie.jpg',           'ingredients'=>'Dark chocolate, butter, sugar, flour, milk, vanilla essence, baking powder, chocolate chips'],
    ['name'=>'Peanut Butter Swirl Brownie',  'price'=>150,'image'=>'brownies/peanut-butter-swirl-brownie.jpg',  'ingredients'=>'Dark chocolate, butter, sugar, flour, milk, vanilla essence, baking powder, peanut butter'],
    ['name'=>'Orange Zest Chocolate Brownie','price'=>140,'image'=>'brownies/orange-zest-chocolate-brownie.jpg','ingredients'=>'Dark chocolate, butter, sugar, flour, milk, vanilla essence, baking powder, orange zest'],
    ['name'=>'Coffee Mocha Brownie',         'price'=>145,'image'=>'brownies/coffee-mocha-brownie.jpg',         'ingredients'=>'Dark chocolate, butter, sugar, flour, milk, vanilla essence, baking powder, coffee powder'],
    ['name'=>'Chocolate Strawberry Brownie', 'price'=>160,'image'=>'brownies/chocolate-strawberry-brownie.jpg', 'ingredients'=>'Dark chocolate, butter, sugar, flour, milk, strawberry essence, dried strawberries'],
  ],
  'cake' => [
    ['name'=>'Chocolate Truffle Cake','price'=>550,'image'=>'cakes/chocolate-truffle-cake.jpg','ingredients'=>'Dark chocolate, fresh cream, butter, sugar, flour, cocoa powder, vanilla essence'],
    ['name'=>'Black Forest Cake',     'price'=>500,'image'=>'cakes/black-forest-cake.jpg',     'ingredients'=>'Chocolate sponge, fresh cream, cherries, cocoa powder, sugar, butter'],
    ['name'=>'Red Velvet Cake',       'price'=>600,'image'=>'cakes/red-velvet-cake.jpg',       'ingredients'=>'Flour, cocoa powder, butter, sugar, buttermilk, cream cheese, vanilla essence, strawberry'],
    ['name'=>'Oreo Chocolate Cake',   'price'=>450,'image'=>'cakes/oreo-chocolate-cake.jpg',   'ingredients'=>'Flour, butter, sugar, fresh cream, vanilla essence'],
    ['name'=>'Strawberry Cake',       'price'=>480,'image'=>'cakes/strawberry-cake.jpg',       'ingredients'=>'Flour, butter, sugar, fresh cream, strawberry pulp, vanilla essence'],
    ['name'=>'Mango Cake',            'price'=>470,'image'=>'cakes/mango-cake.jpg',            'ingredients'=>'Flour, butter, sugar, fresh cream, mango chunks, vanilla essence'],
  ],
  'donut' => [
    ['name'=>'Strawberry Donut',        'price'=>60, 'image'=>'donuts/strawberry-donut.jpg',               'ingredients'=>'All-purpose flour, Sugar, Baking powder, Strawberry syrup, Milk, Food color'],
    ['name'=>'Chocolate Caviour Donut', 'price'=>75, 'image'=>'donuts/choco-caviour-chocolate-donut.jpg',  'ingredients'=>'All-purpose flour, Sugar, Dark chocolate, Fresh cream, Cocoa powder, Chocolate caviar pearls'],
    ['name'=>'Macha Donut',             'price'=>65, 'image'=>'donuts/macha-donut.jpg',                    'ingredients'=>'All-purpose flour, Sugar, Matcha powder, Powdered sugar, Milk, White chocolate'],
    ['name'=>'Caramel Donut',           'price'=>80, 'image'=>'donuts/caramel-donut.jpg',                  'ingredients'=>'All-purpose flour, Sugar, Butter, Fresh cream, Vanilla essence, Salt'],
    ['name'=>'Alcapone Donut',          'price'=>95, 'image'=>'donuts/alcapone-donut.jpg',                 'ingredients'=>'All-purpose flour, Sugar, White chocolate, Butter, Cream cheese, Milk powder'],
    ['name'=>'Red Velvet Donut',        'price'=>115,'image'=>'donuts/red-velvet-donut.jpg',               'ingredients'=>'All-purpose flour, Sugar, Butter, Fresh cream, Vanilla essence, Red food coloring'],
  ],
  'coffee' => [
    ['name'=>'Hot Coffee',             'price'=>50, 'image'=>'coffee/Hot-Coffee.jpg',                'ingredients'=>'Coffee powder, Hot water, Milk, Sugar'],
    ['name'=>'Irish Coffee',           'price'=>190,'image'=>'coffee/Irish Coffee.jpg',              'ingredients'=>'Coffee, Hot water, Irish whiskey, Sugar, Fresh cream'],
    ['name'=>'Mocha Coffee',           'price'=>90, 'image'=>'coffee/mocha-coffee.jpg',              'ingredients'=>'Coffee, Milk, Cocoa Powder, Sugar, Whipped Cream, Chocolate Shavings'],
    ['name'=>'Mint Mojito Iced Coffee','price'=>120,'image'=>'coffee/mint-mojito-iced-coffee.jpg',   'ingredients'=>'Coffee, Mint leaves, Mojito syrup, Ice cubes, Milk'],
    ['name'=>'Nutella Coffee',         'price'=>150,'image'=>'coffee/nuttella-coffe.jpg',            'ingredients'=>'Coffee, Nutella, Milk, Whipped Cream, Chocolate Shavings'],
    ['name'=>'Raspberry White Coffee', 'price'=>200,'image'=>'coffee/Raspberry-white-Coffee.jpg',   'ingredients'=>'Coffee, Raspberry syrup, Milk, Whipped Cream, Vanilla essence'],
  ],
  'chocolate' => [
    ['name'=>'Hazelnut Chocolate',   'price'=>60, 'image'=>'chocolates/hazelnut chocolate.jpg',   'ingredients'=>'Hazelnut, Cocoa powder, Sugar, Milk powder, Cocoa butter, Palm oil, Vanilla extract'],
    ['name'=>'Truffle Chocolate',    'price'=>45, 'image'=>'chocolates/truffle chocolate.jpg',    'ingredients'=>'Cocoa powder, Dark chocolate, Fresh cream, Butter, Sugar, Vanilla extract'],
    ['name'=>'Almond Chocolate',     'price'=>65, 'image'=>'chocolates/Almond-chocolate.jpg',     'ingredients'=>'Almonds, Cocoa powder, Sugar, Milk powder, Cocoa butter, Vanilla extract'],
    ['name'=>'Ruby Chocolate',       'price'=>105,'image'=>'chocolates/Ruby Chocolate.jpg',       'ingredients'=>'Cocoa butter, Sugar, Milk powder, Skimmed milk powder, Whey powder, Citric acid'],
    ['name'=>'White Chocolate',      'price'=>70, 'image'=>'chocolates/White Chocolate .jpg',     'ingredients'=>'Cocoa butter, Sugar, Milk powder, Skimmed milk powder, Vanilla extract'],
    ['name'=>'Strawberry Chocolate', 'price'=>45, 'image'=>'chocolates/strawberry chocolate.jpg', 'ingredients'=>'Chocolate, Butter, Fresh cream, Vanilla essence, Strawberry essence'],
  ],
  'ice-cream' => [
    ['name'=>'Mint Chocolate Ice Cream','price'=>60,'image'=>'ice cream/Mint Chocolate Ice Cream.jpg',     'ingredients'=>'Milk, Fresh cream, Sugar, Mint extract, Dark chocolate chips, Cocoa powder, Vanilla extract'],
    ['name'=>'Butter Pecan Ice Cream',  'price'=>75,'image'=>'ice cream/butter pecan ice cream.jpg',       'ingredients'=>'Milk, Fresh cream, Sugar, Butter, Roasted pecans, Vanilla extract, Salt'],
    ['name'=>'Cookie & Cream Ice Cream','price'=>65,'image'=>'ice cream/Coockie & Cream Ice CReam.jpg',    'ingredients'=>'Milk, Fresh cream, Sugar, Vanilla extract, Crushed chocolate cookies, Cocoa powder'],
    ['name'=>'Raspberry Ice Cream',     'price'=>80,'image'=>'ice cream/Raspberry Ice Cream.jpg',          'ingredients'=>'Milk, Fresh cream, Sugar, Raspberry puree, Vanilla extract, Lemon juice'],
    ['name'=>'Blueberry Ice Cream',     'price'=>95,'image'=>'ice cream/Blueberry Ice Cream.jpg',          'ingredients'=>'Milk, Fresh cream, Sugar, Blueberry puree, Vanilla extract, Salt'],
    ['name'=>'Red Velvet Ice Cream',    'price'=>95,'image'=>'ice cream/red-velvet-ice-cream.jpg',         'ingredients'=>'Milk, Fresh cream, Sugar, Vanilla essence, Red food coloring, Fresh cream frosting'],
  ],
  'pastry' => [
    ['name'=>'Vanilla Cream Pastry',     'price'=>60,'image'=>'pastries/Vanilla Cream Pastry.jpg',     'ingredients'=>'Refined flour, butter, powdered sugar, fresh milk, whipped dairy cream, vanilla essence, baking powder'],
    ['name'=>'Chocolate Cream Pastry',   'price'=>75,'image'=>'pastries/Chocolate Cream Pastry.jpg',   'ingredients'=>'Refined flour, cocoa powder, butter, powdered sugar, fresh milk, whipped chocolate cream'],
    ['name'=>'Pineapple Pastry',         'price'=>85,'image'=>'pastries/Pineapple Pastry.jpg',         'ingredients'=>'Refined flour, butter, powdered sugar, fresh milk, whipped cream, pineapple crush, pineapple pieces'],
    ['name'=>'Butterscotch Pastry',      'price'=>80,'image'=>'pastries/Butterscotch Pastry.jpg',      'ingredients'=>'Refined flour, butter, brown sugar, fresh milk, whipped cream, butterscotch sauce, caramel chips'],
    ['name'=>'Chocolate Truffle Pastry', 'price'=>95,'image'=>'pastries/Chocolate Truffle Pastry.jpg', 'ingredients'=>'Dark chocolate, refined flour, butter, powdered sugar, fresh milk, whipped cream, chocolate ganache'],
    ['name'=>'Oreo Pastry',              'price'=>50,'image'=>'pastries/Oreo Pastry.jpg',              'ingredients'=>'Refined flour, butter, powdered sugar, fresh milk, whipped chocolate cream, crushed Oreo biscuits'],
    ['name'=>'Red Velvet Pastry',        'price'=>70,'image'=>'pastries/Red Velvet Pastry.jpg',        'ingredients'=>'Refined flour, butter, powdered sugar, buttermilk, cocoa powder, whipped cream cheese frosting'],
    ['name'=>'Black Forest Pastry',      'price'=>85,'image'=>'pastries/Black Forest Pastry.jpg',      'ingredients'=>'Refined flour, cocoa powder, butter, powdered sugar, fresh milk, whipped cream, black cherry filling'],
    ['name'=>'Strawberry Pastry',        'price'=>65,'image'=>'pastries/Strawberry Pastry.jpg',        'ingredients'=>'Refined flour, butter, powdered sugar, fresh milk, whipped cream, strawberry crush, strawberry pieces'],
    ['name'=>'Mango Pastry',             'price'=>80,'image'=>'pastries/Mango Pastry.jpg',             'ingredients'=>'Refined flour, butter, powdered sugar, fresh milk, whipped cream, mango pulp, mango glaze'],
  ],
  'bread' => [
    ['name'=>'White Bread',        'price'=>25,'image'=>'breads/White Bread.jpg',         'ingredients'=>'Refined flour, yeast, sugar, salt, lukewarm water, milk powder, butter, edible vegetable oil'],
    ['name'=>'Whole Wheat Bread',  'price'=>35,'image'=>'breads/Whole Wheat Bread.jpg',   'ingredients'=>'Whole wheat flour, yeast, brown sugar, salt, lukewarm water, milk powder, butter, edible vegetable oil'],
    ['name'=>'Multigrain Bread',   'price'=>45,'image'=>'breads/Multigrain Bread.jpg',    'ingredients'=>'Whole wheat flour, oats, flax seeds, sunflower seeds, sesame seeds, yeast, brown sugar, salt'],
    ['name'=>'Garlic Bread',       'price'=>50,'image'=>'breads/Garlic Bread.jpg',        'ingredients'=>'Refined flour, yeast, sugar, salt, butter, garlic paste, mixed herbs, edible vegetable oil'],
    ['name'=>'Herb Focaccia Bread','price'=>55,'image'=>'breads/Herb Focaccia Bread.jpg', 'ingredients'=>'Refined flour, yeast, olive oil, salt, sugar, lukewarm water, rosemary, mixed herbs, black olives'],
    ['name'=>'Milk Bread',         'price'=>30,'image'=>'breads/Milk Bread.jpg',          'ingredients'=>'Refined flour, yeast, sugar, salt, fresh milk, butter, milk powder, edible vegetable oil'],
  ],
];

// Try DB first; fall back to hardcoded
$products = [];
try {
    if ($conn && !$conn->connect_error) {
        $stmt = $conn->prepare("SELECT * FROM products WHERE category = ? ORDER BY id");
        if ($stmt) {
            $stmt->bind_param("s", $category);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            if (!empty($rows)) $products = $rows;
        }
    }
} catch (Exception $e) {}

if (empty($products)) $products = isset($hardcoded[$category]) ? $hardcoded[$category] : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Crave &amp; Order</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include 'header.php'; ?>

<main id="order">
    <a href="menu.php" class="back-btn">&#8592; Back To Menu</a>
    <section>
        <h1 class="h1"><?= htmlspecialchars($pageTitle) ?></h1><br><br>
        <div class="order-list">
            <?php if (empty($products)): ?>
                <p style="text-align:center;padding:40px;">No products found.</p>
            <?php else: foreach ($products as $p):
                $pname  = htmlspecialchars($p['name']);
                $pprice = (float)(isset($p['price']) ? $p['price'] : 0);
                $pimg   = htmlspecialchars(isset($p['image']) ? $p['image'] : '');
                $pingr  = htmlspecialchars(isset($p['ingredients']) ? $p['ingredients'] : '');
            ?>
                <div class="order-item">
                    <img src="<?= $pimg ?>" alt="<?= $pname ?>">
                    <div class="order-info">
                        <h3><?= $pname ?></h3>
                        <?php if ($pingr): ?>
                        <div class="ingredients-box">
                            <h4>Ingredients:</h4>
                            <ul><?= $pingr ?></ul>
                        </div>
                        <?php endif; ?>
                        <span class="price">&#8377;<?= number_format($pprice, 0) ?></span>
                        <button onclick="addToCart('<?= addslashes($pname) ?>', <?= $pprice ?>)">Add to Cart</button>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </section><br><br><br>
</main>

<?php include 'footer.php'; ?>

<script>
function addToCart(name, price) {
    fetch('cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=add&name=' + encodeURIComponent(name) + '&price=' + price
    })
    .then(function(r){ return r.json(); })
    .then(function(data) {
        if (data.redirect) {
            alert(data.message);
            window.location.href = data.redirect;
        } else {
            alert(data.message);
            var cartLink = document.querySelector('nav a[href="cart.php"]');
            if (cartLink && data.count) cartLink.textContent = '🛒 Cart (' + data.count + ')';
        }
    })
    .catch(function(){ alert('Error. Please try again.'); });
}
</script>
</body>
</html>
