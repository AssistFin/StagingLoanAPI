@extends('admin.layouts.app')

@section('panel')
<div class="container mt-4">
    <h2>Create New Role</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.roles.store') }}">
        @csrf
        <div class="form-group">
            <label for="name">Role Name</label>
            <input type="text" class="form-control" name="name" required placeholder="e.g. Credit Manager" value="{{ old('name') }}">
            @error('name')
                <span class="text-danger d-block mt-1">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary mt-2">Create Role</button>
        <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary mt-2">Back</a>
    </form>
</div>
@endsection
