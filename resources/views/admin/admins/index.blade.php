@extends('admin.layouts.app')

@section('panel')
<div class="container">
    <h2>All Employees</h2>
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('admin.admins.create') }}" class="btn btn-success mb-3">Add Employee</a>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Employee Id</th>
                <th>Name</th>
                <th>Email</th>
                <th>Username</th>
                <th>Roles</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($admins as $admin)
                <tr>
                    <td>{{ $admin->id }}</td>
                    <td>{{ $admin->name }}</td>
                    <td>{{ $admin->email }}</td>
                    <td>{{ $admin->username }}</td>
                    <td>{{ implode(', ', $admin->roles->pluck('name')->toArray()) }}</td>
                    <td>
                        <a href="{{ route('admin.admins.edit', $admin->id) }}" class="btn btn-primary">Edit</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection