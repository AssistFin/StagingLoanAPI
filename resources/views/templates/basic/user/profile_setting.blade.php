@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-inner">
        <div class="mb-4">
            <h3 class="mb-2">{{ __($pageTitle) }}</h3>
        </div>
        <div class="row gy-4 justify-content-center">
            <div class="col-8 col-md-4 col-lg-4">
                <div class="card custom--card">
                    <div class="card-body p-3">
                        <div class="proifle-image-preview">
                            <img src="{{ getImage(getFilePath('userProfile') . '/' . $user->image, null, true) }}" alt="profile-image">
                        </div>
                        <ul class="list-group list-group-flush mt-3">
                            <li class="list-group-item d-flex flex-column gap-1 aling-items-center">
                                <h6><i class="la la-user"></i> {{ $user->username }}</h6>
                            </li>

                            <li class="list-group-item d-flex flex-column gap-1 aling-items-center">
                                <h6><i class="la la-envelope"></i> {{ $user->email }}</h6>
                            </li>

                            <li class="list-group-item d-flex flex-column gap-1 aling-items-center">
                                <h6><i class="la la-mobile"></i> +{{ $user->mobile }}</h6>
                            </li>

                            <li class="list-group-item d-flex flex-column gap-1 aling-items-center">
                                <h6><i class="la la-globe"></i> {{ $user->address->country }}</h6>
                            </li>

                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-7 col-sm-10 col-lg-8">
                <div class="card custom--card">
                    <div class="card-body">
                        <form class="register" action="" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label class="form-label">@lang('First Name')</label>
                                        <input type="text" class="form-control form--control" name="firstname" value="{{ $user->firstname }}" required>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label class="form-label">@lang('Last Name')</label>
                                        <input type="text" class="form-control form--control" name="lastname" value="{{ $user->lastname }}" required>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label class="form-label">@lang('State')</label>
                                        <input type="text" class="form-control form--control" name="state" value="{{ @$user->address->state }}">
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label class="form-label">@lang('Zip Code')</label>
                                        <input type="text" class="form-control form--control" name="zip" value="{{ @$user->address->zip }}">
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label class="form-label">@lang('City')</label>
                                        <input type="text" class="form-control form--control" name="city" value="{{ @$user->address->city }}">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label class="form-label">@lang('Image')</label>
                                        <input class="form-control form--control" id="imageUpload" name="image" type='file' accept=".png, .jpg, .jpeg" />
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label">@lang('Address')</label>
                                        <input type="text" class="form-control form--control" name="address" value="{{ @$user->address->address }}">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn--base mt-3 w-100">@lang('Submit')</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('style')
    <style>
        .list-group-item {
            border: none;
        }

        .user-profile-wrapper {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .profile-info {
            width: 320px;
        }

        .profile-form {
            width: calc(100% - 335px);
        }

        @media(max-width:767px) {
            .user-profile-wrapper {
                gap: 10px;
            }

            .profile-info {
                width: 380px;
            }

            .profile-form {
                width: 380px;
            }
        }

        @media(max-width:590px) {
            .profile-info {
                width: 300px;
            }

            .profile-form {
                width: 300px;
            }
        }

        .proifle-image-preview {
            text-align: center;
        }

        .proifle-image-preview img {
            width: 100%;
            height: auto;
            max-height: 300px;
            border-radius: 5px;
        }
    </style>
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";
            $("#imageUpload").on('change', function() {
                if (this.files && this.files[0]) {
                    let reader = new FileReader();
                    reader.onload = function(e) {
                        $('.proifle-image-preview img').attr('src', e.target.result)
                    }
                    reader.readAsDataURL(this.files[0]);
                }
            });
        })(jQuery);
    </script>
@endpush
