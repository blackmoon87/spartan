<ul class="results-list">
    @if(empty($users))
        <li style="text-align: center; padding: 2.5rem 1rem; color: var(--color-muted); border: 1px dashed var(--border-color); border-radius: var(--radius-md); list-style: none;">
            No matching customers found. Try searching for something else.
        </li>
    @else
        @foreach($users as $user)
            <li class="result-item">
                <div>
                    <h4 style="font-weight: 600; color: #fff;">{{ $user['name'] }}</h4>
                    <span style="font-size: 0.85rem; color: var(--color-muted);">{{ $user['email'] }}</span>
                </div>
                <div>
                    <span class="badge-active">Customer</span>
                </div>
            </li>
        @endforeach
    @endif
</ul>
