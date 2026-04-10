<!-- Edit Modal for {{ $account->name }} -->
<div class="modal fade" id="editAccountModal{{ $account->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('accounting.accounts.update', $account->id) }}" method="POST">
            @csrf @method('PUT')
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Edit Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Account Name (Arabic)</label>
                        <input type="text" name="name_ar" class="form-control bg-light border-0 rounded-3"
                            value="{{ $account->name_ar }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Account Name (English)</label>
                        <input type="text" name="name_en" class="form-control bg-light border-0 rounded-3"
                            value="{{ $account->name_en }}">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-muted">Account Code</label>
                            <input type="text" name="code" class="form-control bg-light border-0 rounded-3" value="{{ $account->code }}"
                                required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-muted">Account Type</label>
                            <select name="type" class="form-select bg-light border-0 rounded-3" required>
                                <option value="1" {{ $account->type == 1 ? 'selected' : '' }}>Asset</option>
                                <option value="2" {{ $account->type == 2 ? 'selected' : '' }}>Liability</option>
                                <option value="3" {{ $account->type == 3 ? 'selected' : '' }}>Equity</option>
                                <option value="4" {{ $account->type == 4 ? 'selected' : '' }}>Income</option>
                                <option value="5" {{ $account->type == 5 ? 'selected' : '' }}>Expense</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

@if($account->relationLoaded('children') && $account->children->count() > 0)
    @foreach($account->children as $child)
        @include('accounting.partials.account_modals', ['account' => $child])
    @endforeach
@endif
