<div class="sidebar bg--dark">
    <button class="res-sidebar-close-btn"><i class="las la-times"></i></button>
    <div class="sidebar__inner">
        <div class="sidebar__logo">
            <a href="{{ route('admin.dashboard') }}" class="sidebar__main-logo">
                <h2>LoanOne</h2>
            </a>
        </div>

        <div class="sidebar__menu-wrapper" id="sidebar__menuWrapper">
            <ul class="sidebar__menu">
                @if (auth('admin')->check())
                    @php
                        $menus = auth('admin')->user()->accessibleMenus();
                    @endphp

                    @foreach ($menus as $menu)
                        <li class="sidebar-menu-item {{ ($menu->submenus->count() == 0) ? menuActive('$menu->route') : 'sidebar-dropdown' }}">
                            @if ($menu->submenus->count() == 0)
                            <a href="{{ route($menu->route) }}" class="nav-link ">
                                <i class="menu-icon las la-home"></i>
                                <span class="menu-title">{{ $menu->name }}</span>
                            </a>
                            @endif
                            @if ($menu->submenus->count() > 0)

                            <a href="javascript:void(0)" class="{{ menuActive('$menu->route', 4) }}">
                                <i class="menu-icon las la-search-dollar"></i>
                                <span class="menu-title">{{ $menu->name }}</span>
                            </a>

                            <div class="sidebar-submenu {{ menuActive($menu->route) }} ">
                                <ul>
                                    @foreach ($menu->submenus as $submenu)
                                        <li class="sidebar-menu-item {{ request()->routeIs($submenu->route) ? 'active' : '' }} ">
                                            <a href="{{ route($submenu->route) }}" class="nav-link">
                                                <i class="menu-icon las la-dot-circle"></i>
                                                <span class="menu-title">{{ $submenu->name }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                @endif
            </ul>
            <div class="text-center mb-3 text-uppercase">
                <span class="text--primary">{{ __(systemDetails()['name']) }}</span>
                <span class="text--success">@lang('V'){{ systemDetails()['version'] }} </span>
            </div>
        </div>
    </div>
</div>
<!-- sidebar end -->

@push('script')
    <script>
        if ($('li').hasClass('active')) {
            $('#sidebar__menuWrapper').animate({
                scrollTop: eval($(".active").offset().top - 320)
            }, 500);
        }
    </script>
@endpush
