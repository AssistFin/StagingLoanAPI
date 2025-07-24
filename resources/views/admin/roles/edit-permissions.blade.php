@extends('admin.layouts.app')

@section('panel')
<div class="container">
    <h4>Edit Permissions for Role: {{ $role->name }}</h4>

    <form action="{{ route('admin.roles.update.permissions', $role->id) }}" method="POST">
        @csrf

        <div class="row">
            @foreach($menus as $menu)
                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-header">
                            <input type="checkbox" id="menu{{ $menu->id }}" name="menus[]" value="{{ $menu->id }}" 
                                {{ in_array($menu->id, $roleMenus) ? 'checked' : '' }}> 
                            <strong>{{ $menu->name }}</strong>
                        </div>
                        <div class="card-body">
                            @foreach($menu->submenus as $submenu)
                                <div class="form-check">
                                    <input class="form-check-input" id="submenus{{ $submenu->id }}" type="checkbox" name="submenus[]" value="{{ $submenu->id }}" 
                                        {{ in_array($submenu->id, $roleSubmenus) ? 'checked' : '' }}>
                                    <label class="form-check-label">
                                        {{ $submenu->name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <button type="submit" class="btn btn-success">Save Permissions</button>
    </form>
</div>
@endsection