@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <section class="section">
        <div class="container">
            <div class="row gy-5">
                <div class="col-lg-8">
                    <div class="blog-post">
                        <img src="{{ getImage('assets/images/frontend/blog/' . @$blog->data_values->image, '860x450') }}" class="img-fluid" />
                        <div class="blog-post__body">
                            <div class="blog-post__date">
                                <h4 class="mt-0 mb-1 t-text-white">{{ showDateTime($blog->created_at, 'd') }}</h4>
                                <p class="mb-0 t-text-white text-capitalize">{{ showDateTime($blog->created_at, 'M') }}</p>
                            </div>
                            <h3 class="text-capitalize mt-0">
                                {{ __($blog->data_values->title) }}
                            </h3>
                            <ul class="list list--row">

                                <li class="list--row__item">
                                    <div class="blog-post__meta">
                                        <div class="blog-post__meta-icon">
                                            <i class="bx bxs-calendar"></i>
                                        </div>
                                        <div class="blog-post__meta-text text-uppercase">
                                            <a href="#" class="t-link t-link--primary t-text">{{ showDateTime($blog->created_at) }}</a>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                            <div id="content">
                                <p>
                                    @php echo @$blog->data_values->description_nic @endphp
                                </p>
                            </div>
                        </div>
                    </div>
                    @push('fbComment')
                        @php echo loadExtension('fb-comment') @endphp
                    @endpush
                </div>

                <div class="col-lg-4">
                    <ul class="list list--column">
                        <li class="list--column__item-xl">
                            <div class="widget bg--light-1">
                                <h4 class="widget__title text-capitalize mb-4 mt-0">
                                    @lang('Latest Blogs')
                                </h4>
                                <ul class="list list--column widget-category">
                                    @foreach ($latestBlogs as $blog)
                                        <li class="list--column__item widget-category__item">
                                            <div class="d-flex pb-3">
                                                <div class="me-3 flex-shrink-0">
                                                    <div class="blog-thumb">
                                                        <img src="{{ getImage('assets/images/frontend/blog/thumb_' . @$blog->data_values->image, '430x225') }}" />
                                                    </div>
                                                </div>
                                                <div class="article">
                                                    <h6 class="texte-capitalize t-fw-md mt-0 mb-2">
                                                        <a href="{{ route('blog.details', [slug($blog->data_values->title), $blog->id]) }}" class="t-link d-inline-block t-text-heading fw-md t-link--primary text-capitalize">
                                                            {{ __(strLimit($blog->data_values->title, 40)) }}
                                                        </a>
                                                    </h6>

                                                    <ul class="list list--row justify-content-between">
                                                        <li class="list--row__item">
                                                            <div class="blog-post__meta">
                                                                <div class="span blog-post__meta-icon">
                                                                    <i class="bx bxs-calendar"></i>
                                                                </div>
                                                                <div class="span blog-post__meta-text text-uppercase">
                                                                    <a href="blog-post.html" class="t-link t-link--primary t-text-heading">{{ showDateTime($blog->created_at, 'd-M-Y') }}</a>
                                                                </div>
                                                            </div>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
@endsection
@push('fbComment')
    @php echo loadExtension('fb-comment') @endphp
@endpush
