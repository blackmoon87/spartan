<?php
/** @var array $users */
/** @var array $orders */
/** @var array $stats */
/** @var array $errors */
/** @var array $old */
?>

<style>
    /* Stats Layout */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 3rem;
    }

    .card {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 1.5rem;
        backdrop-filter: blur(16px);
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        transition: var(--transition-smooth);
        position: relative;
        overflow: hidden;
    }

    .card:hover {
        border-color: var(--border-hover);
        transform: translateY(-4px);
    }

    .card-title {
        color: var(--color-muted);
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .card-value {
        font-size: 2rem;
        font-weight: 800;
        color: var(--color-text);
        margin-bottom: 0.5rem;
    }

    .card-footer {
        font-size: 0.75rem;
        color: var(--color-muted);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .highlight-purple {
        color: #a78bfa;
    }

    .highlight-green {
        color: var(--success);
    }

    /* Forms & Interactive Area */
    .grid-2col {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
    }

    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-label {
        display: block;
        color: var(--color-text);
        font-size: 0.85rem;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    .form-control {
        width: 100%;
        background: rgba(39, 39, 42, 0.6);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        padding: 0.75rem 1rem;
        color: var(--color-text);
        font-family: inherit;
        font-size: 0.95rem;
        transition: var(--transition-smooth);
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px var(--primary-glow);
    }

    .btn {
        width: 100%;
        background: linear-gradient(135deg, var(--primary) 0%, #6366f1 100%);
        color: #fff;
        border: none;
        border-radius: var(--radius-sm);
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition-smooth);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn:hover {
        opacity: 0.9;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25);
    }

    .btn-secondary {
        background: rgba(63, 63, 70, 0.5);
        border: 1px solid var(--border-color);
        color: var(--color-text);
    }

    .btn-secondary:hover {
        background: rgba(63, 63, 70, 0.8);
        border-color: var(--border-hover);
        box-shadow: none;
    }

    .btn-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        box-shadow: none;
        padding: 0.4rem 0.8rem;
        font-size: 0.8rem;
    }

    .btn-danger:hover {
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
    }

    .error-text {
        color: #f87171;
        font-size: 0.75rem;
        margin-top: 0.25rem;
        display: block;
    }

    /* Lists and Tables */
    .table-container {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 1.5rem;
        backdrop-filter: blur(16px);
        margin-bottom: 3rem;
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
    }

    .table-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 1rem;
    }

    .table-title {
        font-size: 1.25rem;
        font-weight: 700;
        background: linear-gradient(135deg, #fff 0%, var(--color-muted) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .table-badge {
        background: rgba(255, 255, 255, 0.1);
        padding: 0.2rem 0.6rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--color-text);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    th {
        color: var(--color-muted);
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
    }

    td {
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
        font-size: 0.95rem;
        vertical-align: middle;
    }

    tr:last-child td {
        border-bottom: none;
    }

    .user-relation-list {
        list-style: none;
        margin-top: 0.5rem;
        padding-left: 0.5rem;
    }

    .user-relation-item {
        font-size: 0.8rem;
        color: var(--color-muted);
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .user-relation-item::before {
        content: '•';
        color: var(--primary);
    }

    .order-status {
        display: inline-block;
        padding: 0.2rem 0.6rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-completed {
        background: rgba(16, 185, 129, 0.15);
        color: #34d399;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .status-pending {
        background: rgba(245, 158, 11, 0.15);
        color: #fbbf24;
        border: 1px solid rgba(245, 158, 11, 0.3);
    }

    /* Redirect Sandbox Section */
    .sandbox-footer {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        justify-content: center;
        background: rgba(24, 24, 27, 0.45);
        border: 1px solid var(--border-color);
        padding: 1.5rem;
        border-radius: var(--radius-lg);
    }
</style>

<!-- 1. Caching & Statistics -->
<div class="stats-grid">
    <div class="card">
        <div class="card-title">Total Orders (Cached)</div>
        <div class="card-value highlight-purple"><?= $stats['total_orders'] ?></div>
        <div class="card-footer">
            <span>Aggregated Orders count</span>
            <span class="table-badge">Active</span>
        </div>
    </div>
    
    <div class="card">
        <div class="card-title">Total Revenue (Cached)</div>
        <div class="card-value highlight-green">$<?= number_format($stats['total_revenue'], 2) ?></div>
        <div class="card-footer">
            <span>Eager Sum of totals</span>
            <span class="table-badge">USD</span>
        </div>
    </div>

    <div class="card">
        <div class="card-title">Cache Status</div>
        <div class="card-value" style="font-size: 1.5rem; padding-top: 0.5rem;"><?= $stats['generated_at'] ?></div>
        <div class="card-footer">
            <span>Cache lifetime: 10s</span>
            <span class="highlight-purple">Auto-invalidates</span>
        </div>
    </div>
</div>

<!-- 2. Dual Forms (Validated Inputs & DB Creates) -->
<div class="grid-2col">
    <!-- Form A: Add Customer -->
    <div class="card">
        <h3 class="table-title" style="margin-bottom: 1.5rem;">Add Customer</h3>
        <form action="/user" method="POST">
            <!-- CSRF Token Input -->
            <input type="hidden" name="_csrf" value="<?= $this->csrfTokenValue() ?>">

            <div class="form-group">
                <label class="form-label" for="user_name">Customer Name</label>
                <input class="form-control" type="text" name="name" id="user_name" placeholder="John Doe" value="<?= htmlspecialchars($old['name'] ?? '') ?>" required>
                <?php if (isset($errors['name'])): ?>
                    <span class="error-text">⚠ <?= htmlspecialchars($errors['name']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="user_email">Email Address</label>
                <input class="form-control" type="email" name="email" id="user_email" placeholder="john@example.com" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
                <?php if (isset($errors['email'])): ?>
                    <span class="error-text">⚠ <?= htmlspecialchars($errors['email']) ?></span>
                <?php endif; ?>
            </div>

            <button class="btn" type="submit">Create Customer</button>
        </form>
    </div>

    <!-- Form B: Create Order -->
    <div class="card">
        <h3 class="table-title" style="margin-bottom: 1.5rem;">Place New Order</h3>
        <form action="/order" method="POST">
            <!-- CSRF Token Input -->
            <input type="hidden" name="_csrf" value="<?= $this->csrfTokenValue() ?>">

            <div class="form-group">
                <label class="form-label" for="order_user">Target Customer</label>
                <select class="form-control" name="user_id" id="order_user" required>
                    <option value="">-- Select Customer --</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>" <?= (isset($old['user_id']) && (int)$old['user_id'] === (int)$user['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['user_id'])): ?>
                    <span class="error-text">⚠ <?= htmlspecialchars($errors['user_id']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="order_status">Order Status</label>
                <select class="form-control" name="status" id="order_status" required>
                    <option value="pending" <?= (isset($old['status']) && $old['status'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                    <option value="completed" <?= (isset($old['status']) && $old['status'] === 'completed') ? 'selected' : '' ?>>Completed</option>
                </select>
                <?php if (isset($errors['status'])): ?>
                    <span class="error-text">⚠ <?= htmlspecialchars($errors['status']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="order_total">Order Total Amount ($)</label>
                <input class="form-control" type="text" name="total" id="order_total" placeholder="99.99" value="<?= htmlspecialchars($old['total'] ?? '') ?>" required>
                <?php if (isset($errors['total'])): ?>
                    <span class="error-text">⚠ <?= htmlspecialchars($errors['total']) ?></span>
                <?php endif; ?>
            </div>

            <button class="btn" type="submit">Place Order</button>
        </form>
    </div>
</div>

<!-- 3. Table representation showing Eager Loaded collection relationships (O(1) relation execution) -->
<div class="table-container">
    <div class="table-header">
        <h3 class="table-title">Customers &amp; Eager Loaded Orders <span style="font-size: 0.8rem; font-weight: normal; color: var(--color-muted);">[HasMany Relation]</span></h3>
        <span class="table-badge">O(1) Database Load</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Loaded Orders Relation</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td style="font-weight: 600;"><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <?php if (empty($user['orders'])): ?>
                            <span style="color: var(--color-muted); font-size: 0.8rem; font-style: italic;">No orders placed</span>
                        <?php else: ?>
                            <ul class="user-relation-list">
                                <?php foreach ($user['orders'] as $order): ?>
                                    <li class="user-relation-item">
                                        Order #<?= $order['id'] ?> - $<?= number_format((float)$order['total'], 2) ?> 
                                        <span style="font-size: 0.7rem; opacity: 0.8;">(<?= htmlspecialchars($order['status']) ?>)</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- 4. Table representation showing raw QueryBuilder Joins & Method Spoofing deletes -->
<div class="table-container">
    <div class="table-header">
        <h3 class="table-title">Orders List <span style="font-size: 0.8rem; font-weight: normal; color: var(--color-muted);">[QueryBuilder INNER JOIN &amp; Method Spoofing DELETE]</span></h3>
        <span class="table-badge">Live DB Reads</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer Name</th>
                <th>Total</th>
                <th>Status</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--color-muted); padding: 2rem;">No orders placed yet. Select a customer and create one above!</td>
                </tr>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?= $order['id'] ?></td>
                        <td style="font-weight: 500;"><?= htmlspecialchars($order['customer_name']) ?></td>
                        <td style="font-weight: 600; color: #a78bfa;">$<?= number_format((float)$order['total'], 2) ?></td>
                        <td>
                            <span class="order-status status-<?= $order['status'] ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </td>
                        <td style="font-size: 0.85rem; color: var(--color-muted);"><?= $order['created_at'] ?></td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <!-- Form Method Spoofing: PUT (Toggle Status) -->
                                <form action="/order/<?= $order['id'] ?>" method="POST" style="margin: 0;">
                                    <input type="hidden" name="_csrf" value="<?= $this->csrfTokenValue() ?>">
                                    <input type="hidden" name="_method" value="PUT">
                                    <input type="hidden" name="status" value="<?= $order['status'] === 'completed' ? 'pending' : 'completed' ?>">
                                    <button type="submit" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; width: auto; white-space: nowrap;">
                                        Mark as <?= $order['status'] === 'completed' ? 'Pending' : 'Completed' ?>
                                    </button>
                                </form>

                                <!-- Form Method Spoofing: DELETE -->
                                <form action="/order/<?= $order['id'] ?>" method="POST" onsubmit="return confirm('Are you sure you want to delete this order?');" style="margin: 0;">
                                    <input type="hidden" name="_csrf" value="<?= $this->csrfTokenValue() ?>">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="btn btn-danger" style="width: auto;">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- 5. Sandbox security redirects test -->
<div class="table-header" style="margin-bottom: 1rem;">
    <h3 class="table-title">Security Safeguards Testing Sandbox</h3>
</div>
<div class="sandbox-footer">
    <a class="btn btn-secondary" href="/redirect-test?url=http://localhost:8000/" style="width: auto;">
        Safe Local Redirect (Allowed)
    </a>
    <a class="btn btn-secondary" href="/redirect-test?url=https://malicious.domain.com/hack" style="width: auto; border-color: rgba(239, 68, 68, 0.4);" onclick="return confirm('Testing Open Redirect vulnerability. The framework should block the malicious URL and fall back to home.');">
        Malicious External Redirect (Intercepted)
    </a>
</div>
