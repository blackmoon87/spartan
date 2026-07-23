<tr>
    <td><?php echo htmlspecialchars(($task['title'] ?? '') ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
    <td>
        <?php if(($task['status'] ?? '') === 'done'): ?>
        <span class="badge badge-done">✓ Done</span>
        <?php elseif(($task['status'] ?? '') === 'in_progress'): ?>
        <span class="badge badge-progress">⏳ In Progress</span>
        <?php else: ?>
        <span class="badge badge-todo">📋 Todo</span>
        <?php endif; ?>
    </td>
    <td><span class="badge badge-<?php echo htmlspecialchars(($task['priority'] ?? 'medium') ?? '', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((ucfirst($task['priority'] ?? 'medium')) ?? '', ENT_QUOTES, 'UTF-8'); ?></span></td>
    <td style="font-size:.85rem; color:var(--text-muted);"><?php echo htmlspecialchars(($task['due_date'] ?? '—') ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
    <td>
        <?php if(($task['status'] ?? '') !== 'done'): ?>
        <form method="POST" action="/task/<?php echo htmlspecialchars(($task['id'] ?? 0) ?? '', ENT_QUOTES, 'UTF-8'); ?>/complete" style="display:inline;">
            <?php echo $this->csrfToken(); ?>
            <input type="hidden" name="_method" value="PUT">
            <button type="submit" style="padding:4px 12px; font-size:.8rem;">✓ Complete</button>
        </form>
        <?php endif; ?>
    </td>
</tr>
