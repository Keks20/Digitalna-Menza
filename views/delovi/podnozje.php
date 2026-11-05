<?php
/**
 * Kraj svake stranice: Bootstrap JS i skripte te stranice.
 *
 * Stranica pre uključivanja može da postavi:
 *   $skripte – niz putanja do JS fajlova, npr. ['js/login.js'] (nije obavezno)
 *
 * Upotreba u view fajlu:
 *   <?php require __DIR__ . '/delovi/podnozje.php'; ?>
 */
$skripte = $skripte ?? [];
?>
  <!-- Bootstrap JS (meni, modal, tabovi...) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php foreach ($skripte as $skripta): ?>
  <script src="<?php echo htmlspecialchars($skripta); ?>"></script>
<?php endforeach; ?>
</body>
</html>
