@extends('admin.layouts.app')

@section('panel')

    <div class="row">
        <div class="col-lg-12">
            <div class="card  b-radius--10">
                <div class="card-body p-0">
                    <div class="d-flex justify-content-end mb-3">
                        <a href="{{ route('admin.template.createemail') }}" class="btn btn-success mb-3">Add New Email Template</a>
                    </div>
                    <div class="table-responsive--md table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>Sr No</th>
                                    <th>Title</th>
                                    <th>Added On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $x = 1; @endphp
                                @foreach($etemplate as $key => $temp)
                                    <tr>
                                        <td>{{ $x++ }}</td>
                                        <td>{{ $temp->title }}</td>
                                        <td>{{ $temp->created_at }}</td>
                                        <td>
                                            <a href="{{ route('admin.template.edit.emailtemplates', $temp->id) }}" class="btn btn-primary btn-sm">Edit</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <x-confirmation-modal />
@endsection