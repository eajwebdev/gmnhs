{{--
    Shared screen for the PPE / high value / low value / intangible account titles.
    Expects: $prefix (ppe|lv|hv|int), $typeLabel, $properties, $categories, $property, $selected (nullable)
--}}
@php
    $editing = $selected !== null;
    $suggestedPrefix = ($property && $property->default_code !== '' && $property->property_code !== '')
        ? $property->default_code.'-'.$property->property_code.'-'
        : '';
@endphp

<div class="row crud-layout">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ $typeLabel }} account titles</h3>
                <span class="count-pill">{{ $properties->count() }} {{ \Illuminate\Support\Str::plural('record', $properties->count()) }}</span>
            </div>
            <div class="card-body">
                <table id="example1" class="table table-hover">
                    <thead>
                        <tr>
                            <th class="cell-num">#</th>
                            <th>Account No.</th>
                            <th>Account Title</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th class="cell-actions" data-orderable="false">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($properties as $row)
                            <tr id="tr-{{ $row->id }}" class="{{ $editing && $row->id == $selected->id ? 'bg-selectEdit' : '' }}">
                                <td class="cell-num">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</td>
                                <td class="cell-code">{{ $row->account_number }}</td>
                                <td class="cell-strong">{{ $row->account_title }}</td>
                                <td><span class="tag">{{ $row->account_title_abbr }}</span></td>
                                <td>{{ $row->cat_name ?? '—' }}</td>
                                <td class="cell-actions">
                                    <div class="row-actions">
                                        <a href="{{ route($prefix.'Edit', $row->id) }}" class="btn btn-icon" title="Edit" aria-label="Edit {{ $row->account_title }}">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <button type="button" class="btn btn-icon is-danger" title="Delete" aria-label="Delete {{ $row->account_title }}"
                                            data-delete-url="{{ route($prefix.'Delete', $row->id) }}"
                                            data-delete-label="{{ $row->account_title }}"
                                            @if($editing && $row->id == $selected->id) data-after-delete="{{ route($prefix.'Read') }}" @endif>
                                            <i class="fas fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card crud-form">
            <div class="card-header">
                <h3 class="card-title">{{ $editing ? 'Edit account title' : 'New account title' }}</h3>
                <span class="mode-flag {{ $editing ? '' : 'is-new' }}">{{ $editing ? 'Editing' : 'Create' }}</span>
            </div>
            <div class="card-body">
                @unless($property)
                    <div class="alert alert-warning">This property type is not configured in the system yet.</div>
                @endunless

                <form id="accountTitleForm" action="{{ $editing ? route($prefix.'Update') : route($prefix.'Create') }}" method="POST">
                    @csrf
                    @if($editing)
                        <input type="hidden" name="id" value="{{ $selected->id }}">
                    @endif

                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category_id" class="form-control select2bs4" style="width: 100%;" data-prefix="{{ $suggestedPrefix }}">
                            <option value="">Select a category</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->cat_code }}" @selected(old('category_id', $selected->category_id ?? null) == $cat->cat_code)>
                                    {{ $cat->cat_code }} · {{ $cat->cat_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="account_number">Account number</label>
                        <input type="text" id="account_number" name="account_number" class="form-control mono"
                            value="{{ old('account_number', $selected->account_number ?? '') }}"
                            placeholder="0-00-00-000" inputmode="numeric" maxlength="11" autocomplete="off">
                        <span class="form-hint">Format <code>0-00-00-000</code>. The last three digits become the item code.</span>
                    </div>

                    <div class="form-group">
                        <label for="account_title">Account title</label>
                        <input type="text" id="account_title" name="account_title" class="form-control"
                            value="{{ old('account_title', $selected->account_title ?? '') }}" placeholder="e.g. Office Equipment">
                    </div>

                    <div class="form-group">
                        <label for="account_title_abbr">Title type / abbreviation</label>
                        <input type="text" id="account_title_abbr" name="account_title_abbr" class="form-control"
                            value="{{ old('account_title_abbr', $selected->account_title_abbr ?? '') }}" placeholder="e.g. OE"
                            oninput="this.value = this.value.toUpperCase()">
                    </div>

                    <div class="form-actions">
                        @if($editing)
                            <a href="{{ route($prefix.'Read') }}" class="btn btn-default">Cancel</a>
                        @endif
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-floppy-disk"></i> {{ $editing ? 'Save changes' : 'Add account title' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
