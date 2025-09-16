@extends('admin.layouts.app')

@section('panel')
<div class="container">
    <h4>Edit Permissions for Role -  {{ $role->name }}</h4>
    </br>
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
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Handle menu checkbox change
        document.querySelectorAll('input[id^="menu"]').forEach(function(menuCheckbox) {
            menuCheckbox.addEventListener('change', function () {
                const menuId = this.id.replace('menu', '');
                const card = this.closest('.card');
                const submenus = card.querySelectorAll('.form-check-input[id^="submenus"]');

                submenus.forEach(cb => cb.checked = this.checked);
            });
        });

        // Handle submenu checkbox change
        document.querySelectorAll('.form-check-input[id^="submenus"]').forEach(function(subCheckbox) {
            subCheckbox.addEventListener('change', function () {
                const card = this.closest('.card');
                const submenus = card.querySelectorAll('.form-check-input[id^="submenus"]');
                const menuCheckbox = card.querySelector('input[id^="menu"]');

                const anyChecked = Array.from(submenus).some(cb => cb.checked);
                menuCheckbox.checked = anyChecked;
            });
        });
    });
</script>
@endsection