  <?php $__fc = $contact ?? []; ?>
  <footer class="site-footer">
    <?php if (array_filter($__fc)): ?>
    <div class="footer-social">
      <?php if (!empty($__fc['telegram'])): ?>
      <a class="footer-icon" style="background:#2AABEE" href="https://t.me/<?= htmlspecialchars(ltrim($__fc['telegram'], '@')) ?>" target="_blank" rel="noopener" title="@<?= htmlspecialchars(ltrim($__fc['telegram'], '@')) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M21 4 3 11l6 2.2M21 4l-3.2 16L9 13.2M21 4 9 13.2m0 0v5.4l3-3.2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
      </a>
      <?php endif; ?>
      <?php if (!empty($__fc['viber'])): ?>
      <a class="footer-icon" style="background:#7360F2" href="viber://chat?number=<?= urlencode($__fc['viber']) ?>" title="<?= htmlspecialchars($__fc['viber']) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 3a8 8 0 0 0-7 11.9L4 21l6.3-1a8 8 0 1 0 1.7-17Z" stroke-linejoin="round"></path><path d="M9 10.5c1 2.2 2.3 3.5 4.5 4.5l1.2-1.2c.2-.2.5-.3.8-.2.9.3 1.8.4 2.5.4.4 0 .7.3.7.7v1.8c0 .4-.3.7-.7.7-6.1 0-11-4.9-11-11 0-.4.3-.7.7-.7H9.5c.4 0 .7.3.7.7 0 .8.1 1.6.4 2.5.1.3 0 .6-.2.8L9 10.5Z" stroke-linecap="round" stroke-linejoin="round"></path></svg>
      </a>
      <?php endif; ?>
      <?php if (!empty($__fc['whatsapp'])): ?>
      <a class="footer-icon" style="background:#25D366" href="https://wa.me/<?= urlencode(preg_replace('/\D/', '', $__fc['whatsapp'])) ?>" target="_blank" rel="noopener" title="<?= htmlspecialchars($__fc['whatsapp']) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 3a8.5 8.5 0 0 0-7.4 12.7L3 21l5.5-1.4A8.5 8.5 0 1 0 12 3Z" stroke-linejoin="round"></path><path d="M8.7 9c.2-.5.4-.5.7-.5h.5c.2 0 .4 0 .6.5s.6 1.5.7 1.6c.1.2.1.4 0 .6-.1.2-.2.3-.4.5s-.3.3-.1.6c.2.3.8 1.3 1.7 2.1 1.1 1 2 1.3 2.3 1.4.3.1.5.1.7-.1s.7-.8.9-1.1c.2-.3.4-.2.6-.1s1.5.7 1.8.9c.2.1.4.2.5.3.1.2.1.9-.2 1.4-.3.6-1.6 1.2-2.4 1.3-.6.1-1.4.2-4.5-1s-5-4.5-5.2-4.7c-.2-.2-1.3-1.8-1.3-3.4s.8-2.4 1.1-2.8c.3-.3.6-.4.8-.4Z"></path></svg>
      </a>
      <?php endif; ?>
      <?php if (!empty($__fc['instagram'])): ?>
      <a class="footer-icon" style="background:linear-gradient(135deg,#F58529,#DD2A7B 60%,#8134AF)" href="https://instagram.com/<?= htmlspecialchars(ltrim($__fc['instagram'], '@')) ?>" target="_blank" rel="noopener" title="@<?= htmlspecialchars(ltrim($__fc['instagram'], '@')) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="3" width="18" height="18" rx="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.5" cy="6.5" r="0.8" fill="currentColor"></circle></svg>
      </a>
      <?php endif; ?>
      <?php if (!empty($__fc['phone'])): ?>
      <a class="footer-icon" style="background:var(--terracotta)" href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $__fc['phone'])) ?>" title="<?= htmlspecialchars($__fc['phone']) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M6 3h3l2 5-2.5 1.5a11 11 0 0 0 5 5L15 12l5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 4 5a2 2 0 0 1 2-2Z" stroke-linecap="round" stroke-linejoin="round"></path></svg>
      </a>
      <?php endif; ?>
      <?php if (!empty($__fc['email'])): ?>
      <a class="footer-icon" style="background:var(--ink-soft)" href="mailto:<?= htmlspecialchars($__fc['email']) ?>" title="<?= htmlspecialchars($__fc['email']) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="5" width="18" height="14" rx="2.5"></rect><path d="m4 6.5 8 6 8-6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
      </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <p>&copy; <?= date('Y') ?> Bee Genius · Валентин</p>
  </footer>
</div>
<script>
function openContactModal(e) {
  if (e) e.preventDefault();
  var el = document.getElementById('contact-modal-backdrop');
  if (el) el.style.display = 'flex';
  return false;
}
function closeContactModal() {
  var el = document.getElementById('contact-modal-backdrop');
  if (el) el.style.display = 'none';
}
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') closeContactModal();
});

(function () {
  var forms = document.querySelectorAll('form[data-track-changes]');
  if (!forms.length) return;
  var dirty = false;

  forms.forEach(function (form) {
    var btnrow = form.querySelector('.btnrow');
    var badge = null;
    if (btnrow) {
      badge = document.createElement('span');
      badge.textContent = '● Є незбережені зміни';
      badge.style.cssText = 'display:none;align-items:center;color:var(--terracotta);font-size:13px;font-weight:600;margin-left:4px';
      btnrow.appendChild(badge);
    }
    function markDirty() {
      dirty = true;
      if (badge) badge.style.display = 'inline-flex';
    }
    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);
    form.addEventListener('submit', function () { dirty = false; });
  });

  window.addEventListener('beforeunload', function (e) {
    if (!dirty) return;
    e.preventDefault();
    e.returnValue = '';
  });
})();
</script>
</body>
</html>
