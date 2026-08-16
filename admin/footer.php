<?php $base = base_path(); ?>
      <div class="admin-footer-note">
        <span>GadgetZone Admin</span>
        <span>&middot;</span>
        <span>Logged in as <?= isset($_SESSION['role']) ? e(str_replace('_',' ', $_SESSION['role'])) : 'admin' ?></span>
      </div>
    </div>
  </div>
</div>

<script src="<?= $base ?>/admin/admin.js"></script>
</body>
</html>
