@php $level = $level ?? 0; @endphp

<tr class="align-middle {{ $level === 0 ? 'table-parent-row' : '' }}">
    <td class="ps-{{ 4 + ($level * 3) }}">
        @if($level === 0)
            <i class="fa-solid fa-folder-tree text-primary me-2"></i>
        @else
            <i class="fa-solid fa-caret-right me-2 opacity-50"></i>
        @endif
        {{ app()->getLocale() == 'ar' ? ($account->name_ar ?? $account->name) : ($account->name_en ?? $account->name) }}
    </td>
    <td class="text-center"><code>{{ $account->code }}</code></td>
    <td class="text-center">
        @if($level === 0)
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 rounded-pill">
                {{ $account->type_name }}
            </span>
        @else
            <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 rounded-pill small">
                Sub-Account
            </span>
        @endif
    </td>
    <td class="text-end pe-4 fw-bold {{ $account->total_hierarchy_balance < 0 ? 'text-danger' : ($account->total_hierarchy_balance > 0 ? 'text-success' : 'text-muted') }}">
        ${{ number_format(abs($account->total_hierarchy_balance), 2) }}
        <small class="opacity-50 ms-1">{{ $account->total_hierarchy_balance >= 0 ? 'DR' : 'CR' }}</small>
    </td>
    <td class="text-center">
        <div class="dropdown">
            <button class="btn btn-sm btn-light rounded-circle" type="button" data-bs-toggle="dropdown">
                <i class="fa-solid fa-ellipsis-vertical"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li>
                    <a class="dropdown-item py-2" href="{{ route('accounting.statement', $account->id) }}">
                        <i class="fa-solid fa-file-invoice me-2 opacity-50"></i> Statement
                    </a>
                </li>
                <li>
                    <button class="dropdown-item py-2" data-bs-toggle="modal" data-bs-target="#editAccountModal{{ $account->id }}">
                        <i class="fa-solid fa-pen me-2 opacity-50"></i> Edit
                    </button>
                </li>
                <li><hr class="dropdown-divider opacity-50"></li>
                <li>
                    <form action="{{ route('accounting.accounts.destroy', $account->id) }}" method="POST" class="account-delete-form">
                        @csrf @method('DELETE')
                        <button type="submit" class="dropdown-item py-2 text-danger">
                            <i class="fa-solid fa-trash me-2 opacity-50"></i> Delete
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </td>
</tr>

@if($account->relationLoaded('children') && $account->children->count() > 0)
    @foreach($account->children as $child)
        @include('accounting.partials.account_row', ['account' => $child, 'level' => $level + 1])
    @endforeach
@endif
