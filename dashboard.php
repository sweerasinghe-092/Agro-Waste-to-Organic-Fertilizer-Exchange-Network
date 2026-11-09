        <!-- My waste listings (provider) -->
        <?php elseif ($view === 'listings' && $role === 'provider'): ?>
            <div class="data-panel">
                <div class="panel-heading"><div><h2>Your waste listings</h2><p>Keep your available materials up to date.</p></div><a class="primary-action" href="post_waste.php">Post waste</a></div>
                <div class="table-scroll">
                    <table class="modern-table">
                        <thead><tr><th>Listing</th><th>Quantity</th><th>Available from</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php $st = $conn->prepare('SELECT * FROM waste_listings WHERE provider_id = ? ORDER BY created_at DESC'); $st->bind_param('i', $uid); $st->execute(); $rs = $st->get_result(); if (!$rs->num_rows) echo '<tr><td colspan="5" class="empty-cell">You have not posted any waste yet.</td></tr>'; while ($r = $rs->fetch_assoc()): ?>
                                <tr>
                                    <td><?= e($r['title']) ?></td>
                                    <td><?= e($r['quantity_available']) ?> <?= e($r['unit']) ?></td>
                                    <td><?= e($r['available_from'] ?: '—') ?></td>
                                    <td><span class="status-tag"><?= e(ucfirst($r['status'])) ?></span></td>
                                    <td><a href="post_waste.php?edit=<?= $r['listing_id'] ?>">Edit</a></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <!-- Requests -->
        <?php elseif ($view === 'requests'): ?>
            <div class="data-panel">
                <div class="panel-heading"><div><h2><?= $role === 'provider' ? 'Requests for your waste' : 'Your waste requests' ?></h2><p>Track the progress of material exchanges.</p></div></div>
                <div class="table-scroll">
                    <table class="modern-table">
                        <thead><tr><th>Waste listing</th><th>Requested by</th><th>Quantity</th><th>Requested</th><th>Status / Action</th></tr></thead>
                        <tbody>
                            <?php
                            if ($role === 'provider') {
                                $st = $conn->prepare('SELECT r.*, w.title, u.full_name FROM exchange_requests r JOIN waste_listings w ON r.listing_id = w.listing_id JOIN users u ON r.producer_id = u.user_id WHERE w.provider_id = ? ORDER BY r.requested_at DESC');
                            } else {
                                $st = $conn->prepare('SELECT r.*, w.title, u.full_name FROM exchange_requests r JOIN waste_listings w ON r.listing_id = w.listing_id JOIN users u ON w.provider_id = u.user_id WHERE r.producer_id = ? ORDER BY r.requested_at DESC');
                            }
                            $st->bind_param('i', $uid); $st->execute(); $rs = $st->get_result();
                            if (!$rs->num_rows) echo '<tr><td colspan="5" class="empty-cell">No exchange requests yet.</td></tr>';
                            while ($r = $rs->fetch_assoc()): ?>
                                <tr>
                                    <td><?= e($r['title']) ?></td>
                                    <td><?= e($r['full_name']) ?></td>
                                    <td><?= e($r['quantity_requested']) ?> kg</td>
                                    <td><?= e(date('M j, Y', strtotime($r['requested_at']))) ?></td>
                                    <td>
                                        <?php if ($role === 'provider' && $r['status'] === 'pending'): ?>
                                            <form class="inline-actions" method="post">
                                                <input type="hidden" name="request_id" value="<?= $r['request_id'] ?>">
                                                <button class="mini-button" name="request_action" value="accepted">Accept</button>
                                                <button class="mini-button secondary" name="request_action" value="rejected">Reject</button>
                                            </form>
                                        <?php else: ?><span class="status-tag"><?= e(ucfirst($r['status'])) ?></span><?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <!-- My products (producer) -->
        <?php elseif ($view === 'products' && $role === 'producer'): ?>
            <div class="data-panel">
                <div class="panel-heading"><div><h2>Your fertilizer products</h2><p>Add the organic products you make available to buyers.</p></div></div>
                <form class="dashboard-form" method="post">
                    <div class="form-grid">
                        <label>Product name<input name="product_name" required></label>
                        <label>Fertilizer type
                            <select name="fertilizer_type"><option>Compost</option><option>Vermicompost</option><option>Bokashi</option><option>Liquid fertilizer</option><option>Other</option></select>
                        </label>
                        <label>Price (Rs.)<input type="number" name="price" min="0" step="0.01" required></label>
                        <label>Unit<input name="unit" value="kg" required></label>
                        <label>Stock quantity<input type="number" name="stock_quantity" min="0" value="0" required></label>
                        <label>District
                            <select name="district_id"><option value="0">Select district</option>
                                <?php $ds = $conn->query('SELECT district_id, district_name FROM districts ORDER BY district_name'); while ($d = $ds->fetch_assoc()): ?>
                                    <option value="<?= $d['district_id'] ?>" <?= $me['district_id'] == $d['district_id'] ? 'selected' : '' ?>><?= e($d['district_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </label>
                        <label class="full-field">Description<textarea name="description" rows="3"></textarea></label>
                    </div>
                    <button class="primary-action" name="add_product">Add product</button>
                </form>
                <div class="table-scroll">
                    <table class="modern-table">
                        <thead><tr><th>Product</th><th>Type</th><th>Price</th><th>Stock</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php $st = $conn->prepare('SELECT * FROM fertilizer_products WHERE producer_id = ? ORDER BY created_at DESC'); $st->bind_param('i', $uid); $st->execute(); $rs = $st->get_result(); if (!$rs->num_rows) echo '<tr><td colspan="5" class="empty-cell">No products added yet.</td></tr>'; while ($r = $rs->fetch_assoc()): ?>
                                <tr>
                                    <td><?= e($r['product_name']) ?></td>
                                    <td><?= e($r['fertilizer_type']) ?></td>
                                    <td>Rs. <?= number_format((float)$r['price'], 2) ?></td>
                                    <td><?= e($r['stock_quantity']) ?> <?= e($r['unit']) ?></td>
                                    <td><span class="status-tag"><?= e(ucfirst($r['status'])) ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <!-- Orders -->
        <?php elseif ($view === 'orders'): ?>
            <div class="data-panel">
                <div class="panel-heading"><div><h2><?= $role === 'producer' ? 'Customer orders' : 'Your order history' ?></h2><p><?= $role === 'producer' ? 'Orders containing your fertilizer products.' : 'Follow the status of your purchases.' ?></p></div></div>
                <div class="table-scroll">
                    <table class="modern-table">
                        <thead><tr><th>Order</th><th><?= $role === 'producer' ? 'Buyer' : 'Items total' ?></th><th>Date</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php
                            if ($role === 'buyer') {
                                $st = $conn->prepare('SELECT order_id, total_amount, order_date, order_status FROM orders WHERE buyer_id = ? ORDER BY order_date DESC');
                            } else {
                                $st = $conn->prepare('SELECT DISTINCT o.order_id, o.total_amount, o.order_date, o.order_status FROM orders o JOIN order_items oi ON o.order_id = oi.order_id JOIN fertilizer_products p ON oi.product_id = p.product_id WHERE p.producer_id = ? ORDER BY o.order_date DESC');
                            }
                            $st->bind_param('i', $uid); $st->execute(); $rs = $st->get_result();
                            if (!$rs->num_rows) echo '<tr><td colspan="4" class="empty-cell">No orders to show yet.</td></tr>';
                            while ($r = $rs->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?= e($r['order_id']) ?></td>
                                    <td>Rs. <?= number_format((float)$r['total_amount'], 2) ?></td>
                                    <td><?= e(date('M j, Y', strtotime($r['order_date']))) ?></td>
                                    <td><span class="status-tag"><?= e(ucfirst($r['order_status'])) ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>