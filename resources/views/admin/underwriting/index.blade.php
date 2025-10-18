@extends('admin.layouts.app')

@section('panel')

    <div class="row">
        <div class="col-lg-12">
            <div class="card  b-radius--10">
                <div class="card-body p-0">
                    <div class="d-flex justify-content-end mb-3">
                        <a href="{{ route('admin.underwriting.create') }}" class="btn btn-success mb-3">Set U/W Configuration</a>
                    </div>
                    <div class="table-responsive--md table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>Sr No</th>
                                    <th>Name</th>
                                    <th>Added On</th>
                                    <th>Remark</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($uwclogs as $uwclog)
                                    <tr>
                                        <td>{{ $uwclog->id }}</td>
                                        <td>{{ $uwclog->admin->name ?? 'Unknown' }}</td>
                                        <td>{{ $uwclog->created_at->format('d-m-Y H:i') }}</td>
                                        <td>
                                            <a href="#" 
                                            class="btn btn-primary btn-sm view-remark-btn" data-bs-toggle="modal" data-bs-target="#remarkModal"
                                            data-remark="{{ $uwclog->remark ?? 'No Remark' }}">
                                            View Remark
                                            </a>
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

    <div class="modal fade" id="remarkModal" tabindex="-1" aria-labelledby="remarkModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="remarkModalLabel">Remark</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
            <p id="remarkText"></p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
        </div>
    </div>
    </div>
    <x-confirmation-modal />
@endsection
@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const remarkModal = document.getElementById('remarkModal');
        const remarkText = document.getElementById('remarkText');

        // Listen to Bootstrap modal show event
        remarkModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Button that triggered modal
            const remark = button.getAttribute('data-remark'); // Get remark from data attribute
            remarkText.textContent = remark; // Set modal text
        });
    });
</script>
@endpush