@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <section class="container section">
        <div class="row g-4">
            @foreach ($blogs as $blog)
                <div class="col-md-6 col-lg-4">
                    <div class="blog-post">
                        <a href="{{ route('blog.details', [slug($blog->data_values->title), $blog->id]) }}" class="t-link blog-post__img">
                            <img src="{{ getImage('assets/images/frontend/blog/thumb_' . @$blog->data_values->image, '430x225') }}" class="blog-post__img-is" />
                        </a>
                        <div class="blog-post__body">
                            <div class="blog-post__date">
                                <h4 class="mt-0 mb-1 t-text-white">{{ showDateTime($blog->created_at, 'd') }} </h4>
                                <p class="mb-0 t-text-white text-capitalize">{{ showDateTime($blog->created_at, 'M') }}
                                </p>
                            </div>
                            <h4 class="text-capitalize mt-0">
                                <a href="{{ route('blog.details', [slug($blog->data_values->title), $blog->id]) }}" class="t-link blog-post__title">
                                    {{ __($blog->data_values->title) }}
                                </a>
                            </h4>
                            <ul class="list list--row">
                                <li class="list--row__item">
                                    <div class="blog-post__meta">
                                        <div class="blog-post__meta-icon me-2">
                                            <i class="bx bxs-calendar"></i>
                                        </div>
                                        <div class="blog-post__meta-text text-uppercase">
                                            <a href="blog-post.html" class="t-link t-link--primary t-text">{{ showDateTime($blog->created_at) }}</a>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                            <p class="blog-post__article mt-4 mb-0">
                                @php echo __(strLimit(strip_tags($blog->data_values->description_nic), 90));@endphp
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach

            @if ($blogs->hasPages())
                <div class="col-12">
                    <ul class="list list--row justify-content-center align-items-center t-mt-60">
                        {{ paginateLinks($blogs) }}
                    </ul>
                </div>
            @endif
        </div>
    </section>
@endsection
