  <!-- Cart -->
        <?php elseif ($view === 'cart'): ?>
            <div class="data-panel empty-state">
                <h2>Your cart is waiting</h2>
                <p>Explore organic fertilizer and add products to your cart.</p>
                <a class="primary-action" href="fertilizer_market.php">Browse fertilizer market</a>
            </div>

        <!-- Profile -->
        <?php elseif ($view === 'profile'): ?>
            <div class="data-panel">
                <div class="panel-heading"><div><h2>Personal information</h2><p>Keep your contact details current.</p></div></div>
                <form class="dashboard-form" method="post">
                    <div class="form-grid">
                        <label>Full name<input name="full_name" value="<?= e($me['full_name']) ?>" required></label>
                        <label>Email address<input value="<?= e($me['email']) ?>" disabled></label>
                        <label>Phone<input name="phone" value="<?= e($me['phone']) ?>" required></label>
                        <label>Account type<input value="<?= e(ucfirst($role)) ?>" disabled></label>
                        <label class="full-field">Address<textarea name="address" rows="3"><?= e($me['address']) ?></textarea></label>
                    </div>
                    <button class="primary-action" name="profile_update">Save changes</button>
                </form>
            </div>

        <!-- Notifications -->
        <?php elseif ($view === 'notifications'):?>
            <div class="data-panel">
                <div class="panel-heading"><div><h2>Notifications</h2><p>Important updates related to your account.</p></div></div>
                <?php $st = $conn->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 30'); $st->bind_param('i', $uid); $st->execute(); $rs = $st->get_result(); if (!$rs->num_rows) echo '<p class="empty-cell">You are all caught up. No notifications yet.</p>'; while ($n = $rs->fetch_assoc()): ?>
                    <div class="notification-row">
                        <span class="notification-dot"></span>
                        <div><b><?= e($n['content']) ?></b><small><?= e(date('M j, Y · g:i a', strtotime($n['created_at']))) ?></small></div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

    </main>
</div>
</body>
</html>