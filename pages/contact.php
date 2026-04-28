<?php
// ============================================================
// pages/contact.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    
    $name    = clean($_POST['name'] ?? '');
    $email   = clean($_POST['email'] ?? '');
    $subject = clean($_POST['subject'] ?? '');
    $message = clean($_POST['message'] ?? '');

    // Validate
    if (strlen($name) < 2)      $errors['name'] = 'Please enter a valid name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address.';
    if (strlen($subject) < 3)   $errors['subject'] = 'Subject must be at least 3 characters.';
    if (strlen($message) < 10)  $errors['message'] = 'Message must be at least 10 characters.';

    if (!$errors) {
        // Save to database (optional)
        try {
            db()->prepare("
                INSERT INTO contact_messages (name, email, subject, message, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ")->execute([$name, $email, $subject, $message]);
            
            $success = true;
            // Don't redirect, let JS handle Formspree
        } catch (Exception $e) {
            $errors['general'] = 'Unable to send message. Please try again later.';
        }
    }
}

$pageTitle = 'Contact Us — ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<section class="section">
  <div class="container">
    <div class="section-header">
      <div class="label">Get In Touch</div>
      <div class="divider-gold"></div>
      <h1 class="section-title">Contact Us</h1>
      <p style="color:var(--text-muted);font-size:1.05rem;margin-top:0.5rem;">
        Have questions about our artisans, products, or services? We're here to help.
      </p>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:3rem;margin-top:3rem;">
      <!-- Contact Form -->
      <div>
        <h3 style="font-size:1.2rem;margin-bottom:1.5rem;color:var(--dark);">Send us a Message</h3>
        
        <?php if (!empty($errors['general'])): ?>
          <div class="alert alert-error" style="margin-bottom:1.5rem;"><?= e($errors['general']) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
          <div class="alert alert-success" style="margin-bottom:1.5rem;">Thank you! We've received your message and will get back to you soon.</div>
        <?php endif; ?>

        <form method="POST" action="" id="contactForm">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

          <div class="form-group">
            <label class="form-label">Your Name *</label>
            <input type="text" name="name" class="input <?= isset($errors['name']) ? 'input-error' : '' ?>"
                   value="<?= e($_POST['name'] ?? '') ?>" required placeholder="John Doe">
            <?php if (isset($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?>
          </div>

          <div class="form-group">
            <label class="form-label">Email Address *</label>
            <input type="email" name="email" class="input <?= isset($errors['email']) ? 'input-error' : '' ?>"
                   value="<?= e($_POST['email'] ?? '') ?>" required placeholder="you@example.com">
            <?php if (isset($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
          </div>

          <div class="form-group">
            <label class="form-label">Subject *</label>
            <input type="text" name="subject" class="input <?= isset($errors['subject']) ? 'input-error' : '' ?>"
                   value="<?= e($_POST['subject'] ?? '') ?>" required placeholder="How can we help?">
            <?php if (isset($errors['subject'])): ?><div class="field-error"><?= e($errors['subject']) ?></div><?php endif; ?>
          </div>

          <div class="form-group">
            <label class="form-label">Message *</label>
            <textarea name="message" class="input <?= isset($errors['message']) ? 'input-error' : '' ?>"
                      required placeholder="Your message..." style="resize:vertical;min-height:150px;"><?= e($_POST['message'] ?? '') ?></textarea>
            <?php if (isset($errors['message'])): ?><div class="field-error"><?= e($errors['message']) ?></div><?php endif; ?>
          </div>

          <button type="submit" class="btn btn-primary btn-full">Send Message</button>
        </form>
      </div>

      <!-- Contact Information -->
      <div>
        <h3 style="font-size:1.2rem;margin-bottom:2rem;color:var(--dark);">Contact Information</h3>
        
        <div style="margin-bottom:2.5rem;">
          <div style="font-weight:600;color:var(--gold);margin-bottom:0.5rem;">📍 Address</div>
          <p style="color:var(--text-muted);">
            Nairobi, Kenya<br>
            East Africa
          </p>
        </div>

        <div style="margin-bottom:2.5rem;">
          <div style="font-weight:600;color:var(--gold);margin-bottom:0.5rem;">📧 Email</div>
          <a href="mailto:sanaayakenya@gmail.com" style="color:var(--primary);text-decoration:none;">
            sanaayakenya@gmail.com
          </a>
        </div>

        <div style="margin-bottom:2.5rem;">
          <div style="font-weight:600;color:var(--gold);margin-bottom:0.5rem;">💬 Response Time</div>
          <p style="color:var(--text-muted);">
            We aim to respond to all inquiries within 24 hours during business days.
          </p>
        </div>

        <div style="margin-bottom:2.5rem;">
          <div style="font-weight:600;color:var(--gold);margin-bottom:0.5rem;">📱 M-Pesa Support</div>
          <p style="color:var(--text-muted);">
            For payment-related inquiries, please contact our support team at the email above.
          </p>
        </div>

        <div style="padding:2rem;background:var(--cream-dark);border-radius:var(--radius-lg);margin-top:2rem;">
          <div style="font-weight:600;margin-bottom:0.75rem;">💡 Quick Links</div>
          <ul style="list-style:none;padding:0;">
            <li style="margin-bottom:0.5rem;">
              <a href="<?= APP_URL ?>/pages/about.php" style="color:var(--primary);text-decoration:none;">Learn Our Story</a>
            </li>
            <li style="margin-bottom:0.5rem;">
              <a href="<?= APP_URL ?>/pages/products.php" style="color:var(--primary);text-decoration:none;">Browse Products</a>
            </li>
            <li style="margin-bottom:0.5rem;">
              <a href="<?= APP_URL ?>/register.php?role=artisan" style="color:var(--primary);text-decoration:none;">Become an Artisan</a>
            </li>
            <li>
              <a href="<?= APP_URL ?>/pages/home.php" style="color:var(--primary);text-decoration:none;">Back to Home</a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<?php if ($success): ?>
<script>
  // Submit to Formspree after successful database save
  fetch('https://formspree.io/f/xgopaypr', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      name: '<?= e($name) ?>',
      email: '<?= e($email) ?>',
      subject: '<?= e($subject) ?>',
      message: '<?= e($message) ?>'
    })
  }).catch(console.error);
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
