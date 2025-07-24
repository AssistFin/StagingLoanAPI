@extends('admin.layouts.app')

@section('panel')
<div class="container">
    <h2>Edit {{ ucwords($admin->username) }} User</h2>
    <form method="POST" action="{{ route('admin.admins.update', $admin->id) }}">
        @csrf
        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" value="{{ $admin->name }}" required>
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="{{ $admin->email }}" required>
        </div>
        <div class="mb-3">
            <label>Mobile</label>
            <input type="text" name="mobile" class="form-control" value="{{ $admin->mobile }}" required>
        </div>
        <div class="mb-3">
            <label>Username</label>
            <input type="text" name="username" class="form-control" value="{{ $admin->username }}" required>
        </div>
        <div class="mb-3">
            <label>New Password (optional)</label>
            <input type="password" name="password" class="form-control">
        </div>
        <div class="mb-3">
            <label>Assign Roles</label>
            <select name="roles[]" multiple class="form-control" style="height: max-content;" required>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ $admin->roles->contains($role->id) ? 'selected' : '' }}>{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-primary">Update</button>
    </form>
</div>
@endsection