<tr>
    <td>{{ $task['title'] ?? '' }}</td>
    <td>
        @if(($task['status'] ?? '') === 'done')
        <span class="badge badge-done">✓ Done</span>
        @elseif(($task['status'] ?? '') === 'in_progress')
        <span class="badge badge-progress">⏳ In Progress</span>
        @else
        <span class="badge badge-todo">📋 Todo</span>
        @endif
    </td>
    <td><span class="badge badge-{{ $task['priority'] ?? 'medium' }}">{{ ucfirst($task['priority'] ?? 'medium') }}</span></td>
    <td style="font-size:.85rem; color:var(--text-muted);">{{ $task['due_date'] ?? '—' }}</td>
    <td>
        @if(($task['status'] ?? '') !== 'done')
        <form method="POST" action="/task/{{ $task['id'] ?? 0 }}/complete" style="display:inline;">
            @csrf
            <input type="hidden" name="_method" value="PUT">
            <button type="submit" style="padding:4px 12px; font-size:.8rem;">✓ Complete</button>
        </form>
        @endif
    </td>
</tr>
