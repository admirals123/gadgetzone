<?php $base = base_path(); ?>
</main>

<footer class="site-footer">
  <div class="container footer-inner">
    <div class="footer-col">
      <a href="<?= $base ?>/index.php" class="logo">Gadget<span>Zone</span></a>
      <p class="footer-about">Your world. Next-level technology. Premium gadgets, delivered fast.</p>
    </div>
    <div class="footer-col">
      <h4>Shop</h4>
      <a href="<?= $base ?>/pages/shop.php?cat=smartphones">Smartphones</a>
      <a href="<?= $base ?>/pages/shop.php?cat=laptops">Laptops</a>
      <a href="<?= $base ?>/pages/shop.php?cat=audio">Audio</a>
      <a href="<?= $base ?>/pages/shop.php?cat=cameras">Cameras</a>
    </div>
    <div class="footer-col">
      <h4>Account</h4>
      <a href="<?= $base ?>/pages/myaccount.php">My Account</a>
      <a href="<?= $base ?>/pages/cart.php">Cart</a>
      <a href="<?= $base ?>/pages/login.php">Login</a>
    </div>
    <div class="footer-col">
      <h4>Support</h4>
      <p>support@gadgetzone.com</p>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; <?= date('Y') ?> GadgetZone. All rights reserved.</p>
  </div>
</footer>

<script src="<?= $base ?>/assets/js/main.js?v=<?= @filemtime(__DIR__ . '/../assets/js/main.js') ?>"></script>
</body>
</html>
