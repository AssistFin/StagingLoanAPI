@extends('admin.layouts.app')

@section('panel')

    <div class="row">
        <div class="col-lg-12">
            <div class="card  b-radius--10">
                <div class="card-body p-0">
                    <div class="d-flex justify-content-end mb-3">
                        <a href="{{ route('admin.collection.createconfig') }}" class="btn btn-success mb-3">Add Collection Configuration</a>
                    </div>
                    <div class="table-responsive--md table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>Sr No</th>
                                    <th>Principal Off (%)</th>
                                    <th>Interest Off (%)</th>
									<th>Penal Off (%)</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $x = 1; @endphp
                                @foreach($uwclogs as $uwclog)
                                    <tr>
                                        <td>{{ $x++ }}</td>
                                        <td>{{ $uwclog->pr_off ?? 'Unknown' }}</td>
										<td>{{ $uwclog->in_off ?? 'Unknown' }}</td>
                                        <td>{{ $uwclog->pe_off ?? 'Unknown' }}</td>
                                        <td>
                                            <a href="{{ route('admin.collection.editconfig', $uwclog->id) }}" class="btn btn-primary btn-sm">Edit</a>&nbsp;&nbsp; 
                                            @if ($uwclog->status == 1)
                                                <span class="badge btn-success">Active</span>
                                            @else
                                                <button class="btn btn-sm btn-danger activate-btn" data-id="{{ $uwclog->id }}">Inactive
                                                </button>
                                            @endif
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
@push('script')
<script>
$(document).ready(function() {
    $('.activate-btn').click(function() {
        const id = $(this).data('id');
        const button = $(this);

        $.ajax({
            url: "{{ url('admin/collection/activate') }}/" + id,
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    // Reset all rows visually
                    $('td').removeClass('btn-success');
                    $('td .badge').remove();
                    $('td .activate-btn').show();

                    // Highlight the activated one
                    const row = $('tr[data-id="' + response.active_id + '"]');
                    row.addClass('btn-success');
                    button.hide().before('<span class="badge btn-success">Active</span>');

                    alert('Activated Successfully.')
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                }
            },
            error: function() {
                toastr.error('Something went wrong. Try again.');
            }
        });
    });
});
</script>
@endpush
